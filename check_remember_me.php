<?php
/**
 * VERIFICAÇÃO AUTOMÁTICA DE "LEMBRE-ME"
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este arquivo deve ser incluído no início de todas as páginas protegidas
 * para verificar automaticamente se o usuário tem um token válido
 */

// Incluir funções do sistema "Lembre-me"
require_once 'remember_me_functions.php';

// Verificar se o usuário já está autenticado
if (!isset($_SESSION['user_id'])) {
    // Tentar autenticar via "Lembre-me"
    if (checkRememberMe()) {
        // Usuário autenticado via cookie
        error_log("Usuário autenticado via 'Lembre-me': " . ($_SESSION['user'] ?? 'desconhecido'));
    }
}

// Se ainda não estiver autenticado, redirecionar para login
if (!isset($_SESSION['user_id'])) {
    // Salvar a página atual para redirecionamento após login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    header('Location: login.php');
    exit();
}
?>
