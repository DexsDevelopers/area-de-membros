<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit();
}

$user_id = $_SESSION['user_id'];
$mensagem = trim($_POST['mensagem'] ?? '');

if (!empty($mensagem)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO chat_mensagens (user_id, mensagem) VALUES (?, ?)");
        $stmt->execute([$user_id, $mensagem]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>