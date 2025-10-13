<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE user_id = ? AND lida = 0");
        $stmt->execute([$_SESSION['user_id']]);
        // Responde com sucesso
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error']);
    }
}
?>