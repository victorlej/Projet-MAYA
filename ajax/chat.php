<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

require_once __DIR__ . '/../config/config.php';

$input   = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$ctx     = $input['context'] ?? [];

if (!$message) {
    echo json_encode(['reply' => '']);
    exit;
}

// Build hive context string
$hive_ctx = '';
if (!empty($ctx['hasRuche'])) {
    $hive_ctx = "\n\nDonnées en temps réel de la ruche :";
    if ($ctx['temp']  !== null) $hive_ctx .= "\n- Température intérieure : {$ctx['temp']} °C (idéale 34–36 °C)";
    if ($ctx['hum']   !== null) $hive_ctx .= "\n- Humidité : {$ctx['hum']} % (idéale 40–70 %)";
    if ($ctx['poids'] !== null) $hive_ctx .= "\n- Poids : {$ctx['poids']} kg";
    if ($ctx['lum']   !== null) $hive_ctx .= "\n- Luminosité : {$ctx['lum']} %";
}

$system = <<<PROMPT
Tu es MAYA, l'assistante IA du rucher connecté. Tu es experte en apiculture et en analyse de données IoT de ruches (température, humidité, poids, luminosité). Tu donnes des conseils pratiques, précis et bienveillants aux apiculteurs, en t'appuyant sur les données capteurs quand elles sont disponibles. Tu réponds en français, de manière concise (3–5 phrases max), avec des emojis pertinents.{$hive_ctx}
PROMPT;

$payload = json_encode([
    'model'      => 'claude-haiku-4-5-20251001',
    'max_tokens' => 512,
    'system'     => $system,
    'messages'   => [
        ['role' => 'user', 'content' => $message],
    ],
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT => 30,
]);

$result    = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$reply = 'Désolé, une erreur est survenue. Vérifiez la clé API dans config.php.';
if ($http_code === 200) {
    $data  = json_decode($result, true);
    $reply = $data['content'][0]['text'] ?? $reply;
}

header('Content-Type: application/json');
echo json_encode(['reply' => $reply]);
