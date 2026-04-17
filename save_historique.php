<?php
/**
 * save_historique.php
 * Enregistre chaque échange (question + réponse) dans la table `historique`,
 * avec la famille de la plante et le nom de la maladie (si détectée).
 * Appelé en POST par resultat.php avec { plante, question, reponse, famille, maladie }
 */
session_start();
include 'dataconnect.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non autorisé"]);
    exit();
}

$body = json_decode(file_get_contents("php://input"), true);

$user_id = (int) $_SESSION['user'];
$plante  = htmlspecialchars($body['plante']   ?? '');
$question= htmlspecialchars($body['question'] ?? '');
$reponse = $body['reponse'] ?? '';   // texte long, pas besoin d'htmlspecialchars ici
$famille = htmlspecialchars($body['famille'] ?? '');
$maladie = htmlspecialchars($body['maladie'] ?? '');

if (empty($plante) || empty($question) || empty($reponse)) {
    http_response_code(400);
    echo json_encode(["error" => "Données incomplètes"]);
    exit();
}

$sql = "INSERT INTO historique (user_id, plante_nom, famille, maladie_nom, question, reponse) 
        VALUES (?, ?, ?, ?, ?, ?)";
$req = $mysqlClient->prepare($sql);
$req->execute([$user_id, $plante, $famille, $maladie, $question, $reponse]);

echo json_encode(["status" => "ok", "id" => $mysqlClient->lastInsertId()]);
