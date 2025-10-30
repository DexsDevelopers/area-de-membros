<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registrar.php');
    exit();
}

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token_register']) || !hash_equals($_SESSION['csrf_token_register'], $csrf)) {
    header('Location: registrar.php?error=invalid_csrf');
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm'] ?? '';

// Validações básicas
if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
    header('Location: registrar.php?error=invalid_username');
    exit();
}

if (strlen($password) < 8) {
    header('Location: registrar.php?error=password_short');
    exit();
}

if ($password !== $confirm) {
    header('Location: registrar.php?error=password_mismatch');
    exit();
}

try {
    // Verificar se usuário existe
    $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $check->execute([$username]);
    if ((int)$check->fetchColumn() > 0) {
        header('Location: registrar.php?error=username_exists');
        exit();
    }

    // Criar usuário
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
    $insert->execute([$username, $hash, 'user']);
    $userId = (int)$pdo->lastInsertId();

    // Limpar token de registro
    unset($_SESSION['csrf_token_register']);

    // Autenticar automaticamente
    $_SESSION['user_id'] = $userId;
    $_SESSION['user'] = $username;
    $_SESSION['role'] = 'user';

    header('Location: index.php');
    exit();
} catch (Exception $e) {
    error_log('Erro no registro: ' . $e->getMessage());
    header('Location: registrar.php?error=unknown');
    exit();
}


