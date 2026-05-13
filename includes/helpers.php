<?php
/**
 * Helpers réutilisables.
 */

/**
 * Envoie un downlink hex à TTN via l'API HTTP.
 * @return array [bool $ok, string $message]
 */
function send_downlink(string $app_id, string $api_key, string $device_id, string $hex): array
{
    if ($app_id === '' || $api_key === '') {
        return [false, 'Clés TTN manquantes.'];
    }

    $url = 'https://' . TTN_HOST . '/api/v3/as/applications/' . urlencode($app_id) .
           '/devices/' . urlencode($device_id) . '/down/push';

    $body = json_encode(['downlinks' => [[
        'f_port'      => 1,
        'frm_payload' => base64_encode(hex2bin($hex)),
        'priority'    => 'NORMAL',
    ]]]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $api_key", 'Content-Type: application/json'],
        CURLOPT_POST           => 1,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($code >= 200 && $code < 300)
        ? [true, 'Ordre envoyé !']
        : [false, "Échec TTN ($code)"];
}

/** Échappement HTML court. */
function e(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
