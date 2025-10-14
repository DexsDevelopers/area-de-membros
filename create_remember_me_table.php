<?php
/**
 * CRIAR TABELA PARA SISTEMA "LEMBRE-ME"
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este script cria a tabela necessária para o sistema de "Lembre-me"
 */

// Incluir configuração do banco
require 'config.php';

// Verificar se é admin
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Tabela Lembre-me - HELMER ACADEMY</title>
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
                Sistema "Lembre-me"
            </h1>
            <p class="text-gray-400">Criando tabela para tokens de autenticação</p>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Status da Criação -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-database text-blue-400 mr-2"></i>
                    Status da Criação da Tabela
                </h2>
                <?php
                try {
                    // Verificar se a tabela já existe
                    $stmt = $pdo->query("SHOW TABLES LIKE 'remember_tokens'");
                    $table_exists = $stmt->fetch();
                    
                    if ($table_exists) {
                        echo '<div class="status-warning mb-4">⚠️ Tabela "remember_tokens" já existe</div>';
                        echo '<div class="code-block">Verificando estrutura atual...</div>';
                        
                        // Mostrar estrutura atual
                        $stmt = $pdo->query("DESCRIBE remember_tokens");
                        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo '<div class="code-block">';
                        echo "Estrutura atual:\n";
                        foreach ($columns as $column) {
                            echo "- {$column['Field']}: {$column['Type']} " . ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
                        }
                        echo '</div>';
                        
                    } else {
                        echo '<div class="status-ok mb-4">✅ Tabela "remember_tokens" não existe - criando...</div>';
                        
                        // Criar a tabela
                        $create_table_sql = "
                        CREATE TABLE remember_tokens (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            token_hash VARCHAR(64) NOT NULL,
                            expires_at TIMESTAMP NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            last_used_at TIMESTAMP NULL,
                            user_agent TEXT,
                            ip_address VARCHAR(45),
                            is_active BOOLEAN DEFAULT TRUE,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                            INDEX idx_token_hash (token_hash),
                            INDEX idx_user_id (user_id),
                            INDEX idx_expires_at (expires_at),
                            INDEX idx_active (is_active)
                        )";
                        
                        $pdo->exec($create_table_sql);
                        echo '<div class="status-ok mb-4">✅ Tabela "remember_tokens" criada com sucesso!</div>';
                        
                        // Mostrar estrutura criada
                        $stmt = $pdo->query("DESCRIBE remember_tokens");
                        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo '<div class="code-block">';
                        echo "Estrutura criada:\n";
                        foreach ($columns as $column) {
                            echo "- {$column['Field']}: {$column['Type']} " . ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
                        }
                        echo '</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="status-error mb-4">❌ Erro ao criar tabela: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>

            <!-- Funcionalidades da Tabela -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-cogs text-green-400 mr-2"></i>
                    Funcionalidades da Tabela
                </h2>
                <div class="code-block">
                    <strong>Campos da Tabela:</strong>
                    - id: Identificador único
                    - user_id: ID do usuário (FK para users)
                    - token_hash: Hash SHA-256 do token
                    - expires_at: Data de expiração do token
                    - created_at: Data de criação
                    - last_used_at: Último uso do token
                    - user_agent: Navegador do usuário
                    - ip_address: IP do usuário
                    - is_active: Se o token está ativo

                    <strong>Índices Criados:</strong>
                    - idx_token_hash: Busca rápida por token
                    - idx_user_id: Busca por usuário
                    - idx_expires_at: Limpeza de tokens expirados
                    - idx_active: Filtro por tokens ativos

                    <strong>Segurança:</strong>
                    - Tokens são armazenados como hash SHA-256
                    - Expiração automática configurável
                    - Rastreamento de uso e localização
                    - Limpeza automática de tokens expirados
                </div>
            </div>

            <!-- Próximos Passos -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-list-check text-yellow-400 mr-2"></i>
                    Próximos Passos
                </h2>
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-check-circle text-green-400"></i>
                        <span>✅ Tabela criada com sucesso</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-arrow-right text-blue-400"></i>
                        <span>➡️ Implementar funções de token</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-arrow-right text-blue-400"></i>
                        <span>➡️ Atualizar processa_login.php</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-arrow-right text-blue-400"></i>
                        <span>➡️ Adicionar verificação automática</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-arrow-right text-blue-400"></i>
                        <span>➡️ Implementar logout seguro</span>
                    </div>
                </div>
            </div>

            <!-- Botão para Continuar -->
            <div class="text-center">
                <a href="remember_me_functions.php" class="bg-red-600 hover:bg-red-700 text-white px-8 py-4 rounded-xl font-bold transition-colors inline-flex items-center">
                    <i class="fas fa-arrow-right mr-2"></i>
                    Continuar para Implementação
                </a>
            </div>

        </div>
    </div>
</body>
</html>
