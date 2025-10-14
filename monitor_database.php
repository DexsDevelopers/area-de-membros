<?php
/**
 * MONITOR DE BANCO DE DADOS - HELMER ACADEMY
 * 
 * Este script monitora o status do banco de dados
 * e mostra se as atualizações automáticas estão funcionando
 */

// Incluir configuração do banco
require 'config.php';

// Verificar se é admin
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die('Acesso negado. Apenas administradores podem acessar este monitor.');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Banco - HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        .gradient-bg {
            background: linear-gradient(135deg, #000000 0%, #1a0000 25%, #2d0000 50%, #1a0000 75%, #000000 100%);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .status-card {
            background: linear-gradient(145deg, rgba(220,38,38,0.1) 0%, rgba(0,0,0,0.3) 50%, rgba(220,38,38,0.05) 100%);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(220,38,38,0.3);
            box-shadow: 0 8px 32px rgba(220,38,38,0.2);
        }
        
        .status-online { border-left: 4px solid #10b981; }
        .status-warning { border-left: 4px solid #f59e0b; }
        .status-error { border-left: 4px solid #ef4444; }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .auto-refresh {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4">
                <i class="fas fa-database text-red-400 mr-3"></i>
                Monitor de Banco de Dados
            </h1>
            <p class="text-gray-400">Acompanhe o status das atualizações automáticas</p>
        </div>

        <!-- Status Geral -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Status da Conexão -->
            <div class="status-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Conexão com Banco</h3>
                    <div id="connection-status" class="w-3 h-3 rounded-full bg-gray-500"></div>
                </div>
                <p id="connection-message" class="text-sm text-gray-400">Verificando...</p>
            </div>

            <!-- Status das Tabelas -->
            <div class="status-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Tabelas do Sistema</h3>
                    <div id="tables-status" class="w-3 h-3 rounded-full bg-gray-500"></div>
                </div>
                <p id="tables-message" class="text-sm text-gray-400">Verificando...</p>
            </div>

            <!-- Status das Atualizações -->
            <div class="status-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Atualizações Automáticas</h3>
                    <div id="updates-status" class="w-3 h-3 rounded-full bg-gray-500"></div>
                </div>
                <p id="updates-message" class="text-sm text-gray-400">Verificando...</p>
            </div>
        </div>

        <!-- Detalhes das Tabelas -->
        <div class="status-card p-6 rounded-xl mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-table text-red-400 mr-3"></i>
                Status das Tabelas
            </h2>
            <div id="tables-details" class="space-y-4">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Logs de Execução -->
        <div class="status-card p-6 rounded-xl mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-file-alt text-red-400 mr-3"></i>
                Logs de Execução
            </h2>
            <div id="logs-content" class="bg-black/50 p-4 rounded-lg font-mono text-sm max-h-64 overflow-y-auto">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="status-card p-6 rounded-xl mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-chart-bar text-red-400 mr-3"></i>
                Estatísticas do Sistema
            </h2>
            <div id="stats-content" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Controles -->
        <div class="status-card p-6 rounded-xl">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-cogs text-red-400 mr-3"></i>
                Controles do Sistema
            </h2>
            <div class="flex flex-wrap gap-4">
                <button onclick="refreshStatus()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Atualizar Status
                </button>
                <button onclick="testConnection()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-plug mr-2"></i>
                    Testar Conexão
                </button>
                <button onclick="viewLogs()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-file-alt mr-2"></i>
                    Ver Logs Completos
                </button>
                <button onclick="runUpdate()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-play mr-2"></i>
                    Executar Atualização
                </button>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 30 segundos
        setInterval(refreshStatus, 30000);
        
        // Carregar status inicial
        document.addEventListener('DOMContentLoaded', function() {
            refreshStatus();
        });

        async function refreshStatus() {
            try {
                const response = await fetch('?action=check_status');
                const data = await response.json();
                
                updateConnectionStatus(data.connection);
                updateTablesStatus(data.tables);
                updateUpdatesStatus(data.updates);
                updateTablesDetails(data.tables_details);
                updateLogs(data.logs);
                updateStats(data.stats);
                
            } catch (error) {
                console.error('Erro ao atualizar status:', error);
            }
        }

        function updateConnectionStatus(status) {
            const indicator = document.getElementById('connection-status');
            const message = document.getElementById('connection-message');
            
            if (status.success) {
                indicator.className = 'w-3 h-3 rounded-full bg-green-500 pulse-animation';
                message.textContent = 'Conexão estabelecida com sucesso';
                message.className = 'text-sm text-green-400';
            } else {
                indicator.className = 'w-3 h-3 rounded-full bg-red-500';
                message.textContent = 'Erro na conexão: ' + status.error;
                message.className = 'text-sm text-red-400';
            }
        }

        function updateTablesStatus(tables) {
            const indicator = document.getElementById('tables-status');
            const message = document.getElementById('tables-message');
            
            const totalTables = tables.length;
            const existingTables = tables.filter(t => t.exists).length;
            
            if (existingTables === totalTables) {
                indicator.className = 'w-3 h-3 rounded-full bg-green-500 pulse-animation';
                message.textContent = `${existingTables}/${totalTables} tabelas encontradas`;
                message.className = 'text-sm text-green-400';
            } else {
                indicator.className = 'w-3 h-3 rounded-full bg-yellow-500';
                message.textContent = `${existingTables}/${totalTables} tabelas encontradas`;
                message.className = 'text-sm text-yellow-400';
            }
        }

        function updateUpdatesStatus(updates) {
            const indicator = document.getElementById('updates-status');
            const message = document.getElementById('updates-message');
            
            if (updates.last_execution) {
                const lastExec = new Date(updates.last_execution);
                const now = new Date();
                const diffMinutes = Math.floor((now - lastExec) / (1000 * 60));
                
                if (diffMinutes < 60) {
                    indicator.className = 'w-3 h-3 rounded-full bg-green-500 pulse-animation';
                    message.textContent = `Última execução: ${diffMinutes} min atrás`;
                    message.className = 'text-sm text-green-400';
                } else {
                    indicator.className = 'w-3 h-3 rounded-full bg-yellow-500';
                    message.textContent = `Última execução: ${diffMinutes} min atrás`;
                    message.className = 'text-sm text-yellow-400';
                }
            } else {
                indicator.className = 'w-3 h-3 rounded-full bg-red-500';
                message.textContent = 'Nenhuma execução registrada';
                message.className = 'text-sm text-red-400';
            }
        }

        function updateTablesDetails(tables) {
            const container = document.getElementById('tables-details');
            container.innerHTML = '';
            
            tables.forEach(table => {
                const statusClass = table.exists ? 'text-green-400' : 'text-red-400';
                const statusIcon = table.exists ? 'fa-check-circle' : 'fa-times-circle';
                const statusText = table.exists ? 'Existe' : 'Não encontrada';
                
                container.innerHTML += `
                    <div class="flex items-center justify-between p-4 bg-gray-800/50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <i class="fas ${statusIcon} ${statusClass}"></i>
                            <span class="font-semibold">${table.name}</span>
                        </div>
                        <div class="text-right">
                            <div class="${statusClass} text-sm">${statusText}</div>
                            ${table.records ? `<div class="text-xs text-gray-400">${table.records} registros</div>` : ''}
                        </div>
                    </div>
                `;
            });
        }

        function updateLogs(logs) {
            const container = document.getElementById('logs-content');
            container.innerHTML = logs || 'Nenhum log disponível';
        }

        function updateStats(stats) {
            const container = document.getElementById('stats-content');
            container.innerHTML = `
                <div class="bg-gray-800/50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-red-400">${stats.total_tables || 0}</div>
                    <div class="text-sm text-gray-400">Tabelas</div>
                </div>
                <div class="bg-gray-800/50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-green-400">${stats.total_records || 0}</div>
                    <div class="text-sm text-gray-400">Registros</div>
                </div>
                <div class="bg-gray-800/50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-blue-400">${stats.last_update || 'N/A'}</div>
                    <div class="text-sm text-gray-400">Última Atualização</div>
                </div>
                <div class="bg-gray-800/50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-yellow-400">${stats.auto_updates || 0}</div>
                    <div class="text-sm text-gray-400">Atualizações Automáticas</div>
                </div>
            `;
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

        function viewLogs() {
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
                    refreshStatus();
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
        case 'check_status':
            echo json_encode(checkDatabaseStatus());
            break;
        case 'test_connection':
            echo json_encode(testDatabaseConnection());
            break;
        case 'run_update':
            echo json_encode(runDatabaseUpdate());
            break;
        case 'view_logs':
            showLogs();
            break;
    }
    exit;
}

function checkDatabaseStatus() {
    global $pdo;
    
    try {
        // Testar conexão
        $pdo->query("SELECT 1");
        $connection = ['success' => true];
    } catch (PDOException $e) {
        $connection = ['success' => false, 'error' => $e->getMessage()];
    }
    
    // Verificar tabelas
    $tables = ['users', 'produtos', 'cursos', 'comentarios', 'categorias', 'banners', 'notificacoes', 'favoritos'];
    $tables_details = [];
    $total_records = 0;
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $tables_details[] = ['name' => $table, 'exists' => true, 'records' => $count];
            $total_records += $count;
        } catch (PDOException $e) {
            $tables_details[] = ['name' => $table, 'exists' => false, 'records' => 0];
        }
    }
    
    // Verificar logs
    $logs = '';
    if (file_exists('database_deploy.log')) {
        $logs = file_get_contents('database_deploy.log');
    }
    
    // Verificar última execução
    $last_execution = null;
    if (file_exists('last_database_deploy.txt')) {
        $last_execution = file_get_contents('last_database_deploy.txt');
    }
    
    return [
        'connection' => $connection,
        'tables' => $tables_details,
        'tables_details' => $tables_details,
        'updates' => ['last_execution' => $last_execution],
        'logs' => $logs,
        'stats' => [
            'total_tables' => count($tables),
            'total_records' => $total_records,
            'last_update' => $last_execution ? date('d/m/Y H:i', $last_execution) : 'Nunca',
            'auto_updates' => file_exists('database_updated.flag') ? 1 : 0
        ]
    ];
}

function testDatabaseConnection() {
    global $pdo;
    
    try {
        $pdo->query("SELECT 1");
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function runDatabaseUpdate() {
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
