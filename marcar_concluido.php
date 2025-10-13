<?php
session_start();
require 'config.php';

// Apenas usuários logados podem marcar progresso
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Apenas aceita requisições POST para segurança
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Método não permitido.');
}

// Pega o ID do usuário da sessão e o ID do curso do formulário
$user_id = $_SESSION['user_id'];
$curso_id = intval($_POST['curso_id'] ?? 0);

if ($curso_id > 0) {
    try {
        // Tenta inserir um novo registro. Se já existir (por causa da UNIQUE KEY), não faz nada.
        // É uma forma segura e eficiente de registrar a conclusão.
        $stmt = $pdo->prepare(
            "INSERT INTO user_progresso (user_id, curso_id, status) 
             VALUES (?, ?, 'concluido')
             ON DUPLICATE KEY UPDATE status = 'concluido'"
        );
        $stmt->execute([$user_id, $curso_id]);
        
        // Redireciona de volta para a página do curso
        header("Location: curso_pagina.php?id=" . $curso_id);
        exit();

    } catch (PDOException $e) {
        // Em caso de erro, redireciona de volta com uma mensagem de erro
        error_log('Erro ao marcar progresso: ' . $e->getMessage());
        header("Location: curso_pagina.php?id=" . $curso_id . "&error=progress_failed");
        exit();
    }
} else {
    // Se o ID do curso for inválido, volta para a home
    header("Location: index.php");
    exit();
}
?>