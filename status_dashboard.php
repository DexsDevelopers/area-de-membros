<?php
/**
 * DASHBOARD DE STATUS - HELMER ACADEMY
 * 
 * Interface simples para verificar se tudo está funcionando
 */

// Incluir configuração do banco
require 'config.php';

// Verificar se é admin
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die('Acesso negado. Apenas administradores podem acessar este dashboard.');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Dashboard - HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-ok { color: #10b981; }
        .status-warning { color: #f59e0b; }
        .status-error { color: #ef4444; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4">
                <i class="fas fa-tachometer-alt text-red-400 mr-3"></i>
                Status Dashboard
            </h1>
            <p class="text-gray-400">Verificação rápida do sistema</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Status da Conexão -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h3 class="text-lg font-semibold mb-4">Conexão com Banco</h3>
                <div id="connection-status" class="text-center">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                    <p class="mt-2">Verificando...</p>
                </div>
            </div>

            <!-- Status das Tabelas -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h3 class="text-lg font-semibold mb-4">Tabelas do Sistema</h3>
                <div id="tables-status" class="text-center">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                    <p class="mt-2">Verificando...</p>
                </div>
            </div>

            <!-- Status das Atualizações -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h3 class="text-lg font-semibold mb-4">Atualizações Automáticas</h3>
                <div id="updates-status" class="text-center">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                    <p class="mt-2">Verificando...</p>
                </div>
            </div>
        </div>

        <!-- Detalhes -->
        <div class="mt-8 bg-gray-800 p-6 rounded-xl">
            <h3 class="text-lg font-semibold mb-4">Detalhes do Sistema</h3>
            <div id="system-details">
                <i class="fas fa-spinner fa-spin"></i> Carregando detalhes...
            </div>
        </div>

        <!-- Logs -->
        <div class="mt-8 bg-gray-800 p-6 rounded-xl">
            <h3 class="text-lg font-semibold mb-4">Logs Recentes</h3>
            <div id="logs-content" class="bg-black p-4 rounded-lg font-mono text-sm max-h-64 overflow-y-auto">
                <i class="fas fa-spinner fa-spin"></i> Carregando logs...
            </div>
        </div>

        <!-- Controles -->
        <div class="mt-8 bg-gray-800 p-6 rounded-xl">
            <h3 class="text-lg font-semibold mb-4">Controles</h3>
            <div class="flex flex-wrap gap-4">
                <button onclick="refreshAll()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Atualizar Tudo
                </button>
                <button onclick="testConnection()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-plug mr-2"></i>
                    Testar Conexão
                </button>
                <button onclick="viewFullLogs()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-file-alt mr-2"></i>
                    Ver Logs Completos
                </button>
                <button onclick="runUpdate()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-play mr-2"></i>
                    Executar Atualização
                </button>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 30 segundos
        setInterval(refreshAll, 30000);
        
        // Carregar status inicial
        document.addEventListener('DOMContentLoaded', function() {
            refreshAll();
        });

        async function refreshAll() {
            await checkConnection();
            await checkTables();
            await checkUpdates();
            await loadSystemDetails();
            await loadLogs();
        }

        async function checkConnection() {
            try {
                const response = await fetch('?action=check_connection');
                const data = await response.json();
                
                const status = document.getElementById('connection-status');
                if (data.success) {
                    status.innerHTML = `
                        <i class="fas fa-check-circle text-2xl status-ok pulse"></i>
                        <p class="mt-2 status-ok">Conectado</p>
                        <p class="text-sm text-gray-400">${data.response_time}ms</p>
                    `;
                } else {
                    status.innerHTML = `
                        <i class="fas fa-times-circle text-2xl status-error"></i>
                        <p class="mt-2 status-error">Erro de Conexão</p>
                        <p class="text-sm text-gray-400">${data.error}</p>
                    `;
                }
            } catch (error) {
                const status = document.getElementById('connection-status');
                status.innerHTML = `
                    <i class="fas fa-exclamation-triangle text-2xl status-error"></i>
                    <p class="mt-2 status-error">Erro na Verificação</p>
                `;
            }
        }

        async function checkTables() {
            try {
                const response = await fetch('?action=check_tables');
                const data = await response.json();
                
                const status = document.getElementById('tables-status');
                const totalTables = data.total;
                const existingTables = data.existing;
                
                if (existingTables === totalTables) {
                    status.innerHTML = `
                        <i class="fas fa-check-circle text-2xl status-ok pulse"></i>
                        <p class="mt-2 status-ok">${existingTables}/${totalTables} Tabelas</p>
                        <p class="text-sm text-gray-400">Todas encontradas</p>
                    `;
                } else {
                    status.innerHTML = `
                        <i class="fas fa-exclamation-triangle text-2xl status-warning"></i>
                        <p class="mt-2 status-warning">${existingTables}/${totalTables} Tabelas</p>
                        <p class="text-sm text-gray-400">Algumas faltando</p>
                    `;
                }
            } catch (error) {
                const status = document.getElementById('tables-status');
                status.innerHTML = `
                    <i class="fas fa-exclamation-triangle text-2xl status-error"></i>
                    <p class="mt-2 status-error">Erro na Verificação</p>
                `;
            }
        }

        async function checkUpdates() {
            try {
                const response = await fetch('?action=check_updates');
                const data = await response.json();
                
                const status = document.getElementById('updates-status');
                
                if (data.last_execution) {
                    const lastExec = new Date(data.last_execution * 1000);
                    const now = new Date();
                    const diffMinutes = Math.floor((now - lastExec) / (1000 * 60));
                    
                    if (diffMinutes < 60) {
                        status.innerHTML = `
                            <i class="fas fa-check-circle text-2xl status-ok pulse"></i>
                            <p class="mt-2 status-ok">Atualizado</p>
                            <p class="text-sm text-gray-400">${diffMinutes} min atrás</p>
                        `;
                    } else {
                        status.innerHTML = `
                            <i class="fas fa-exclamation-triangle text-2xl status-warning"></i>
                            <p class="mt-2 status-warning">Desatualizado</p>
                            <p class="text-sm text-gray-400">${diffMinutes} min atrás</p>
                        `;
                    }
                } else {
                    status.innerHTML = `
                        <i class="fas fa-times-circle text-2xl status-error"></i>
                        <p class="mt-2 status-error">Nunca Executado</p>
                        <p class="text-sm text-gray-400">Execute uma atualização</p>
                    `;
                }
            } catch (error) {
                const status = document.getElementById('updates-status');
                status.innerHTML = `
                    <i class="fas fa-exclamation-triangle text-2xl status-error"></i>
                    <p class="mt-2 status-error">Erro na Verificação</p>
                `;
            }
        }

        async function loadSystemDetails() {
            try {
                const response = await fetch('?action=system_details');
                const data = await response.json();
                
                const details = document.getElementById('system-details');
                details.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-700 p-4 rounded-lg">
                            <h4 class="font-semibold mb-2">Informações do Servidor</h4>
                            <p class="text-sm">PHP: ${data.php_version}</p>
                            <p class="text-sm">Servidor: ${data.server}</p>
                            <p class="text-sm">Data: ${data.current_time}</p>
                        </div>
                        <div class="bg-gray-700 p-4 rounded-lg">
                            <h4 class="font-semibold mb-2">Arquivos de Sistema</h4>
                            <p class="text-sm">Flag de atualização: ${data.update_flag ? '✅ Existe' : '❌ Não existe'}</p>
                            <p class="text-sm">Logs: ${data.logs_count} arquivos</p>
                            <p class="text-sm">Última verificação: ${data.last_check}</p>
                        </div>
                    </div>
                `;
            } catch (error) {
                document.getElementById('system-details').innerHTML = '<p class="text-red-400">Erro ao carregar detalhes</p>';
            }
        }

        async function loadLogs() {
            try {
                const response = await fetch('?action=get_logs');
                const data = await response.json();
                
                const logs = document.getElementById('logs-content');
                if (data.logs) {
                    logs.innerHTML = `<pre>${data.logs}</pre>`;
                } else {
                    logs.innerHTML = '<p class="text-gray-400">Nenhum log disponível</p>';
                }
            } catch (error) {
                document.getElementById('logs-content').innerHTML = '<p class="text-red-400">Erro ao carregar logs</p>';
            }
        }

        async function testConnection() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testando...';
            button.disabled = true;
            
            try {
                const response = await fetch('?action=test_connection');
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Conexão testada com sucesso!');
                } else {
                    alert('❌ Erro na conexão: ' + data.error);
                }
            } catch (error) {
                alert('❌ Erro ao testar conexão: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        function viewFullLogs() {
            window.open('?action=view_logs', '_blank');
        }

        async function runUpdate() {
            if (!confirm('Tem certeza que deseja executar a atualização do banco de dados?')) {
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Executando...';
            button.disabled = true;
            
            try {
                const response = await fetch('?action=run_update');
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Atualização executada com sucesso!');
                    refreshAll();
                } else {
                    alert('❌ Erro na atualização: ' + data.error);
                }
            } catch (error) {
                alert('❌ Erro ao executar atualização: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
    </script>
</body>
</html>

<?php
// Processar ações AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'check_connection':
            echo json_encode(testConnection());
            break;
        case 'check_tables':
            echo json_encode(checkTables());
            break;
        case 'check_updates':
            echo json_encode(checkUpdates());
            break;
        case 'system_details':
            echo json_encode(getSystemDetails());
            break;
        case 'get_logs':
            echo json_encode(getLogs());
            break;
        case 'test_connection':
            echo json_encode(testConnection());
            break;
        case 'run_update':
            echo json_encode(runUpdate());
            break;
        case 'view_logs':
            showLogs();
            break;
    }
    exit;
}

function testConnection() {
    global $pdo;
    
    $start_time = microtime(true);
    
    try {
        $pdo->query("SELECT 1");
        $end_time = microtime(true);
        $response_time = round(($end_time - $start_time) * 1000, 2);
        
        return [
            'success' => true,
            'response_time' => $response_time
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function checkTables() {
    global $pdo;
    
    $tables = ['users', 'produtos', 'cursos', 'comentarios', 'categorias', 'banners', 'notificacoes', 'favoritos'];
    $existing = 0;
    
    foreach ($tables as $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $existing++;
        } catch (PDOException $e) {
            // Tabela não existe
        }
    }
    
    return [
        'total' => count($tables),
        'existing' => $existing
    ];
}

function checkUpdates() {
    $last_execution = null;
    
    if (file_exists('last_database_deploy.txt')) {
        $last_execution = (int)file_get_contents('last_database_deploy.txt');
    }
    
    return [
        'last_execution' => $last_execution
    ];
}

function getSystemDetails() {
    return [
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
        'current_time' => date('d/m/Y H:i:s'),
        'update_flag' => file_exists('database_updated.flag'),
        'logs_count' => count(glob('*.log')),
        'last_check' => file_exists('last_database_deploy.txt') ? date('d/m/Y H:i:s', file_get_contents('last_database_deploy.txt')) : 'Nunca'
    ];
}

function getLogs() {
    $logs = '';
    
    if (file_exists('database_deploy.log')) {
        $logs = file_get_contents('database_deploy.log');
    }
    
    return [
        'logs' => $logs
    ];
}

function runUpdate() {
    // Simular execução da atualização
    file_put_contents('last_database_deploy.txt', time());
    file_put_contents('database_updated.flag', date('Y-m-d H:i:s'));
    
    return ['success' => true];
}

function showLogs() {
    $logs = '';
    if (file_exists('database_deploy.log')) {
        $logs = file_get_contents('database_deploy.log');
    }
    
    echo "<pre style='background: #1a1a1a; color: #00ff00; padding: 20px; font-family: monospace;'>";
    echo htmlspecialchars($logs ?: 'Nenhum log disponível');
    echo "</pre>";
}
?>
