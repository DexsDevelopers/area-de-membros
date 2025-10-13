<?php
// Inicia a sessão no topo de tudo.
session_start();

// Inclui o arquivo de conexão com o banco de dados.
require 'config.php';

// Garante que o script só seja acessado via método POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se não for POST, redireciona para a página de login.
    header('Location: login.php');
    exit();
}

// 1. RECEBE E VALIDA OS DADOS DO FORMULÁRIO
// ----------------------------------------------------
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Se algum campo estiver vazio, redireciona de volta com um erro.
if (empty($username) || empty($password)) {
    header('Location: login.php?error=empty');
    exit();
}


// 2. BUSCA O USUÁRIO NO BANCO DE DADOS
// ----------------------------------------------------
try {
    // Prepara a query para buscar o usuário pelo 'username'.
    // É importante selecionar também a senha_hash e a role.
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    // Pega o resultado da busca.
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Em caso de erro no banco, redireciona com um erro genérico.
    // O ideal é logar o erro real para o desenvolvedor.
    error_log("Erro no login: " . $e->getMessage());
    header('Location: login.php?error=db_error');
    exit();
}


// 3. VERIFICA O USUÁRIO E A SENHA
// ----------------------------------------------------

// Verifica se o usuário foi encontrado E se a senha digitada corresponde à senha hash no banco.
if ($user && password_verify($password, $user['password'])) {
    
    // Se tudo estiver correto, o login é um sucesso!
    
    // Regenera o ID da sessão para prevenir ataques de "session fixation".
    session_regenerate_id(true);

    // Guarda as informações importantes do usuário na sessão.
    $_SESSION['user_id'] = $user['id'];       // O ID é útil para várias operações.
    $_SESSION['user'] = $user['username']; // O nome de usuário para saudação.
    $_SESSION['role'] = $user['role'];       // A 'role' para controle de acesso ('admin' ou 'user').
    
    // Redireciona o usuário com base na sua 'role'.
    if ($user['role'] === 'admin') {
        header('Location: admin_painel.php');
    } else {
        header('Location: index.php'); // Ou outra página para usuários comuns
    }
    exit();

} else {
    // Se o usuário não foi encontrado OU a senha está incorreta, o login falha.
    // Redireciona de volta para a página de login com um erro genérico para não dar pistas a invasores.
    header('Location: login.php?error=invalid');
    exit();
}
?>