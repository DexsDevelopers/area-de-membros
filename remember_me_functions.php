<?php
/**
 * FUNÇÕES DO SISTEMA "LEMBRE-ME"
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este arquivo contém todas as funções necessárias para o sistema de "Lembre-me"
 */

// Incluir configuração do banco
require 'config.php';

/**
 * Gerar um token seguro para "Lembre-me"
 */
function generateRememberToken() {
    return bin2hex(random_bytes(32)); // 64 caracteres hexadecimais
}

/**
 * Criar um token de "Lembre-me" para um usuário
 */
function createRememberToken($user_id, $user_agent = '', $ip_address = '') {
    global $pdo;
    
    try {
        // Gerar token seguro
        $token = generateRememberToken();
        $token_hash = hash('sha256', $token);
        
        // Definir expiração (30 dias)
        $expires_at = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
        
        // Inserir token no banco
        $stmt = $pdo->prepare("
            INSERT INTO remember_tokens (user_id, token_hash, expires_at, user_agent, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $token_hash,
            $expires_at,
            $user_agent,
            $ip_address
        ]);
        
        // Retornar o token (não o hash)
        return $token;
        
    } catch (Exception $e) {
        error_log("Erro ao criar token de lembre-me: " . $e->getMessage());
        return false;
    }
}

/**
 * Verificar se um token de "Lembre-me" é válido
 */
function validateRememberToken($token) {
    global $pdo;
    
    try {
        $token_hash = hash('sha256', $token);
        
        // Buscar token no banco
        $stmt = $pdo->prepare("
            SELECT rt.*, u.id, u.username, u.role 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.token_hash = ? 
            AND rt.expires_at > NOW() 
            AND rt.is_active = 1
        ");
        
        $stmt->execute([$token_hash]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($token_data) {
            // Atualizar último uso
            $stmt = $pdo->prepare("
                UPDATE remember_tokens 
                SET last_used_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$token_data['id']]);
            
            return $token_data;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Erro ao validar token de lembre-me: " . $e->getMessage());
        return false;
    }
}

/**
 * Revogar um token específico
 */
function revokeRememberToken($token) {
    global $pdo;
    
    try {
        $token_hash = hash('sha256', $token);
        
        $stmt = $pdo->prepare("
            UPDATE remember_tokens 
            SET is_active = 0 
            WHERE token_hash = ?
        ");
        
        return $stmt->execute([$token_hash]);
        
    } catch (Exception $e) {
        error_log("Erro ao revogar token de lembre-me: " . $e->getMessage());
        return false;
    }
}

/**
 * Revogar todos os tokens de um usuário
 */
function revokeAllUserTokens($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE remember_tokens 
            SET is_active = 0 
            WHERE user_id = ?
        ");
        
        return $stmt->execute([$user_id]);
        
    } catch (Exception $e) {
        error_log("Erro ao revogar tokens do usuário: " . $e->getMessage());
        return false;
    }
}

/**
 * Limpar tokens expirados
 */
function cleanExpiredTokens() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM remember_tokens 
            WHERE expires_at < NOW() OR is_active = 0
        ");
        
        $result = $stmt->execute();
        $deleted = $stmt->rowCount();
        
        error_log("Tokens expirados removidos: $deleted");
        return $deleted;
        
    } catch (Exception $e) {
        error_log("Erro ao limpar tokens expirados: " . $e->getMessage());
        return false;
    }
}

/**
 * Verificar e autenticar usuário via cookie "Lembre-me"
 */
function checkRememberMe() {
    if (isset($_SESSION['user_id'])) {
        return true; // Já autenticado
    }
    
    if (!isset($_COOKIE['remember_me'])) {
        return false; // Sem cookie
    }
    
    $token = $_COOKIE['remember_me'];
    $token_data = validateRememberToken($token);
    
    if ($token_data) {
        // Autenticar usuário
        $_SESSION['user_id'] = $token_data['user_id'];
        $_SESSION['user'] = $token_data['username'];
        $_SESSION['role'] = $token_data['role'];
        
        // Regenerar ID da sessão por segurança
        session_regenerate_id(true);
        
        return true;
    } else {
        // Token inválido - remover cookie
        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
        return false;
    }
}

/**
 * Definir cookie "Lembre-me"
 */
function setRememberMeCookie($token, $expires_days = 30) {
    $expires = time() + ($expires_days * 24 * 60 * 60);
    
    return setcookie(
        'remember_me',
        $token,
        $expires,
        '/',
        '',
        true,  // HTTPS only
        true   // HttpOnly
    );
}

/**
 * Remover cookie "Lembre-me"
 */
function removeRememberMeCookie() {
    return setcookie('remember_me', '', time() - 3600, '/', '', true, true);
}

/**
 * Obter informações de tokens de um usuário
 */
function getUserRememberTokens($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, created_at, last_used_at, user_agent, ip_address, expires_at
            FROM remember_tokens 
            WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
            ORDER BY last_used_at DESC
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erro ao buscar tokens do usuário: " . $e->getMessage());
        return [];
    }
}

/**
 * Revogar token específico por ID
 */
function revokeTokenById($token_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE remember_tokens 
            SET is_active = 0 
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$token_id, $user_id]);
        
    } catch (Exception $e) {
        error_log("Erro ao revogar token por ID: " . $e->getMessage());
        return false;
    }
}

// Executar limpeza automática de tokens expirados (5% de chance)
if (rand(1, 100) <= 5) {
    cleanExpiredTokens();
}
?>
