<?php
/**
 * groq_proxy.php
 * Proxy serveur vers l'API Groq — la clé API n'est jamais exposée au navigateur.
 * Appelé en POST par resultat.php avec { messages: [...] }
 */
session_start();
header("Content-Type: application/json");

// ✅ Seuls les utilisateurs connectés peuvent appeler ce proxy
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non autorisé — session expirée"]);
    exit();
}

//  Configuration 
define('GROQ_API_KEY', 'my_key');   // ← Remplacer par votre clé https://console.groq.com
define('GROQ_MODEL',   'llama-3.3-70b-versatile');
define('GROQ_URL',     'https://api.groq.com/openai/v1/chat/completions');

//  Vérification cURL disponible
if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(["error" => "cURL non activé dans PHP. Activez extension=curl dans php.ini et redémarrez Apache."]);
    exit();
}

//Lecture du body JSON
$raw  = file_get_contents("php://input");
$body = json_decode($raw, true);

if (empty($body['messages']) || !is_array($body['messages'])) {
    http_response_code(400);
    echo json_encode(["error" => "Paramètre messages manquant ou invalide"]);
    exit();
}

// ─── Appel Groq via cURL ──────────────────────────────────────────────────────
$payload = json_encode([
    "model"       => GROQ_MODEL,
    "messages"    => $body['messages'],
    "max_tokens"  => 1024,
    "temperature" => 0.7
]);

$ch = curl_init(GROQ_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        "Content-Type: application/json",
        "Authorization: Bearer " . GROQ_API_KEY
    ],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ─── Erreur réseau cURL wa
if ($curlError) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur réseau cURL : $curlError"]);
    exit();
}

// ─── Erreur API Groq (clé invalide, quota…) ───────────────────────────────────
if ($httpCode !== 200) {
    $detail = json_decode($response, true);
    $msg    = $detail['error']['message'] ?? $response;
    http_response_code($httpCode);
    echo json_encode([
        "error"   => "Groq a répondu $httpCode",
        "detail"  => $msg
    ]);
    exit();
}

http_response_code(200);
echo $response;