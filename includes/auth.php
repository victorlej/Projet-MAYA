<?php
/**
 * Garde-fou session + connexion PDO partagée.
 * Redirige vers login.php si pas connecté.
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$user_id = $_SESSION['user_id'];
