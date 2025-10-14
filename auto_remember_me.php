<?php
/**
 * VERIFICAÇÃO AUTOMÁTICA DE "LEMBRE-ME"
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este arquivo deve ser incluído no início de todas as páginas protegidas
 * para verificar automaticamente se o usuário tem um token válido
 * 
 * USO: require_once 'auto_remember_me.php';
 */

// Verificar se já foi incluído
if (defined('REMEMBER_ME_CHECKED')) {
    return;
}

// Marcar como verificado
define('REMEMBER_ME_CHECKED', true);

// Incluir funções do sistema "Lembre-me"
require_once 'remember_me_functions.php';

// Verificar se o usuário já está autenticado
if (!isset($_SESSION['user_id'])) {
    // Tentar autenticar via "Lembre-me"
    if (checkRememberMe()) {
        // Usuário autenticado via cookie
        error_log("Usuário autenticado via 'Lembre-me': " . ($_SESSION['user'] ?? 'desconhecido'));
        
        // Redirecionar para a página atual para evitar problemas de cache
        if (!headers_sent()) {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}

// Se ainda não estiver autenticado, redirecionar para login
if (!isset($_SESSION['user_id'])) {
    // Salvar a página atual para redirecionamento após login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    if (!headers_sent()) {
        header('Location: login.php');
        exit();
    }
}
?>
