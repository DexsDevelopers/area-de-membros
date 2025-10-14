<?php
/**
 * LOGOUT SEGURO COM REVOGAÇÃO DE TOKENS
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este arquivo processa o logout seguro, revogando tokens de "Lembre-me"
 */

session_start();

// Incluir funções do sistema "Lembre-me"
require 'remember_me_functions.php';

// Verificar se o usuário está logado
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['user'] ?? 'desconhecido';
    
    // Revogar todos os tokens do usuário
    if (revokeAllUserTokens($user_id)) {
        error_log("Todos os tokens 'Lembre-me' revogados para usuário: $username");
    }
    
    // Remover cookie "Lembre-me"
    removeRememberMeCookie();
    
    // Log da ação
    error_log("Logout seguro realizado para usuário: $username");
}

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Redirecionar para login com mensagem de logout
header('Location: login.php?logout=success');
exit();
?>
