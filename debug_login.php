<?php
// Debug do sistema de login
session_start();
require 'config.php';

echo "<h1>🔍 Debug do Sistema de Login</h1>";

// 1. Testar conexão com banco
echo "<h2>1. Teste de Conexão com Banco</h2>";
try {
    $test = $pdo->query("SELECT 1");
    echo "✅ Conexão com banco: OK<br>";
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    exit();
}

// 2. Verificar se a tabela users existe
echo "<h2>2. Verificar Tabela Users</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $table = $stmt->fetch();
    if ($table) {
        echo "✅ Tabela 'users' existe<br>";
        
        // Verificar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Estrutura da tabela users:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar usuários
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $count = $stmt->fetch();
        echo "<br>📊 Total de usuários: " . $count['total'] . "<br>";
        
        // Listar usuários (sem senhas)
        $stmt = $pdo->query("SELECT id, username, role FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Usuários cadastrados:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ Tabela 'users' não existe<br>";
        echo "<h3>Criando tabela users...</h3>";
        
        $createTable = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createTable);
        echo "✅ Tabela 'users' criada com sucesso!<br>";
        
        // Criar usuário admin padrão
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $adminPassword, 'admin']);
        echo "✅ Usuário admin criado (username: admin, senha: admin123)<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar tabela: " . $e->getMessage() . "<br>";
}

// 3. Testar dados do POST
echo "<h2>3. Dados do Formulário</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "✅ Método POST detectado<br>";
    echo "Username: " . ($_POST['username'] ?? 'não informado') . "<br>";
    echo "Password: " . (isset($_POST['password']) ? '***' : 'não informado') . "<br>";
} else {
    echo "ℹ️ Nenhum POST recebido<br>";
}

// 4. Testar sessão
echo "<h2>4. Status da Sessão</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
if (isset($_SESSION['user'])) {
    echo "✅ Usuário logado: " . $_SESSION['user'] . "<br>";
} else {
    echo "❌ Nenhum usuário logado<br>";
}

// 5. Links de teste
echo "<h2>5. Links de Teste</h2>";
echo "<a href='login.php'>🔐 Ir para Login</a><br>";
echo "<a href='index.php'>🏠 Ir para Index</a><br>";
echo "<a href='debug_login.php'>🔄 Recarregar Debug</a><br>";

// 6. Formulário de teste
echo "<h2>6. Teste de Login</h2>";
echo "<form method='POST' action='processa_login.php'>";
echo "Username: <input type='text' name='username' value='admin'><br><br>";
echo "Password: <input type='password' name='password' value='admin123'><br><br>";
echo "<button type='submit'>🔐 Testar Login</button>";
echo "</form>";
?>
