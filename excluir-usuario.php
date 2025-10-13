<?php
session_start();
require 'config.php';

// 1. Padroniza a verificação de autorização para 'role'
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Garante que o script só aceite requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se não for POST, nega o acesso
    header('HTTP/1.0 405 Method Not Allowed');
    echo 'Método não permitido.';
    exit();
}

// 3. Valida o token CSRF para prevenir ataques
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    // Redireciona com erro se o token for inválido
    header("Location: admin_painel.php?error=csrf_fail");
    exit();
}

// Pega o ID do usuário do formulário POST
$id = intval($_POST['id'] ?? 0);

// Proteção extra: impede que o admin se auto-exclua
// (Supondo que você armazene o ID do usuário logado em $_SESSION['user_id'])
if ($id === ($_SESSION['user_id'] ?? null)) {
    header("Location: admin_painel.php?error=self_delete");
    exit();
}

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redireciona com mensagem de sucesso
        header("Location: admin_painel.php?msg=user_deleted");
        exit();
    } catch (PDOException $e) {
        // Em caso de erro, redireciona com uma mensagem genérica
        error_log('Erro ao excluir usuário: ' . $e->getMessage()); // Loga o erro real
        header("Location: admin_painel.php?error=database_error");
        exit();
    }
} else {
    // Redireciona se o ID for inválido
    header("Location: admin_painel.php?error=invalid_id");
    exit();
}
?>