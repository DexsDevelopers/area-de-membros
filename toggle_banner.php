<?php
session_start();
require 'config.php';

// Apenas admins podem acessar
if (($_SESSION['role'] ?? '') !== 'admin') {
    exit('Acesso negado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_banner'])) {
    $id = intval($_POST['id_banner']);

    try {
        // Inverte o status atual do campo 'ativo' (de 0 para 1, ou de 1 para 0)
        $stmt = $pdo->prepare("UPDATE banners SET ativo = NOT ativo WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Erro ao alternar status do banner: " . $e->getMessage());
    }
}

header("Location: gerenciar_banners.php");
exit();
?>