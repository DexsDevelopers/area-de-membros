<?php
session_start();
require 'config.php';

// SUGESTÃO 3: Garante que o script só aceite requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    echo 'Método não permitido.';
    exit();
}

// SUGESTÃO 1: Validação do token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    // Redireciona com erro em vez de mostrar mensagem crua
    header("Location: admin_painel.php?error=csrf_fail");
    exit();
}
// Limpa o token para que ele não possa ser reutilizado
unset($_SESSION['csrf_token']);


// Verifica se o usuário está logado e é admin (Sua implementação original, que está perfeita)
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Recebe dados do formulário via POST
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validação simples
if ($username === '' || $password === '') {
    // SUGESTÃO 2: Redireciona com mensagem de erro
    header("Location: admin_painel.php?error=empty_fields");
    exit();
}

// Evita usernames inválidos
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    header("Location: admin_painel.php?error=invalid_username");
    exit();
}

// SUGESTÃO BÔNUS: Política de senha mínima
if (strlen($password) < 8) {
    header("Location: admin_painel.php?error=password_too_short");
    exit();
}


try {
    // Verifica se username já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header("Location: admin_painel.php?error=username_exists");
        exit();
    }

    // Cria hash da senha
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insere novo usuário com role 'user'
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
    $stmt->execute([$username, $hash]);

    // Redireciona para painel admin com sucesso
    header("Location: admin_painel.php?msg=usuario_cadastrado");
    exit();

} catch (PDOException $e) {
    // Em caso de erro de DB, redireciona com uma mensagem genérica
    error_log('Erro ao cadastrar usuário: ' . $e->getMessage()); // Loga o erro real para o admin ver
    header("Location: admin_painel.php?error=database_error");
    exit();
}