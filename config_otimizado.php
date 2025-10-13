<?php
// Configuração otimizada para produção
$host = "localhost";
$db   = "u853242961_helmer_db";
$user = "u853242961_helmer_user";
$pass = "Lucastav8012@";
$charset = "utf8mb4";

// Configurações otimizadas para performance
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    // Otimizações de performance
    PDO::ATTR_PERSISTENT => true, // Conexão persistente
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    PDO::MYSQL_ATTR_FOUND_ROWS => true,
    // Timeouts otimizados
    PDO::ATTR_TIMEOUT => 5, // 5 segundos timeout
];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Configurações adicionais do MySQL para performance
    $pdo->exec("SET SESSION wait_timeout = 300");
    $pdo->exec("SET SESSION interactive_timeout = 300");
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    
    // Log de conexão bem-sucedida
    error_log("Conexão otimizada estabelecida com sucesso");
    
} catch (PDOException $e) {
    error_log("Erro na conexão otimizada: " . $e->getMessage());
    die("Erro ao conectar com o banco de dados.");
}
?>
