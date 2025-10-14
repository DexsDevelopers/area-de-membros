<?php
/**
 * SISTEMA DE NOTIFICAÇÕES DO BANCO DE DADOS
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este script envia notificações sobre o status do banco
 * e executa verificações automáticas
 */

// Incluir configuração do banco
require 'config.php';

// Configurações de notificação
$admin_email = 'admin@helmeracademy.com'; // Substitua pelo seu email
$notification_enabled = true;

/**
 * Enviar notificação por email
 */
function sendNotification($subject, $message, $type = 'info') {
    global $admin_email, $notification_enabled;
    
    if (!$notification_enabled) return;
    
    $headers = [
        'From: HELMER ACADEMY <noreply@helmeracademy.com>',
        'Reply-To: ' . $admin_email,
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #1a1a1a; color: #ffffff; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #dc2626, #ef4444); padding: 20px; border-radius: 10px 10px 0 0; }
            .content { background: #2a2a2a; padding: 20px; border-radius: 0 0 10px 10px; }
            .status-{$type} { border-left: 4px solid " . ($type === 'success' ? '#10b981' : ($type === 'error' ? '#ef4444' : '#f59e0b')) . "; }
            .footer { text-align: center; margin-top: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🗄️ HELMER ACADEMY - Monitor de Banco</h1>
            </div>
            <div class='content status-{$type}'>
                {$message}
            </div>
            <div class='footer'>
                <p>Enviado automaticamente pelo sistema HELMER ACADEMY</p>
                <p>Data: " . date('d/m/Y H:i:s') . "</p>
            </div>
        </div>
    </body>
    </html>";
    
    return mail($admin_email, $subject, $html_message, implode("\r\n", $headers));
}

/**
 * Verificar status do banco de dados
 */
function checkDatabaseStatus() {
    global $pdo;
    
    $status = [
        'connection' => false,
        'tables' => [],
        'errors' => [],
        'warnings' => []
    ];
    
    try {
        // Testar conexão
        $pdo->query("SELECT 1");
        $status['connection'] = true;
    } catch (PDOException $e) {
        $status['errors'][] = "Erro de conexão: " . $e->getMessage();
        return $status;
    }
    
    // Verificar tabelas essenciais
    $essential_tables = ['users', 'produtos', 'cursos', 'comentarios'];
    $new_tables = ['categorias', 'banners', 'notificacoes', 'favoritos'];
    
    foreach ($essential_tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $status['tables'][$table] = ['exists' => true, 'records' => $count];
        } catch (PDOException $e) {
            $status['tables'][$table] = ['exists' => false, 'records' => 0];
            $status['errors'][] = "Tabela essencial '$table' não encontrada";
        }
    }
    
    foreach ($new_tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $status['tables'][$table] = ['exists' => true, 'records' => $count];
        } catch (PDOException $e) {
            $status['tables'][$table] = ['exists' => false, 'records' => 0];
            $status['warnings'][] = "Nova tabela '$table' não encontrada";
        }
    }
    
    return $status;
}

/**
 * Verificar logs de execução
 */
function checkExecutionLogs() {
    $logs = [];
    
    // Verificar arquivo de flag de execução
    if (file_exists('database_updated.flag')) {
        $logs['last_update'] = file_get_contents('database_updated.flag');
    }
    
    // Verificar timestamp da última execução
    if (file_exists('last_database_deploy.txt')) {
        $logs['last_execution'] = file_get_contents('last_database_deploy.txt');
    }
    
    // Verificar logs de deploy
    if (file_exists('database_deploy.log')) {
        $logs['deploy_log'] = file_get_contents('database_deploy.log');
    }
    
    return $logs;
}

/**
 * Executar verificação completa
 */
function runCompleteCheck() {
    $status = checkDatabaseStatus();
    $logs = checkExecutionLogs();
    
    $message = "<h2>📊 Status do Banco de Dados</h2>";
    
    if ($status['connection']) {
        $message .= "<p>✅ <strong>Conexão:</strong> Estabelecida com sucesso</p>";
    } else {
        $message .= "<p>❌ <strong>Conexão:</strong> Falha na conexão</p>";
    }
    
    $message .= "<h3>📋 Tabelas do Sistema</h3>";
    foreach ($status['tables'] as $table => $info) {
        if ($info['exists']) {
            $message .= "<p>✅ <strong>{$table}:</strong> {$info['records']} registros</p>";
        } else {
            $message .= "<p>❌ <strong>{$table}:</strong> Não encontrada</p>";
        }
    }
    
    if (!empty($status['errors'])) {
        $message .= "<h3>❌ Erros Encontrados</h3>";
        foreach ($status['errors'] as $error) {
            $message .= "<p>• {$error}</p>";
        }
    }
    
    if (!empty($status['warnings'])) {
        $message .= "<h3>⚠️ Avisos</h3>";
        foreach ($status['warnings'] as $warning) {
            $message .= "<p>• {$warning}</p>";
        }
    }
    
    if (!empty($logs)) {
        $message .= "<h3>📝 Logs de Execução</h3>";
        if (isset($logs['last_update'])) {
            $message .= "<p>🕒 <strong>Última atualização:</strong> {$logs['last_update']}</p>";
        }
        if (isset($logs['last_execution'])) {
            $lastExec = date('d/m/Y H:i:s', $logs['last_execution']);
            $message .= "<p>🕒 <strong>Última execução:</strong> {$lastExec}</p>";
        }
    }
    
    return [
        'status' => $status,
        'logs' => $logs,
        'message' => $message
    ];
}

// Processar requisições
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'check':
            $result = runCompleteCheck();
            echo json_encode($result);
            break;
            
        case 'notify':
            $result = runCompleteCheck();
            $type = !empty($result['status']['errors']) ? 'error' : 'success';
            $sent = sendNotification(
                'HELMER ACADEMY - Status do Banco de Dados',
                $result['message'],
                $type
            );
            echo json_encode(['sent' => $sent, 'type' => $type]);
            break;
            
        case 'auto_check':
            // Verificação automática (pode ser chamada por cron)
            $result = runCompleteCheck();
            
            // Se há erros, enviar notificação
            if (!empty($result['status']['errors'])) {
                sendNotification(
                    '🚨 HELMER ACADEMY - Erros no Banco de Dados',
                    $result['message'],
                    'error'
                );
            }
            
            // Se não há erros e é a primeira verificação bem-sucedida, enviar confirmação
            if (empty($result['status']['errors']) && !file_exists('database_health_check.flag')) {
                sendNotification(
                    '✅ HELMER ACADEMY - Banco de Dados Funcionando',
                    $result['message'],
                    'success'
                );
                file_put_contents('database_health_check.flag', date('Y-m-d H:i:s'));
            }
            
            echo json_encode(['checked' => true, 'errors' => count($result['status']['errors'])]);
            break;
    }
    exit;
}

// Se acessado diretamente, mostrar interface
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações do Banco - HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4">
                <i class="fas fa-bell text-red-400 mr-3"></i>
                Sistema de Notificações
            </h1>
            <p class="text-gray-400">Monitore e receba notificações sobre o status do banco</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-bold mb-4">Verificação Manual</h2>
                <button onclick="checkStatus()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors w-full">
                    <i class="fas fa-search mr-2"></i>
                    Verificar Status
                </button>
            </div>

            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-bold mb-4">Enviar Notificação</h2>
                <button onclick="sendNotification()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors w-full">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Enviar Email
                </button>
            </div>
        </div>

        <div id="result" class="mt-8"></div>
    </div>

    <script>
        async function checkStatus() {
            const result = document.getElementById('result');
            result.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Verificando...</div>';
            
            try {
                const response = await fetch('?action=check');
                const data = await response.json();
                
                result.innerHTML = `
                    <div class="bg-gray-800 p-6 rounded-xl">
                        <h3 class="text-xl font-bold mb-4">Resultado da Verificação</h3>
                        <div class="prose prose-invert max-w-none">
                            ${data.message}
                        </div>
                    </div>
                `;
            } catch (error) {
                result.innerHTML = `<div class="bg-red-800 p-4 rounded-lg">Erro: ${error.message}</div>`;
            }
        }

        async function sendNotification() {
            const result = document.getElementById('result');
            result.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Enviando...</div>';
            
            try {
                const response = await fetch('?action=notify');
                const data = await response.json();
                
                if (data.sent) {
                    result.innerHTML = '<div class="bg-green-800 p-4 rounded-lg">✅ Notificação enviada com sucesso!</div>';
                } else {
                    result.innerHTML = '<div class="bg-red-800 p-4 rounded-lg">❌ Erro ao enviar notificação</div>';
                }
            } catch (error) {
                result.innerHTML = `<div class="bg-red-800 p-4 rounded-lg">Erro: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
