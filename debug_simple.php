<?php
/**
 * DIAGNÓSTICO SIMPLES - HELMER ACADEMY
 * 
 * Script que executa diagnósticos sem AJAX para identificar problemas
 */

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
    <title>Diagnóstico Simples - HELMER ACADEMY</title>
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
                <i class="fas fa-bug text-red-400 mr-3"></i>
                Diagnóstico Simples
            </h1>
            <p class="text-gray-400">Verificação direta sem AJAX</p>
        </div>

        <div class="max-w-6xl mx-auto space-y-6">
            
            <!-- Teste 1: Informações do Sistema -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                    1. Informações do Sistema
                </h2>
                <div class="code-block">
                    <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                    <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'; ?><br>
                    <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Desconhecido'; ?><br>
                    <strong>Script Path:</strong> <?php echo __FILE__; ?><br>
                    <strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                    <strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?><br>
                    <strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?>s<br>
                </div>
            </div>

            <!-- Teste 2: Verificar config.php -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-cog text-yellow-400 mr-2"></i>
                    2. Verificação do config.php
                </h2>
                <?php
                try {
                    if (file_exists('config.php')) {
                        echo '<div class="status-ok mb-4">✅ Arquivo config.php encontrado</div>';
                        
                        // Tentar incluir o arquivo
                        ob_start();
                        include 'config.php';
                        $output = ob_get_clean();
                        
                        if (!empty($output)) {
                            echo '<div class="status-warning mb-4">⚠️ Output detectado ao incluir config.php:</div>';
                            echo '<div class="code-block">' . htmlspecialchars($output) . '</div>';
                        } else {
                            echo '<div class="status-ok mb-4">✅ config.php incluído sem output</div>';
                        }
                        
                        // Verificar se $pdo foi criado
                        if (isset($pdo)) {
                            echo '<div class="status-ok mb-4">✅ Variável $pdo criada</div>';
                        } else {
                            echo '<div class="status-error mb-4">❌ Variável $pdo não foi criada</div>';
                        }
                        
                    } else {
                        echo '<div class="status-error mb-4">❌ Arquivo config.php não encontrado</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="status-error mb-4">❌ Erro ao incluir config.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>

            <!-- Teste 3: Conexão com Banco -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-database text-green-400 mr-2"></i>
                    3. Teste de Conexão com Banco
                </h2>
                <?php
                if (isset($pdo)) {
                    try {
                        $start_time = microtime(true);
                        $pdo->query("SELECT 1");
                        $end_time = microtime(true);
                        $response_time = round(($end_time - $start_time) * 1000, 2);
                        
                        echo '<div class="status-ok mb-4">✅ Conexão estabelecida com sucesso</div>';
                        echo '<div class="code-block">Tempo de resposta: ' . $response_time . 'ms</div>';
                        
                        // Testar uma query mais complexa
                        $stmt = $pdo->query("SELECT VERSION() as version");
                        $version = $stmt->fetch()['version'];
                        echo '<div class="code-block">Versão do MySQL: ' . htmlspecialchars($version) . '</div>';
                        
                    } catch (Exception $e) {
                        echo '<div class="status-error mb-4">❌ Erro na conexão: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<div class="status-error mb-4">❌ Variável $pdo não disponível</div>';
                }
                ?>
            </div>

            <!-- Teste 4: Verificar Tabelas -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-table text-purple-400 mr-2"></i>
                    4. Verificação de Tabelas
                </h2>
                <?php
                if (isset($pdo)) {
                    $tables = ['users', 'produtos', 'cursos', 'comentarios', 'categorias', 'banners', 'notificacoes', 'favoritos'];
                    $existing = 0;
                    
                    echo '<div class="code-block">';
                    foreach ($tables as $table) {
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                            $count = $stmt->fetch()['count'];
                            $existing++;
                            echo "✅ $table: $count registros\n";
                        } catch (Exception $e) {
                            echo "❌ $table: " . $e->getMessage() . "\n";
                        }
                    }
                    echo '</div>';
                    
                    echo '<div class="mt-4">';
                    echo '<div class="status-' . ($existing === count($tables) ? 'ok' : 'warning') . '">';
                    echo ($existing === count($tables) ? '✅' : '⚠️') . ' Tabelas encontradas: ' . $existing . '/' . count($tables);
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="status-error">❌ Não é possível verificar tabelas - $pdo não disponível</div>';
                }
                ?>
            </div>

            <!-- Teste 5: Verificar Arquivos -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-file text-orange-400 mr-2"></i>
                    5. Verificação de Arquivos
                </h2>
                <?php
                $files = [
                    'config.php',
                    'index.php', 
                    'login.php',
                    'auto_update_database.php',
                    'monitor_database.php',
                    'status_dashboard.php',
                    'fix_database_issues.php',
                    'test_connection.php'
                ];
                
                $existing = 0;
                echo '<div class="code-block">';
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        $size = filesize($file);
                        $modified = date('Y-m-d H:i:s', filemtime($file));
                        echo "✅ $file: {$size} bytes (modificado: $modified)\n";
                        $existing++;
                    } else {
                        echo "❌ $file: Não encontrado\n";
                    }
                }
                echo '</div>';
                
                echo '<div class="mt-4">';
                echo '<div class="status-' . ($existing === count($files) ? 'ok' : 'warning') . '">';
                echo ($existing === count($files) ? '✅' : '⚠️') . ' Arquivos encontrados: ' . $existing . '/' . count($files);
                echo '</div>';
                echo '</div>';
                ?>
            </div>

            <!-- Teste 6: Verificar Permissões -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-lock text-red-400 mr-2"></i>
                    6. Verificação de Permissões
                </h2>
                <?php
                $dirs = ['cache', 'uploads', 'logs'];
                $files_to_check = ['config.php', 'index.php'];
                
                echo '<div class="code-block">';
                
                // Verificar diretórios
                echo "Diretórios:\n";
                foreach ($dirs as $dir) {
                    if (is_dir($dir)) {
                        $writable = is_writable($dir);
                        echo ($writable ? '✅' : '❌') . " $dir: " . ($writable ? 'Gravável' : 'Não gravável') . "\n";
                    } else {
                        echo "❌ $dir: Não existe\n";
                    }
                }
                
                echo "\nArquivos:\n";
                foreach ($files_to_check as $file) {
                    if (file_exists($file)) {
                        $readable = is_readable($file);
                        echo ($readable ? '✅' : '❌') . " $file: " . ($readable ? 'Legível' : 'Não legível') . "\n";
                    } else {
                        echo "❌ $file: Não encontrado\n";
                    }
                }
                
                echo '</div>';
                ?>
            </div>

            <!-- Teste 7: Teste de JSON -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-code text-cyan-400 mr-2"></i>
                    7. Teste de JSON
                </h2>
                <?php
                // Testar criação de JSON
                $test_data = [
                    'success' => true,
                    'message' => 'Teste de JSON',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'data' => [
                        'connection' => isset($pdo) ? 'OK' : 'ERRO',
                        'tables' => $existing ?? 0,
                        'files' => $existing ?? 0
                    ]
                ];
                
                $json_string = json_encode($test_data, JSON_UNESCAPED_UNICODE);
                
                if ($json_string !== false) {
                    echo '<div class="status-ok mb-4">✅ JSON criado com sucesso</div>';
                    echo '<div class="code-block">' . htmlspecialchars($json_string) . '</div>';
                    
                    // Testar decodificação
                    $decoded = json_decode($json_string, true);
                    if ($decoded !== null) {
                        echo '<div class="status-ok mb-4">✅ JSON decodificado com sucesso</div>';
                    } else {
                        echo '<div class="status-error mb-4">❌ Erro ao decodificar JSON: ' . json_last_error_msg() . '</div>';
                    }
                } else {
                    echo '<div class="status-error mb-4">❌ Erro ao criar JSON: ' . json_last_error_msg() . '</div>';
                }
                ?>
            </div>

            <!-- Resumo -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-summary text-green-400 mr-2"></i>
                    Resumo do Diagnóstico
                </h2>
                <div class="code-block">
                    <?php
                    echo "=== RESUMO DO DIAGNÓSTICO ===\n";
                    echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
                    echo "PHP Version: " . PHP_VERSION . "\n";
                    echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido') . "\n";
                    echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Desconhecido') . "\n";
                    echo "Script Path: " . __FILE__ . "\n";
                    echo "\n=== STATUS ===\n";
                    echo "Config.php: " . (file_exists('config.php') ? 'OK' : 'ERRO') . "\n";
                    echo "Conexão PDO: " . (isset($pdo) ? 'OK' : 'ERRO') . "\n";
                    echo "Tabelas: " . ($existing ?? 0) . " encontradas\n";
                    echo "Arquivos: " . ($existing ?? 0) . " encontrados\n";
                    echo "JSON: " . ($json_string !== false ? 'OK' : 'ERRO') . "\n";
                    ?>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
