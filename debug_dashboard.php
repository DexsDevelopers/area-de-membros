<?php
session_start();
require 'config.php';

// Verificar se é administrador
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

echo "<h1>Debug Dashboard - Verificação de Contabilização</h1>";

try {
    // Testar conexão
    echo "<h2>1. Teste de Conexão:</h2>";
    echo "Conexão com banco: " . ($pdo ? "✅ OK" : "❌ ERRO") . "<br>";
    
    // Verificar tabelas existentes
    echo "<h2>2. Tabelas Existentes:</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
    // Testar consulta de usuários
    echo "<h2>3. Teste de Usuários:</h2>";
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    echo "Total de usuários (role='user'): $total_usuarios<br>";
    
    $total_usuarios_all = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total de usuários (todos): $total_usuarios_all<br>";
    
    // Verificar estrutura da tabela users
    echo "<h2>4. Estrutura da tabela users:</h2>";
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
    // Testar consulta de cursos
    echo "<h2>5. Teste de Cursos:</h2>";
    $total_cursos = $pdo->query("SELECT COUNT(*) FROM cursos WHERE ativo = 1")->fetchColumn();
    echo "Total de cursos ativos: $total_cursos<br>";
    
    $total_cursos_all = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
    echo "Total de cursos (todos): $total_cursos_all<br>";
    
    // Verificar estrutura da tabela cursos
    echo "<h2>6. Estrutura da tabela cursos:</h2>";
    $columns = $pdo->query("DESCRIBE cursos")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
    // Testar consulta de produtos
    echo "<h2>7. Teste de Produtos:</h2>";
    $total_produtos = $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn();
    echo "Total de produtos ativos: $total_produtos<br>";
    
    $total_produtos_all = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
    echo "Total de produtos (todos): $total_produtos_all<br>";
    
    // Verificar estrutura da tabela produtos
    echo "<h2>8. Estrutura da tabela produtos:</h2>";
    $columns = $pdo->query("DESCRIBE produtos")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
    // Testar dados específicos
    echo "<h2>9. Dados Específicos:</h2>";
    
    // Usuários com diferentes roles
    $users_by_role = $pdo->query("SELECT role, COUNT(*) as total FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
    echo "Usuários por role:<br>";
    foreach ($users_by_role as $user) {
        echo "- {$user['role']}: {$user['total']}<br>";
    }
    
    // Cursos por status
    $cursos_by_status = $pdo->query("SELECT ativo, COUNT(*) as total FROM cursos GROUP BY ativo")->fetchAll(PDO::FETCH_ASSOC);
    echo "Cursos por status:<br>";
    foreach ($cursos_by_status as $curso) {
        echo "- ativo={$curso['ativo']}: {$curso['total']}<br>";
    }
    
    // Produtos por status
    $produtos_by_status = $pdo->query("SELECT ativo, COUNT(*) as total FROM produtos GROUP BY ativo")->fetchAll(PDO::FETCH_ASSOC);
    echo "Produtos por status:<br>";
    foreach ($produtos_by_status as $produto) {
        echo "- ativo={$produto['ativo']}: {$produto['total']}<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO:</h2>";
    echo "Erro: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1, h2 { color: #333; }
        h2 { border-bottom: 2px solid #007cba; padding-bottom: 5px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <p><a href="admin_dashboard_moderno.php">← Voltar ao Dashboard</a></p>
</body>
</html>
