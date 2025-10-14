<?php
/**
 * SCRIPT DE DIAGNÓSTICO E CORREÇÃO DO BANCO DE DADOS
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este script identifica e corrige problemas no sistema
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
    <title>Correção de Problemas - HELMER ACADEMY</title>
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
        
        .status-ok { color: #10b981; }
        .status-warning { color: #f59e0b; }
        .status-error { color: #ef4444; }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .fix-button {
            background: linear-gradient(135deg, #dc2626, #ef4444, #f97316);
            background-size: 200% 200%;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
            transition: all 0.3s ease;
            animation: buttonPulse 2s ease-in-out infinite;
        }
        
        .fix-button:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.6);
        }
        
        @keyframes buttonPulse {
            0%, 100% { 
                background-position: 0% 50%; 
                box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4); 
            }
            50% { 
                background-position: 100% 50%; 
                box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6); 
            }
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4">
                <i class="fas fa-tools text-red-400 mr-3"></i>
                Correção de Problemas do Sistema
            </h1>
            <p class="text-gray-400">Diagnóstico e correção automática de erros</p>
        </div>

        <!-- Status dos Problemas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Conexão -->
            <div class="status-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Conexão</h3>
                    <div id="connection-indicator" class="w-3 h-3 rounded-full bg-gray-500"></div>
                </div>
                <p id="connection-message" class="text-sm text-gray-400">Verificando...</p>
            </div>

            <!-- Tabelas -->
            <div class="status-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Tabelas</h3>
                    <div id="tables-indicator" class="w-3 h-3 rounded-full bg-gray-500"></div>
                </div>
                <p id="tables-message" class="text-sm text-gray-400">Verificando...</p>
            </div>

            <!-- Arquivos -->
            <div class="status-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Arquivos</h3>
                    <div id="files-indicator" class="w-3 h-3 rounded-full bg-gray-500"></div>
                </div>
                <p id="files-message" class="text-sm text-gray-400">Verificando...</p>
            </div>

            <!-- Permissões -->
            <div class="status-card p-6 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Permissões</h3>
                    <div id="permissions-indicator" class="w-3 h-3 rounded-full bg-gray-500"></div>
                </div>
                <p id="permissions-message" class="text-sm text-gray-400">Verificando...</p>
            </div>
        </div>

        <!-- Diagnóstico Detalhado -->
        <div class="status-card p-6 rounded-xl mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-stethoscope text-red-400 mr-3"></i>
                Diagnóstico Detalhado
            </h2>
            <div id="diagnosis-content">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-2xl mb-4"></i>
                    <p>Executando diagnóstico completo...</p>
                </div>
            </div>
        </div>

        <!-- Correções Automáticas -->
        <div class="status-card p-6 rounded-xl mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-magic text-red-400 mr-3"></i>
                Correções Automáticas
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button onclick="fixConnection()" class="fix-button text-white font-bold py-4 px-6 rounded-xl transition-all duration-300">
                    <i class="fas fa-plug mr-2"></i>
                    Corrigir Conexão
                </button>
                <button onclick="fixTables()" class="fix-button text-white font-bold py-4 px-6 rounded-xl transition-all duration-300">
                    <i class="fas fa-table mr-2"></i>
                    Corrigir Tabelas
                </button>
                <button onclick="fixFiles()" class="fix-button text-white font-bold py-4 px-6 rounded-xl transition-all duration-300">
                    <i class="fas fa-file mr-2"></i>
                    Corrigir Arquivos
                </button>
                <button onclick="fixAll()" class="fix-button text-white font-bold py-4 px-6 rounded-xl transition-all duration-300">
                    <i class="fas fa-wrench mr-2"></i>
                    Corrigir Tudo
                </button>
            </div>
        </div>

        <!-- Logs de Correção -->
        <div class="status-card p-6 rounded-xl">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-file-alt text-red-400 mr-3"></i>
                Logs de Correção
            </h2>
            <div id="logs-content" class="bg-black/50 p-4 rounded-lg font-mono text-sm max-h-64 overflow-y-auto">
                <div class="text-center text-gray-400">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Aguardando correções...
                </div>
            </div>
        </div>
    </div>

    <script>
        // Executar diagnóstico inicial
        document.addEventListener('DOMContentLoaded', function() {
            runDiagnosis();
        });

        async function runDiagnosis() {
            try {
                const response = await fetch('?action=diagnosis');
                
                // Verificar se a resposta é válida
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Verificar o tipo de conteúdo
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text);
                    throw new Error('Resposta do servidor não é JSON válido');
                }
                
                const data = await response.json();
                
                updateIndicators(data);
                updateDiagnosis(data);
                updateLogs(data.logs);
                
            } catch (error) {
                console.error('Erro no diagnóstico:', error);
                document.getElementById('diagnosis-content').innerHTML = `
                    <div class="bg-red-800/50 p-4 rounded-lg">
                        <p class="text-red-400">❌ Erro no diagnóstico: ${error.message}</p>
                        <p class="text-sm text-gray-400 mt-2">Verifique se o servidor está funcionando corretamente</p>
                    </div>
                `;
                
                // Mostrar indicadores de erro
                document.getElementById('connection-indicator').className = 'w-3 h-3 rounded-full bg-red-500';
                document.getElementById('connection-message').textContent = 'Erro na verificação';
                document.getElementById('connection-message').className = 'text-sm text-red-400';
                
                document.getElementById('tables-indicator').className = 'w-3 h-3 rounded-full bg-red-500';
                document.getElementById('tables-message').textContent = 'Erro na verificação';
                document.getElementById('tables-message').className = 'text-sm text-red-400';
                
                document.getElementById('files-indicator').className = 'w-3 h-3 rounded-full bg-red-500';
                document.getElementById('files-message').textContent = 'Erro na verificação';
                document.getElementById('files-message').className = 'text-sm text-red-400';
                
                document.getElementById('permissions-indicator').className = 'w-3 h-3 rounded-full bg-red-500';
                document.getElementById('permissions-message').textContent = 'Erro na verificação';
                document.getElementById('permissions-message').className = 'text-sm text-red-400';
            }
        }

        function updateIndicators(data) {
            // Conexão
            const connIndicator = document.getElementById('connection-indicator');
            const connMessage = document.getElementById('connection-message');
            
            if (data.connection.success) {
                connIndicator.className = 'w-3 h-3 rounded-full bg-green-500 pulse-animation';
                connMessage.textContent = 'Conexão OK';
                connMessage.className = 'text-sm text-green-400';
            } else {
                connIndicator.className = 'w-3 h-3 rounded-full bg-red-500';
                connMessage.textContent = 'Erro de conexão';
                connMessage.className = 'text-sm text-red-400';
            }

            // Tabelas
            const tablesIndicator = document.getElementById('tables-indicator');
            const tablesMessage = document.getElementById('tables-message');
            
            if (data.tables.all_exist) {
                tablesIndicator.className = 'w-3 h-3 rounded-full bg-green-500 pulse-animation';
                tablesMessage.textContent = `${data.tables.existing}/${data.tables.total} tabelas`;
                tablesMessage.className = 'text-sm text-green-400';
            } else {
                tablesIndicator.className = 'w-3 h-3 rounded-full bg-yellow-500';
                tablesMessage.textContent = `${data.tables.existing}/${data.tables.total} tabelas`;
                tablesMessage.className = 'text-sm text-yellow-400';
            }

            // Arquivos
            const filesIndicator = document.getElementById('files-indicator');
            const filesMessage = document.getElementById('files-message');
            
            if (data.files.all_exist) {
                filesIndicator.className = 'w-3 h-3 rounded-full bg-green-500 pulse-animation';
                filesMessage.textContent = 'Arquivos OK';
                filesMessage.className = 'text-sm text-green-400';
            } else {
                filesIndicator.className = 'w-3 h-3 rounded-full bg-yellow-500';
                filesMessage.textContent = 'Arquivos faltando';
                filesMessage.className = 'text-sm text-yellow-400';
            }

            // Permissões
            const permIndicator = document.getElementById('permissions-indicator');
            const permMessage = document.getElementById('permissions-message');
            
            if (data.permissions.all_ok) {
                permIndicator.className = 'w-3 h-3 rounded-full bg-green-500 pulse-animation';
                permMessage.textContent = 'Permissões OK';
                permMessage.className = 'text-sm text-green-400';
            } else {
                permIndicator.className = 'w-3 h-3 rounded-full bg-red-500';
                permMessage.textContent = 'Problemas de permissão';
                permMessage.className = 'text-sm text-red-400';
            }
        }

        function updateDiagnosis(data) {
            const container = document.getElementById('diagnosis-content');
            
            let html = '<div class="space-y-4">';
            
            // Conexão
            html += `
                <div class="bg-gray-800/50 p-4 rounded-lg">
                    <h3 class="font-semibold mb-2 flex items-center">
                        <i class="fas fa-plug mr-2 ${data.connection.success ? 'text-green-400' : 'text-red-400'}"></i>
                        Conexão com Banco de Dados
                    </h3>
                    <p class="text-sm ${data.connection.success ? 'text-green-400' : 'text-red-400'}">
                        ${data.connection.success ? '✅ Conectado com sucesso' : '❌ ' + data.connection.error}
                    </p>
                    ${data.connection.response_time ? `<p class="text-xs text-gray-400">Tempo de resposta: ${data.connection.response_time}ms</p>` : ''}
                </div>
            `;
            
            // Tabelas
            html += `
                <div class="bg-gray-800/50 p-4 rounded-lg">
                    <h3 class="font-semibold mb-2 flex items-center">
                        <i class="fas fa-table mr-2 ${data.tables.all_exist ? 'text-green-400' : 'text-yellow-400'}"></i>
                        Tabelas do Sistema (${data.tables.existing}/${data.tables.total})
                    </h3>
                    <div class="space-y-2">
                        ${data.tables.details.map(table => `
                            <div class="flex items-center justify-between text-sm">
                                <span>${table.name}</span>
                                <span class="${table.exists ? 'text-green-400' : 'text-red-400'}">
                                    ${table.exists ? '✅ Existe' : '❌ Não encontrada'}
                                    ${table.records ? ` (${table.records} registros)` : ''}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            // Arquivos
            html += `
                <div class="bg-gray-800/50 p-4 rounded-lg">
                    <h3 class="font-semibold mb-2 flex items-center">
                        <i class="fas fa-file mr-2 ${data.files.all_exist ? 'text-green-400' : 'text-yellow-400'}"></i>
                        Arquivos do Sistema
                    </h3>
                    <div class="space-y-2">
                        ${data.files.details.map(file => `
                            <div class="flex items-center justify-between text-sm">
                                <span>${file.name}</span>
                                <span class="${file.exists ? 'text-green-400' : 'text-red-400'}">
                                    ${file.exists ? '✅ Existe' : '❌ Não encontrado'}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            // Permissões
            html += `
                <div class="bg-gray-800/50 p-4 rounded-lg">
                    <h3 class="font-semibold mb-2 flex items-center">
                        <i class="fas fa-lock mr-2 ${data.permissions.all_ok ? 'text-green-400' : 'text-red-400'}"></i>
                        Permissões de Arquivo
                    </h3>
                    <div class="space-y-2">
                        ${data.permissions.details.map(perm => `
                            <div class="flex items-center justify-between text-sm">
                                <span>${perm.file}</span>
                                <span class="${perm.writable ? 'text-green-400' : 'text-red-400'}">
                                    ${perm.writable ? '✅ Gravável' : '❌ Sem permissão'}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            html += '</div>';
            container.innerHTML = html;
        }

        function updateLogs(logs) {
            const container = document.getElementById('logs-content');
            if (logs && logs.length > 0) {
                container.innerHTML = logs.map(log => `<div class="mb-1">${log}</div>`).join('');
            } else {
                container.innerHTML = '<div class="text-gray-400">Nenhum log disponível</div>';
            }
        }

        async function fixConnection() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Corrigindo...';
            button.disabled = true;
            
            try {
                const response = await fetch('?action=fix_connection');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text);
                    throw new Error('Resposta do servidor não é JSON válido');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Conexão corrigida com sucesso!');
                    runDiagnosis();
                } else {
                    alert('❌ Erro ao corrigir conexão: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao corrigir conexão:', error);
                alert('❌ Erro: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        async function fixTables() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Corrigindo...';
            button.disabled = true;
            
            try {
                const response = await fetch('?action=fix_tables');
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Tabelas corrigidas com sucesso!');
                    runDiagnosis();
                } else {
                    alert('❌ Erro ao corrigir tabelas: ' + data.error);
                }
            } catch (error) {
                alert('❌ Erro: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        async function fixFiles() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Corrigindo...';
            button.disabled = true;
            
            try {
                const response = await fetch('?action=fix_files');
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Arquivos corrigidos com sucesso!');
                    runDiagnosis();
                } else {
                    alert('❌ Erro ao corrigir arquivos: ' + data.error);
                }
            } catch (error) {
                alert('❌ Erro: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        async function fixAll() {
            if (!confirm('Tem certeza que deseja corrigir todos os problemas? Esta ação pode demorar alguns minutos.')) {
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Corrigindo tudo...';
            button.disabled = true;
            
            try {
                const response = await fetch('?action=fix_all');
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Todos os problemas foram corrigidos!');
                    runDiagnosis();
                } else {
                    alert('❌ Erro ao corrigir problemas: ' + data.error);
                }
            } catch (error) {
                alert('❌ Erro: ' + error.message);
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
    // Limpar qualquer output anterior
    ob_clean();
    
    // Definir headers corretos
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $result = null;
        
        switch ($_GET['action']) {
            case 'diagnosis':
                $result = runCompleteDiagnosis();
                break;
            case 'fix_connection':
                $result = fixConnection();
                break;
            case 'fix_tables':
                $result = fixTables();
                break;
            case 'fix_files':
                $result = fixFiles();
                break;
            case 'fix_all':
                $result = fixAll();
                break;
            default:
                $result = ['success' => false, 'error' => 'Ação não reconhecida'];
        }
        
        // Garantir que o resultado é válido
        if ($result === null) {
            $result = ['success' => false, 'error' => 'Erro interno do servidor'];
        }
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erro interno: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    } catch (Error $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erro fatal: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

function runCompleteDiagnosis() {
    global $pdo;
    
    $logs = [];
    $logs[] = "[" . date('Y-m-d H:i:s') . "] Iniciando diagnóstico completo";
    
    // 1. Testar conexão
    $connection = testConnection();
    $logs[] = "[" . date('Y-m-d H:i:s') . "] Teste de conexão: " . ($connection['success'] ? 'OK' : 'ERRO');
    
    // 2. Verificar tabelas
    $tables = checkTables();
    $logs[] = "[" . date('Y-m-d H:i:s') . "] Verificação de tabelas: {$tables['existing']}/{$tables['total']} encontradas";
    
    // 3. Verificar arquivos
    $files = checkFiles();
    $logs[] = "[" . date('Y-m-d H:i:s') . "] Verificação de arquivos: " . ($files['all_exist'] ? 'OK' : 'PROBLEMAS');
    
    // 4. Verificar permissões
    $permissions = checkPermissions();
    $logs[] = "[" . date('Y-m-d H:i:s') . "] Verificação de permissões: " . ($permissions['all_ok'] ? 'OK' : 'PROBLEMAS');
    
    return [
        'connection' => $connection,
        'tables' => $tables,
        'files' => $files,
        'permissions' => $permissions,
        'logs' => $logs
    ];
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
    $details = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $existing++;
            $details[] = ['name' => $table, 'exists' => true, 'records' => $count];
        } catch (PDOException $e) {
            $details[] = ['name' => $table, 'exists' => false, 'records' => 0];
        }
    }
    
    return [
        'total' => count($tables),
        'existing' => $existing,
        'all_exist' => $existing === count($tables),
        'details' => $details
    ];
}

function checkFiles() {
    $files = [
        'config.php',
        'auto_update_database.php',
        'monitor_database.php',
        'status_dashboard.php',
        'notify_database_status.php'
    ];
    
    $existing = 0;
    $details = [];
    
    foreach ($files as $file) {
        $exists = file_exists($file);
        if ($exists) $existing++;
        $details[] = ['name' => $file, 'exists' => $exists];
    }
    
    return [
        'total' => count($files),
        'existing' => $existing,
        'all_exist' => $existing === count($files),
        'details' => $details
    ];
}

function checkPermissions() {
    $files = [
        'cache/',
        'uploads/',
        'logs/'
    ];
    
    $all_ok = true;
    $details = [];
    
    foreach ($files as $file) {
        $writable = is_writable($file);
        if (!$writable) $all_ok = false;
        $details[] = ['file' => $file, 'writable' => $writable];
    }
    
    return [
        'all_ok' => $all_ok,
        'details' => $details
    ];
}

function fixConnection() {
    // Tentar recriar a conexão
    try {
        require 'config.php';
        $pdo->query("SELECT 1");
        return ['success' => true, 'message' => 'Conexão restaurada'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function fixTables() {
    global $pdo;
    
    try {
        // Criar tabelas que estão faltando
        $tables_sql = [
            "CREATE TABLE IF NOT EXISTS categorias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                descricao TEXT,
                imagem VARCHAR(255),
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS banners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(200) NOT NULL,
                descricao TEXT,
                imagem VARCHAR(255) NOT NULL,
                link VARCHAR(500),
                posicao ENUM('principal', 'secundario', 'lateral') DEFAULT 'principal',
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                ordem INT DEFAULT 0,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS notificacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                titulo VARCHAR(200) NOT NULL,
                mensagem TEXT NOT NULL,
                tipo ENUM('info', 'sucesso', 'aviso', 'erro') DEFAULT 'info',
                lida BOOLEAN DEFAULT FALSE,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS favoritos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                produto_id INT,
                curso_id INT,
                tipo ENUM('produto', 'curso') NOT NULL,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
                FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                UNIQUE KEY unique_favorito (user_id, produto_id, curso_id, tipo)
            )"
        ];
        
        foreach ($tables_sql as $sql) {
            $pdo->exec($sql);
        }
        
        return ['success' => true, 'message' => 'Tabelas criadas/verificadas'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function fixFiles() {
    try {
        // Criar diretórios necessários
        $dirs = ['cache', 'uploads', 'logs'];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Criar arquivos de log se não existirem
        $log_files = ['database_deploy.log', 'webhook_database.log'];
        foreach ($log_files as $log_file) {
            if (!file_exists($log_file)) {
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Arquivo de log criado\n");
            }
        }
        
        return ['success' => true, 'message' => 'Arquivos criados/verificados'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function fixAll() {
    $results = [];
    
    // Corrigir conexão
    $connection_result = fixConnection();
    $results[] = $connection_result;
    
    // Corrigir tabelas
    $tables_result = fixTables();
    $results[] = $tables_result;
    
    // Corrigir arquivos
    $files_result = fixFiles();
    $results[] = $files_result;
    
    $all_success = true;
    foreach ($results as $result) {
        if (!$result['success']) {
            $all_success = false;
            break;
        }
    }
    
    return [
        'success' => $all_success,
        'results' => $results,
        'message' => $all_success ? 'Todos os problemas corrigidos' : 'Alguns problemas não puderam ser corrigidos'
    ];
}
?>
