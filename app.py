import streamlit as st
import pandas as pd
import numpy as np
import requests
import json
import sqlite3
import base64
import time
from datetime import datetime
import paho.mqtt.client as mqtt

# ==========================================
# GESTION ROBUSTE DE LA BASE DE DONNÉES
# ==========================================
DB_NAME = 'ruches_history.db'

def run_query(query, params=(), fetch=False):
    """Ouvre, exécute et ferme la connexion proprement à chaque appel"""
    with sqlite3.connect(DB_NAME) as conn:
        cursor = conn.cursor()
        cursor.execute(query, params)
        conn.commit()
        if fetch:
            return cursor.fetchall()
        return None

# Initialisation des tables
run_query('''CREATE TABLE IF NOT EXISTS utilisateurs (nom TEXT UNIQUE)''')
run_query('''CREATE TABLE IF NOT EXISTS ruches (device_id TEXT UNIQUE, nom_affichage TEXT, proprietaire TEXT)''')
run_query('''CREATE TABLE IF NOT EXISTS mesures 
             (date TEXT, device_id TEXT, temp REAL, hum REAL, poids REAL, lum REAL, presence INT, tactile INT, lat REAL, lon REAL)''')

# ==========================================
# DESIGN ET ANIMATIONS
# ==========================================
st.markdown("""
<style>
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(220, 38, 38, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
}
.alerte-pulse { animation: pulse 2s infinite; background-color: #DC2626; color: white; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 25px; border: 2px solid #991B1B; }
[data-testid="stMetric"] { background-color: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 20px; transition: all 0.3s ease; }
div[data-testid="stMetricValue"] { color: #F59E0B !important; font-weight: 800 !important; font-size: 2rem !important; }
h1, h2, h3 { color: #F59E0B !important; }
</style>
""", unsafe_allow_html=True)

# ==========================================
# MQTT : RÉCEPTION ET ENREGISTREMENT
# ==========================================
def on_message(client, userdata, msg):
    try:
        payload = json.loads(msg.payload.decode('utf-8'))
        device_id = payload.get('end_device_ids', {}).get('device_id', 'inconnu')
        uplink = payload.get('uplink_message', payload.get('data', {}).get('uplink_message', {}))
        decoded = uplink.get('decoded_payload', {})
        
        # GPS
        lat, lon = 49.894, 2.295
        rx_metadata = uplink.get('rx_metadata', [])
        if rx_metadata and 'location' in rx_metadata[0]:
            lat = rx_metadata[0]['location'].get('latitude', lat)
            lon = rx_metadata[0]['location'].get('longitude', lon)

        if decoded:
            temp = decoded.get('temperature_celsius', 0.0)
            hum = decoded.get('humidite_pourcent', 0.0)
            poids = decoded.get('poids_kg', 0.0)
            lum = decoded.get('luminosite_pourcent', 0.0)
            presence = 1 if decoded.get('presence_detectee', False) else 0
            tactile = 1 if decoded.get('tactile_actif', False) else 0
            
            now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            # Écriture directe via notre fonction sécurisée
            run_query("INSERT INTO mesures VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                     (now, device_id, temp, hum, poids, lum, presence, tactile, lat, lon))
    except Exception as e:
        print(f"Erreur MQTT: {e}")

@st.cache_resource
def init_mqtt(app_id, api_key):
    client = mqtt.Client()
    client.username_pw_set(app_id, api_key)
    client.on_message = on_message
    try:
        client.connect("eu1.cloud.thethings.network", 1883, 60)
        client.subscribe("v3/+/devices/+/up")
        client.loop_start() 
        return True
    except: return False

def send_downlink(app_id, api_key, device_id, payload_hex_string):
    try:
        pub_client = mqtt.Client()
        pub_client.username_pw_set(app_id, api_key)
        pub_client.connect("eu1.cloud.thethings.network", 1883, 60)
        topic = f"v3/{app_id}@ttn/devices/{device_id}/down/push"
        payload_b64 = base64.b64encode(bytes.fromhex(payload_hex_string)).decode()
        msg = { "downlinks": [{"f_port": 1, "frm_payload": payload_b64, "priority": "NORMAL"}] }
        pub_client.publish(topic, json.dumps(msg))
        pub_client.disconnect()
        return True
    except: return False

# ==========================================
# BARRE LATÉRALE : GESTION COMPTE ET RUCHES
# ==========================================
with st.sidebar:
    st.header("👤 Espace Apiculteur")
    
    # 1. INSCRIPTION
    with st.expander("📝 Créer un compte"):
        nom_new = st.text_input("Nouveau pseudo")
        if st.button("Valider l'inscription"):
            if nom_new:
                try:
                    run_query("INSERT INTO utilisateurs (nom) VALUES (?)", (nom_new,))
                    st.success("Compte créé !")
                    st.rerun()
                except: st.error("Nom déjà pris.")

    # 2. CONNEXION
    users = run_query("SELECT nom FROM utilisateurs", fetch=True)
    liste_users = [u[0] for u in users]
    if not liste_users:
        st.warning("Aucun compte trouvé.")
        st.stop()
    
    user_session = st.selectbox("Apiculteur actif", liste_users)
    
    st.divider()

    # 3. MES RUCHES (ISOLATION)
    st.subheader(f"🐝 Ruches de {user_session}")
    ruches_user = run_query("SELECT device_id, nom_affichage FROM ruches WHERE proprietaire=?", (user_session,), fetch=True)
    
    with st.expander("➕ Ajouter une ruche"):
        dev_id = st.text_input("Device ID (ex: projet-iot)")
        nick = st.text_input("Nom de baptême")
        if st.button("Lier à mon compte"):
            if dev_id and nick:
                try:
                    run_query("INSERT INTO ruches VALUES (?, ?, ?)", (dev_id, nick, user_session))
                    st.success("Ruche ajoutée !")
                    st.rerun()
                except: st.error("ID déjà utilisé.")

    # SÉLECTION DE LA RUCHE
    if ruches_user:
        ruche_map = {f"{r[1]} ({r[0]})": r[0] for r in ruches_user}
        selected_label = st.selectbox("Ruche à inspecter", list(ruche_map.keys()))
        selected_id = ruche_map[selected_label]
        selected_nick = selected_label.split(' (')[0]
    else:
        st.info("Ajoutez une ruche pour voir les données.")
        st.stop()

    # 4. RÉGLAGES ET DANGER
    st.divider()
    ttn_id = st.text_input("App ID TTN", value="projet-maya")
    ttn_key = st.text_input("API Key TTN", type="password")
    if st.button("📡 Démarrer l'écoute réseau"):
        if init_mqtt(ttn_id, ttn_key): st.success("Passerelle active !")
    
    auto_ref = st.checkbox("🔄 Actualisation auto (10s)")

    with st.expander("🗑️ Zone de Danger"):
        if st.button(f"Supprimer la ruche {selected_nick}"):
            run_query("DELETE FROM ruches WHERE device_id=?", (selected_id,))
            run_query("DELETE FROM mesures WHERE device_id=?", (selected_id,))
            st.rerun()
        if st.button("Supprimer mon compte apiculteur"):
            run_query("DELETE FROM utilisateurs WHERE nom=?", (user_session,))
            run_query("DELETE FROM ruches WHERE proprietaire=?", (user_session,))
            st.rerun()

# ==========================================
# AFFICHAGE DU DASHBOARD
# ==========================================
# On récupère LA dernière mesure de CETTE ruche
last = run_query("SELECT * FROM mesures WHERE device_id=? ORDER BY date DESC LIMIT 1", (selected_id,), fetch=True)

if not last:
    st.warning(f"Aucune donnée reçue pour la ruche **{selected_nick}**. Assurez-vous que l'ID **{selected_id}** est correct sur TTN.")
    if auto_ref: time.sleep(10); st.rerun()
    st.stop()

# Mapping des données
m = last[0]
d = {'date': m[0], 'temp': m[2], 'hum': m[3], 'poids': m[4], 'lum': m[5], 'pres': m[6], 'tact': m[7], 'lat': m[8], 'lon': m[9]}

st.title(f"🍯 {selected_nick}")
st.caption(f"ID : {selected_id} | Dernière réception : {d['date']}")

# Alertes visuelles
if d['temp'] < 30 and d['temp'] > 0:
    st.markdown(f"<div class='alerte-pulse'>🚨 DANGER HYPOTHERMIE : {d['temp']}°C</div>", unsafe_allow_html=True)
elif d['temp'] > 37:
    st.markdown(f"<div class='alerte-pulse' style='background-color:#EA580C;'>🔥 DANGER SURCHAUFFE : {d['temp']}°C</div>", unsafe_allow_html=True)

col1, col2, col3, col4 = st.columns(4)
col1.metric("🌡️ Température", f"{d['temp']} °C")
col2.metric("💧 Humidité", f"{d['hum']} %")
col3.metric("⚖️ Poids", f"{d['poids']} kg")
col4.metric("☀️ Lumière", f"{d['lum']} %")

# Graphique Historique
st.subheader("📈 Évolution pondérale")
hist = run_query("SELECT date, poids FROM mesures WHERE device_id=? ORDER BY date DESC LIMIT 30", (selected_id,), fetch=True)
if hist:
    df_hist = pd.DataFrame(hist, columns=['Date', 'Poids'])
    df_hist['Date'] = pd.to_datetime(df_hist['Date'])
    st.line_chart(df_hist.set_index('Date'), color="#F59E0B")

# Sécurité et Analyse
c_diag, c_sec = st.columns(2)
with c_diag:
    st.subheader("🧠 Diagnostic Bot")
    if 34 <= d['temp'] <= 35.5: st.success("Conditions idéales pour le couvain.")
    else: st.info("Analyse en cours...")
with c_sec:
    st.subheader("🛡️ Sécurité")
    if d['pres']: st.error("Mouvement détecté !")
    else: st.success("Périmètre calme.")

# Pilotage
st.subheader("🕹️ Commandes à distance")
if st.button("📢 Faire sonner le Buzzer (01)"):
    if send_downlink(ttn_id, ttn_key, selected_id, "01"): st.success("Ordre envoyé !")

# Auto-refresh logic
if auto_ref:
    time.sleep(10)
    st.rerun()
