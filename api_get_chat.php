<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$last_id = intval($_GET['since_id'] ?? 0);

try {
    $stmt = $pdo->prepare(
        "SELECT m.id, m.user_id, m.mensagem, m.timestamp, u.username 
         FROM chat_mensagens m 
         JOIN users u ON m.user_id = u.id
         WHERE m.id > ?
         ORDER BY m.timestamp ASC"
    );
    $stmt->execute([$last_id]);
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($mensagens);
} catch (PDOException $e) {
    http_response_code(500);
}
?>