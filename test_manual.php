<?php
/**
 * TESTE MANUAL VIA TERMINAL
 * HELMER ACADEMY - HOSTINGER
 * 
 * Execute: php test_manual.php
 */

echo "=== DIAGNÓSTICO MANUAL - HELMER ACADEMY ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Verificar PHP
echo "1. VERIFICAÇÃO DO PHP:\n";
echo "   Versão: " . PHP_VERSION . "\n";
echo "   Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido') . "\n";
echo "   Memory Limit: " . ini_get('memory_limit') . "\n";
echo "   Max Execution Time: " . ini_get('max_execution_time') . "s\n\n";

// 2. Verificar config.php
echo "2. VERIFICAÇÃO DO CONFIG.PHP:\n";
if (file_exists('config.php')) {
    echo "   ✅ Arquivo encontrado\n";
    
    try {
        ob_start();
        include 'config.php';
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "   ⚠️  Output detectado: " . substr($output, 0, 100) . "...\n";
        } else {
            echo "   ✅ Incluído sem output\n";
        }
        
        if (isset($pdo)) {
            echo "   ✅ Variável \$pdo criada\n";
        } else {
            echo "   ❌ Variável \$pdo não criada\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Erro: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Arquivo não encontrado\n";
}
echo "\n";

// 3. Testar conexão
echo "3. TESTE DE CONEXÃO:\n";
if (isset($pdo)) {
    try {
        $start = microtime(true);
        $pdo->query("SELECT 1");
        $time = round((microtime(true) - $start) * 1000, 2);
        echo "   ✅ Conexão OK (tempo: {$time}ms)\n";
        
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        echo "   ✅ MySQL: " . $version . "\n";
        
    } catch (Exception $e) {
        echo "   ❌ Erro na conexão: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ PDO não disponível\n";
}
echo "\n";

// 4. Verificar tabelas
echo "4. VERIFICAÇÃO DE TABELAS:\n";
if (isset($pdo)) {
    $tables = ['users', 'produtos', 'cursos', 'comentarios', 'categorias', 'banners', 'notificacoes', 'favoritos'];
    $found = 0;
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "   ✅ $table: $count registros\n";
            $found++;
        } catch (Exception $e) {
            echo "   ❌ $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "   📊 Total: $found/" . count($tables) . " tabelas encontradas\n";
} else {
    echo "   ❌ PDO não disponível\n";
}
echo "\n";

// 5. Verificar arquivos
echo "5. VERIFICAÇÃO DE ARQUIVOS:\n";
$files = [
    'config.php',
    'index.php', 
    'login.php',
    'auto_update_database.php',
    'monitor_database.php',
    'status_dashboard.php',
    'fix_database_issues.php',
    'test_connection.php',
    'debug_simple.php'
];

$found = 0;
foreach ($files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "   ✅ $file: {$size} bytes\n";
        $found++;
    } else {
        echo "   ❌ $file: Não encontrado\n";
    }
}
echo "   📊 Total: $found/" . count($files) . " arquivos encontrados\n\n";

// 6. Teste de JSON
echo "6. TESTE DE JSON:\n";
$test_data = [
    'success' => true,
    'message' => 'Teste de JSON',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => [
        'connection' => isset($pdo) ? 'OK' : 'ERRO',
        'tables' => $found ?? 0,
        'files' => $found ?? 0
    ]
];

$json = json_encode($test_data, JSON_UNESCAPED_UNICODE);
if ($json !== false) {
    echo "   ✅ JSON criado com sucesso\n";
    echo "   📏 Tamanho: " . strlen($json) . " caracteres\n";
    
    $decoded = json_decode($json, true);
    if ($decoded !== null) {
        echo "   ✅ JSON decodificado com sucesso\n";
    } else {
        echo "   ❌ Erro ao decodificar: " . json_last_error_msg() . "\n";
    }
} else {
    echo "   ❌ Erro ao criar JSON: " . json_last_error_msg() . "\n";
}
echo "\n";

// 7. Verificar permissões
echo "7. VERIFICAÇÃO DE PERMISSÕES:\n";
$dirs = ['cache', 'uploads', 'logs'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        echo "   " . ($writable ? '✅' : '❌') . " $dir: " . ($writable ? 'Gravável' : 'Não gravável') . "\n";
    } else {
        echo "   ❌ $dir: Não existe\n";
    }
}
echo "\n";

// 8. Resumo final
echo "=== RESUMO FINAL ===\n";
echo "✅ PHP funcionando\n";
echo "✅ Config: " . (file_exists('config.php') ? 'OK' : 'ERRO') . "\n";
echo "✅ PDO: " . (isset($pdo) ? 'OK' : 'ERRO') . "\n";
echo "✅ Tabelas: " . ($found ?? 0) . " encontradas\n";
echo "✅ Arquivos: " . ($found ?? 0) . " encontrados\n";
echo "✅ JSON: " . ($json !== false ? 'OK' : 'ERRO') . "\n";
echo "\n=== FIM DO DIAGNÓSTICO ===\n";
?>
