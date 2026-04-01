import paho.mqtt.client as mqtt
import mysql.connector
import json
import time

db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'Maya2026!',
    'database': 'ruche_connectee'
}

# Variables pour mémoriser la connexion actuelle
current_app_id = None
current_api_key = None
client = None

def on_message(client, userdata, msg):
    """Gère la réception d'un message TTN"""
    try:
        payload = json.loads(msg.payload.decode('utf-8'))
        
        # Extraction intelligente (On cherche dans 'data' car TTN l'emballe)
        data_block = payload.get('data', payload)
        
        device_id = data_block.get('end_device_ids', {}).get('device_id')
        uplink = data_block.get('uplink_message', {})
        decoded = uplink.get('decoded_payload', {})
        
        if not decoded or not device_id:
            return # Ce n'est pas un message utile
            
        # Récupération GPS si dispo
        lat, lon = 49.894, 2.295
        rx = uplink.get('rx_metadata', [])
        if rx and 'location' in rx[0]:
            lat = rx[0]['location'].get('latitude', lat)
            lon = rx[0]['location'].get('longitude', lon)
            
        # Connexion à MySQL et insertion
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        presence = 1 if decoded.get('presence_detectee') else 0
        tactile = 1 if decoded.get('tactile_actif') else 0
        
        sql = """INSERT INTO mesures 
                 (device_id, temperature, humidite, poids, luminosite, alerte_presence, alerte_choc, lat, lon) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"""
                 
        valeurs = (
            device_id, float(decoded.get('temperature_celsius', 0)), float(decoded.get('humidite_pourcent', 0)),
            float(decoded.get('poids_kg', 0)), int(decoded.get('luminosite_pourcent', 0)),
            presence, tactile, lat, lon
        )
        
        cursor.execute(sql, valeurs)
        conn.commit()
        cursor.close()
        conn.close()
        print(f"✅ 🐝 Mesure sauvegardée pour la ruche : {device_id} ({decoded.get('temperature_celsius')}°C)")
        
    except mysql.connector.Error as err:
        print(f"❌ Erreur MySQL : {err}")
    except Exception as e:
        print(f"❌ Erreur de code : {e}")

def get_credentials_from_db():
    """Va chercher la clé API entrée par l'utilisateur via l'interface web"""
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        # On cherche la première ruche qui possède une clé API configurée
        cursor.execute("SELECT ttn_app_id, ttn_api_key FROM ruches WHERE ttn_api_key IS NOT NULL AND ttn_api_key != '' LIMIT 1")
        row = cursor.fetchone()
        conn.close()
        if row:
            return row['ttn_app_id'], row['ttn_api_key']
    except Exception as e:
        print(f"Erreur de lecture DB : {e}")
    return None, None

# ==========================================
# BOUCLE PRINCIPALE (Tourne à l'infini)
# ==========================================
print("🤖 Démarrage du robot. En attente d'une clé API via l'interface web...")

while True:
    # 1. On regarde dans la base de données s'il y a des clés
    app_id, api_key = get_credentials_from_db()
    
    # 2. Si on trouve de nouvelles clés qu'on n'utilisait pas encore...
    if app_id and api_key and (app_id != current_app_id or api_key != current_api_key):
        print(f"\n🔄 Nouvelles clés détectées (App: {app_id}). Tentative de connexion...")
        
        # On coupe l'ancienne connexion s'il y en avait une
        if client:
            client.disconnect()
            client.loop_stop()
        
        current_app_id = app_id
        current_api_key = api_key
        
        # On lance la nouvelle connexion MQTT
        client = mqtt.Client()
        client.username_pw_set(current_app_id, current_api_key)
        client.on_message = on_message
        try:
            client.connect("eu1.cloud.thethings.network", 1883, 60)
            client.subscribe("v3/+/devices/+/up")
            client.loop_start() # Fait tourner l'écouteur en arrière-plan
            print("✅ Connecté avec succès au réseau TTN !")
        except Exception as e:
            print(f"❌ Identifiants TTN refusés : {e}")
            current_app_id = None # Force à réessayer à la prochaine boucle
            
    # 3. On attend 10 secondes avant de vérifier à nouveau
    time.sleep(10)
