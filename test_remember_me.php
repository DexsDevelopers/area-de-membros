<?php
/**
 * TESTE DO SISTEMA "LEMBRE-ME"
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este arquivo testa todas as funcionalidades do sistema "Lembre-me"
 */

session_start();

// Incluir funções do sistema "Lembre-me"
require 'remember_me_functions.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Sistema "Lembre-me" - HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #000000 0%, #1a0000 25%, #2d0000 50%, #1a0000 75%, #000000 100%);
            color: #ffffff; 
            min-height: 100vh;
        }
        .status-ok { color: #10b981; }
        .status-error { color: #ef4444; }
        .status-warning { color: #f59e0b; }
        .code-block {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4">
                <i class="fas fa-memory text-red-400 mr-3"></i>
                Teste do Sistema "Lembre-me"
            </h1>
            <p class="text-gray-400">Verificando todas as funcionalidades</p>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Status da Tabela -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-database text-blue-400 mr-2"></i>
                    Status da Tabela
                </h2>
                <?php
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE 'remember_tokens'");
                    $table_exists = $stmt->fetch();
                    
                    if ($table_exists) {
                        echo '<div class="status-ok mb-4">✅ Tabela "remember_tokens" existe</div>';
                        
                        // Contar registros
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM remember_tokens");
                        $total = $stmt->fetch()['total'];
                        echo '<div class="text-gray-300">Total de tokens: ' . $total . '</div>';
                        
                        // Tokens ativos
                        $stmt = $pdo->query("SELECT COUNT(*) as ativos FROM remember_tokens WHERE is_active = 1 AND expires_at > NOW()");
                        $ativos = $stmt->fetch()['ativos'];
                        echo '<div class="text-gray-300">Tokens ativos: ' . $ativos . '</div>';
                        
                    } else {
                        echo '<div class="status-error mb-4">❌ Tabela "remember_tokens" não existe</div>';
                        echo '<div class="text-gray-300">Execute: <a href="create_remember_me_table.php" class="text-blue-400 hover:underline">create_remember_me_table.php</a></div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="status-error mb-4">❌ Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>

            <!-- Teste de Funções -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-cogs text-green-400 mr-2"></i>
                    Teste de Funções
                </h2>
                <?php
                echo '<div class="space-y-4">';
                
                // Teste 1: Gerar token
                echo '<div class="flex items-center space-x-3">';
                $test_token = generateRememberToken();
                if ($test_token && strlen($test_token) === 64) {
                    echo '<i class="fas fa-check-circle text-green-400"></i>';
                    echo '<span>✅ generateRememberToken() - OK</span>';
                } else {
                    echo '<i class="fas fa-times-circle text-red-400"></i>';
                    echo '<span>❌ generateRememberToken() - ERRO</span>';
                }
                echo '</div>';
                
                // Teste 2: Hash do token
                echo '<div class="flex items-center space-x-3">';
                $test_hash = hash('sha256', $test_token);
                if ($test_hash && strlen($test_hash) === 64) {
                    echo '<i class="fas fa-check-circle text-green-400"></i>';
                    echo '<span>✅ Hash SHA-256 - OK</span>';
                } else {
                    echo '<i class="fas fa-times-circle text-red-400"></i>';
                    echo '<span>❌ Hash SHA-256 - ERRO</span>';
                }
                echo '</div>';
                
                // Teste 3: Limpeza de tokens expirados
                echo '<div class="flex items-center space-x-3">';
                $cleaned = cleanExpiredTokens();
                if ($cleaned !== false) {
                    echo '<i class="fas fa-check-circle text-green-400"></i>';
                    echo '<span>✅ cleanExpiredTokens() - OK (removidos: ' . $cleaned . ')</span>';
                } else {
                    echo '<i class="fas fa-times-circle text-red-400"></i>';
                    echo '<span>❌ cleanExpiredTokens() - ERRO</span>';
                }
                echo '</div>';
                
                echo '</div>';
                ?>
            </div>

            <!-- Teste de Cookie -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-cookie-bite text-yellow-400 mr-2"></i>
                    Teste de Cookie
                </h2>
                <?php
                echo '<div class="space-y-4">';
                
                // Verificar se existe cookie
                if (isset($_COOKIE['remember_me'])) {
                    echo '<div class="flex items-center space-x-3">';
                    echo '<i class="fas fa-check-circle text-green-400"></i>';
                    echo '<span>✅ Cookie "remember_me" encontrado</span>';
                    echo '</div>';
                    
                    // Testar validação do token
                    $cookie_token = $_COOKIE['remember_me'];
                    $token_data = validateRememberToken($cookie_token);
                    
                    if ($token_data) {
                        echo '<div class="flex items-center space-x-3">';
                        echo '<i class="fas fa-check-circle text-green-400"></i>';
                        echo '<span>✅ Token válido - Usuário: ' . htmlspecialchars($token_data['username']) . '</span>';
                        echo '</div>';
                    } else {
                        echo '<div class="flex items-center space-x-3">';
                        echo '<i class="fas fa-exclamation-triangle text-yellow-400"></i>';
                        echo '<span>⚠️ Token inválido ou expirado</span>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="flex items-center space-x-3">';
                    echo '<i class="fas fa-info-circle text-blue-400"></i>';
                    echo '<span>ℹ️ Nenhum cookie "remember_me" encontrado</span>';
                    echo '</div>';
                }
                
                echo '</div>';
                ?>
            </div>

            <!-- Status da Sessão -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-user text-purple-400 mr-2"></i>
                    Status da Sessão
                </h2>
                <?php
                echo '<div class="space-y-2">';
                
                if (isset($_SESSION['user_id'])) {
                    echo '<div class="flex items-center space-x-3">';
                    echo '<i class="fas fa-check-circle text-green-400"></i>';
                    echo '<span>✅ Usuário logado: ' . htmlspecialchars($_SESSION['user'] ?? 'desconhecido') . '</span>';
                    echo '</div>';
                    
                    echo '<div class="flex items-center space-x-3">';
                    echo '<i class="fas fa-info-circle text-blue-400"></i>';
                    echo '<span>ℹ️ ID: ' . $_SESSION['user_id'] . '</span>';
                    echo '</div>';
                    
                    echo '<div class="flex items-center space-x-3">';
                    echo '<i class="fas fa-info-circle text-blue-400"></i>';
                    echo '<span>ℹ️ Role: ' . htmlspecialchars($_SESSION['role'] ?? 'desconhecida') . '</span>';
                    echo '</div>';
                } else {
                    echo '<div class="flex items-center space-x-3">';
                    echo '<i class="fas fa-times-circle text-red-400"></i>';
                    echo '<span>❌ Nenhum usuário logado</span>';
                    echo '</div>';
                }
                
                echo '</div>';
                ?>
            </div>

            <!-- Links de Teste -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-link text-cyan-400 mr-2"></i>
                    Links de Teste
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg text-center transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Página de Login
                    </a>
                    
                    <a href="manage_remember_me.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg text-center transition-colors">
                        <i class="fas fa-cog mr-2"></i>
                        Gerenciar Tokens
                    </a>
                    
                    <a href="create_remember_me_table.php" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg text-center transition-colors">
                        <i class="fas fa-database mr-2"></i>
                        Criar Tabela
                    </a>
                    
                    <a href="logout_secure.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg text-center transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout Seguro
                    </a>
                </div>
            </div>

            <!-- Informações Técnicas -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-info-circle text-cyan-400 mr-2"></i>
                    Informações Técnicas
                </h2>
                <div class="code-block">
                    <strong>Funcionalidades Implementadas:</strong>
                    ✅ Tabela remember_tokens criada
                    ✅ Funções de gerenciamento de tokens
                    ✅ Sistema de cookies seguros
                    ✅ Verificação automática de tokens
                    ✅ Logout seguro com revogação
                    ✅ Interface de gerenciamento
                    ✅ Limpeza automática de tokens expirados

                    <strong>Segurança:</strong>
                    - Tokens armazenados como hash SHA-256
                    - Cookies HttpOnly e Secure
                    - Expiração automática em 30 dias
                    - Rastreamento de IP e User-Agent
                    - Revogação individual e em massa

                    <strong>Como Usar:</strong>
                    1. Marque "Lembre-me" no login
                    2. Faça login normalmente
                    3. Token será criado automaticamente
                    4. Acesse manage_remember_me.php para gerenciar
                    5. Use logout_secure.php para logout seguro
                </div>
            </div>

        </div>
    </div>
</body>
</html>
