<?php
// Debug de tempo de login
require 'config.php';

echo "<h1>🔍 Debug de Tempo de Login</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .time{background:#f0f0f0;padding:5px;margin:5px 0;}</style>";

$startTime = microtime(true);

// 1. Teste de conexão
echo "<h2>1. Teste de Conexão</h2>";
$connectionStart = microtime(true);
try {
    $pdo->query("SELECT 1");
    $connectionTime = microtime(true) - $connectionStart;
    echo "<div class='success'>✅ Conexão OK - Tempo: " . round($connectionTime * 1000, 2) . "ms</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erro na conexão: " . $e->getMessage() . "</div>";
    exit();
}

// 2. Teste de busca de usuário
echo "<h2>2. Teste de Busca de Usuário</h2>";
$queryStart = microtime(true);
$testUsername = 'admin'; // Mude para um usuário existente
try {
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $queryTime = microtime(true) - $queryStart;
    
    if ($user) {
        echo "<div class='success'>✅ Usuário encontrado - Tempo: " . round($queryTime * 1000, 2) . "ms</div>";
        echo "<div class='info'>Dados: " . json_encode($user, JSON_PRETTY_PRINT) . "</div>";
    } else {
        echo "<div class='error'>❌ Usuário '$testUsername' não encontrado</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erro na query: " . $e->getMessage() . "</div>";
}

// 3. Teste de verificação de senha
echo "<h2>3. Teste de Verificação de Senha</h2>";
if ($user) {
    $passwordStart = microtime(true);
    $testPassword = 'admin123'; // Mude para a senha correta
    $isValid = password_verify($testPassword, $user['password']);
    $passwordTime = microtime(true) - $passwordStart;
    
    if ($isValid) {
        echo "<div class='success'>✅ Senha válida - Tempo: " . round($passwordTime * 1000, 2) . "ms</div>";
    } else {
        echo "<div class='error'>❌ Senha inválida - Tempo: " . round($passwordTime * 1000, 2) . "ms</div>";
    }
}

// 4. Teste de redirecionamento
echo "<h2>4. Teste de Redirecionamento</h2>";
$redirectStart = microtime(true);
// Simular redirecionamento
$redirectTime = microtime(true) - $redirectStart;
echo "<div class='info'>Redirecionamento simulado - Tempo: " . round($redirectTime * 1000, 2) . "ms</div>";

// 5. Tempo total
$totalTime = microtime(true) - $startTime;
echo "<h2>📊 Resumo de Tempos</h2>";
echo "<div class='time'>";
echo "<strong>Tempo Total:</strong> " . round($totalTime * 1000, 2) . "ms<br>";
echo "<strong>Conexão:</strong> " . round($connectionTime * 1000, 2) . "ms<br>";
echo "<strong>Query:</strong> " . round($queryTime * 1000, 2) . "ms<br>";
echo "<strong>Senha:</strong> " . round($passwordTime * 1000, 2) . "ms<br>";
echo "<strong>Redirecionamento:</strong> " . round($redirectTime * 1000, 2) . "ms<br>";
echo "</div>";

// 6. Análise de performance
echo "<h2>🔍 Análise de Performance</h2>";
if ($totalTime > 2) {
    echo "<div class='error'>⚠️ Login muito lento (>2s) - Verifique a conexão com o banco</div>";
} elseif ($totalTime > 1) {
    echo "<div class='error'>⚠️ Login lento (>1s) - Pode ser a conexão com o banco</div>";
} elseif ($totalTime > 0.5) {
    echo "<div class='info'>ℹ️ Login moderado (>0.5s) - Normal para servidor compartilhado</div>";
} else {
    echo "<div class='success'>✅ Login rápido (<0.5s) - Performance boa</div>";
}

// 7. Verificar configurações do banco
echo "<h2>7. Configurações do Banco</h2>";
try {
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'connect_timeout'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='info'>Connect Timeout: " . $result['Value'] . "s</div>";
    
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'wait_timeout'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='info'>Wait Timeout: " . $result['Value'] . "s</div>";
    
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'interactive_timeout'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='info'>Interactive Timeout: " . $result['Value'] . "s</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erro ao verificar configurações: " . $e->getMessage() . "</div>";
}

echo "<p><strong>Fim do debug.</strong></p>";
?>
