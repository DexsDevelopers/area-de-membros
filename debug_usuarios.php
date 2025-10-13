<?php
session_start();
require 'config.php';

// Verificar se é administrador
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

echo "<h1>Debug Painel de Usuários - Verificação de Contabilização</h1>";

try {
    // Testar conexão
    echo "<h2>1. Teste de Conexão:</h2>";
    echo "Conexão com banco: " . ($pdo ? "✅ OK" : "❌ ERRO") . "<br>";
    
    // Verificar se tabela users existe
    echo "<h2>2. Verificação da Tabela Users:</h2>";
    $users_exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    echo "Tabela 'users' existe: " . ($users_exists ? "✅ SIM" : "❌ NÃO") . "<br>";
    
    if ($users_exists) {
        // Verificar estrutura da tabela users
        echo "<h2>3. Estrutura da tabela users:</h2>";
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']}) - Null: {$column['Null']} - Default: {$column['Default']}<br>";
        }
        
        // Testar consultas básicas
        echo "<h2>4. Teste de Consultas Básicas:</h2>";
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "Total de usuários: $total_users<br>";
        
        // Testar por role
        echo "<h2>5. Usuários por Role:</h2>";
        $users_by_role = $pdo->query("SELECT role, COUNT(*) as total FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users_by_role as $user) {
            echo "- Role '{$user['role']}': {$user['total']} usuários<br>";
        }
        
        // Testar campo ativo
        echo "<h2>6. Teste do Campo 'ativo':</h2>";
        try {
            $users_by_ativo = $pdo->query("SELECT ativo, COUNT(*) as total FROM users GROUP BY ativo")->fetchAll(PDO::FETCH_ASSOC);
            echo "Usuários por status ativo:<br>";
            foreach ($users_by_ativo as $user) {
                echo "- ativo={$user['ativo']}: {$user['total']} usuários<br>";
            }
        } catch (Exception $e) {
            echo "❌ Campo 'ativo' não existe: " . $e->getMessage() . "<br>";
            echo "Usando COALESCE(ativo, 1) para simular campo ativo<br>";
        }
        
        // Testar campo data_cadastro
        echo "<h2>7. Teste do Campo 'data_cadastro':</h2>";
        try {
            $recent_users = $pdo->query("SELECT COUNT(*) FROM users WHERE data_cadastro >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetchColumn();
            echo "Usuários cadastrados no último mês: $recent_users<br>";
        } catch (Exception $e) {
            echo "❌ Campo 'data_cadastro' não existe: " . $e->getMessage() . "<br>";
        }
        
        // Testar tabela favoritos
        echo "<h2>8. Teste da Tabela 'favoritos':</h2>";
        $favoritos_exists = $pdo->query("SHOW TABLES LIKE 'favoritos'")->fetchColumn();
        echo "Tabela 'favoritos' existe: " . ($favoritos_exists ? "✅ SIM" : "❌ NÃO") . "<br>";
        
        if ($favoritos_exists) {
            $total_favoritos = $pdo->query("SELECT COUNT(*) FROM favoritos")->fetchColumn();
            echo "Total de favoritos: $total_favoritos<br>";
        }
        
        // Testar consulta completa do painel
        echo "<h2>9. Teste da Consulta Completa do Painel:</h2>";
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, role, data_cadastro, 
                       COALESCE(ativo, 1) as ativo,
                       (SELECT COUNT(*) FROM favoritos WHERE user_id = users.id) as total_favoritos
                FROM users 
                ORDER BY data_cadastro DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "✅ Consulta executada com sucesso!<br>";
            echo "Usuários encontrados: " . count($users) . "<br>";
            
            if (!empty($users)) {
                echo "<h3>Primeiros 5 usuários:</h3>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Ativo</th><th>Favoritos</th><th>Data Cadastro</th></tr>";
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>{$user['username']}</td>";
                    echo "<td>{$user['role']}</td>";
                    echo "<td>{$user['ativo']}</td>";
                    echo "<td>{$user['total_favoritos']}</td>";
                    echo "<td>{$user['data_cadastro']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "❌ Erro na consulta completa: " . $e->getMessage() . "<br>";
        }
        
        // Testar estatísticas
        echo "<h2>10. Teste das Estatísticas:</h2>";
        $stats = [
            'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
            'total_admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
            'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE COALESCE(ativo, 1) = 1")->fetchColumn(),
            'new_this_month' => $pdo->query("SELECT COUNT(*) FROM users WHERE data_cadastro >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetchColumn()
        ];
        
        echo "Estatísticas calculadas:<br>";
        echo "- Total usuários (role='user'): {$stats['total_users']}<br>";
        echo "- Total admins (role='admin'): {$stats['total_admins']}<br>";
        echo "- Usuários ativos: {$stats['active_users']}<br>";
        echo "- Novos este mês: {$stats['new_this_month']}<br>";
        
    } else {
        echo "<h2>❌ PROBLEMA: Tabela 'users' não existe!</h2>";
        echo "Você precisa criar a tabela users primeiro.<br>";
        echo "SQL para criar a tabela:<br>";
        echo "<pre>";
        echo "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    ativo TINYINT(1) DEFAULT 1,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO GERAL:</h2>";
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
    <title>Debug Usuários</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1, h2 { color: #333; }
        h2 { border-bottom: 2px solid #007cba; padding-bottom: 5px; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { margin: 10px 0; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <p><a href="admin_painel_moderno.php">← Voltar ao Painel de Usuários</a></p>
    <p><a href="debug_dashboard.php">← Ver Debug do Dashboard</a></p>
</body>
</html>
