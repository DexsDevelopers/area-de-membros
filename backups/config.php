<?php
// config.php - conexão PDO segura

$host = "localhost"; // ajuste se precisar
$db   = "u853242961_helmer_db";
$user = "u853242961_helmer_user";
$pass = "Lucastav8012@";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // erros lançam exceções
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // fetch padrão associativo
  PDO::ATTR_EMULATE_PREPARES => false,               // desativa emulação para segurança
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  // Aqui poderia logar o erro em arquivo e mostrar uma mensagem genérica
  die("Erro ao conectar com o banco de dados.");
}
?>
