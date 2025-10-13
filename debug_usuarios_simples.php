<?php
session_start();
require 'config.php';

// Verificar se é administrador
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

echo "<h1>Debug Usuários - Versão Simples</h1>";

try {
    // Testar conexão
    echo "<h2>1. Teste de Conexão:</h2>";
    echo "Conexão com banco: " . ($pdo ? "✅ OK" : "❌ ERRO") . "<br>";
    
    // Verificar se tabela users existe
    echo "<h2>2. Verificação da Tabela Users:</h2>";
    $users_exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    echo "Tabela 'users' existe: " . ($users_exists ? "✅ SIM" : "❌ NÃO") . "<br>";
    
    if ($users_exists) {
        // Contar usuários
        echo "<h2>3. Contagem de Usuários:</h2>";
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "Total de usuários: $total_users<br>";
        
        // Mostrar todos os usuários
        echo "<h2>4. Lista de Usuários:</h2>";
        $users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($users)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Ativo</th><th>Data Cadastro</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['username']}</td>";
                echo "<td>{$user['role']}</td>";
                echo "<td>" . (isset($user['ativo']) ? $user['ativo'] : 'N/A') . "</td>";
                echo "<td>" . (isset($user['data_cadastro']) ? $user['data_cadastro'] : 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ Nenhum usuário encontrado na tabela!<br>";
        }
        
        // Testar consulta específica do painel
        echo "<h2>5. Teste da Consulta do Painel:</h2>";
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, role, data_cadastro, 
                       COALESCE(ativo, 1) as ativo
                FROM users 
                ORDER BY data_cadastro DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $users_painel = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "✅ Consulta executada com sucesso!<br>";
            echo "Usuários encontrados: " . count($users_painel) . "<br>";
            
            if (!empty($users_painel)) {
                echo "<h3>Resultado da consulta do painel:</h3>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Ativo</th><th>Data Cadastro</th></tr>";
                foreach ($users_painel as $user) {
                    echo "<tr>";
                    echo "<td>{$user['id']}</td>";
                    echo "<td>{$user['username']}</td>";
                    echo "<td>{$user['role']}</td>";
                    echo "<td>{$user['ativo']}</td>";
                    echo "<td>{$user['data_cadastro']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "❌ Nenhum usuário retornado pela consulta do painel!<br>";
            }
        } catch (Exception $e) {
            echo "❌ Erro na consulta do painel: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "<h2>❌ PROBLEMA: Tabela 'users' não existe!</h2>";
        echo "Você precisa criar a tabela users primeiro.<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO GERAL:</h2>";
    echo "Erro: " . $e->getMessage() . "<br>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Usuários Simples</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1, h2 { color: #333; }
        h2 { border-bottom: 2px solid #007cba; padding-bottom: 5px; }
        table { margin: 10px 0; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <p><a href="admin_painel_simples.php">← Voltar ao Painel de Usuários</a></p>
</body>
</html>
