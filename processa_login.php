<?php
// Inicia a sessão no topo de tudo.
session_start();

// Inclui o arquivo de conexão com o banco de dados.
require 'config.php';

// Inclui as funções do sistema "Lembre-me"
require 'remember_me_functions.php';

// Garante que o script só seja acessado via método POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se não for POST, redireciona para a página de login.
    header('Location: login.php');
    exit();
}

// Log de tempo para debug
$startTime = microtime(true);
error_log("Login iniciado em: " . date('Y-m-d H:i:s'));

// 1. RECEBE E VALIDA OS DADOS DO FORMULÁRIO
// ----------------------------------------------------
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember']); // Verifica se "Lembre-me" foi marcado

// Se algum campo estiver vazio, redireciona de volta com um erro.
if (empty($username) || empty($password)) {
    header('Location: login.php?error=empty');
    exit();
}


// 2. BUSCA O USUÁRIO NO BANCO DE DADOS
// ----------------------------------------------------
$queryStart = microtime(true);
try {
    // Prepara a query para buscar o usuário pelo 'username'.
    // É importante selecionar também a senha_hash e a role.
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    // Pega o resultado da busca.
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $queryTime = microtime(true) - $queryStart;
    error_log("Query executada em: " . round($queryTime * 1000, 2) . "ms");

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
$passwordStart = microtime(true);
if ($user && password_verify($password, $user['password'])) {
    
    $passwordTime = microtime(true) - $passwordStart;
    error_log("Verificação de senha em: " . round($passwordTime * 1000, 2) . "ms");
    
    // Se tudo estiver correto, o login é um sucesso!
    
    // Regenera o ID da sessão para prevenir ataques de "session fixation".
    session_regenerate_id(true);

    // Guarda as informações importantes do usuário na sessão.
    $_SESSION['user_id'] = $user['id'];       // O ID é útil para várias operações.
    $_SESSION['user'] = $user['username']; // O nome de usuário para saudação.
    $_SESSION['role'] = $user['role'];       // A 'role' para controle de acesso ('admin' ou 'user').
    
    // 4. PROCESSAR "LEMBRE-ME" SE SOLICITADO
    // ----------------------------------------------------
    if ($remember_me) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Criar token de "Lembre-me"
        $token = createRememberToken($user['id'], $user_agent, $ip_address);
        
        if ($token) {
            // Definir cookie seguro
            setRememberMeCookie($token, 30); // 30 dias
            error_log("Token 'Lembre-me' criado para usuário: " . $user['username']);
        } else {
            error_log("Erro ao criar token 'Lembre-me' para usuário: " . $user['username']);
        }
    }
    
    $totalTime = microtime(true) - $startTime;
    error_log("Login bem-sucedido em: " . round($totalTime * 1000, 2) . "ms");
    
    // Redireciona o usuário com base na sua 'role'.
    if ($user['role'] === 'admin') {
        header('Location: admin_painel.php');
    } else {
        header('Location: index.php'); // Ou outra página para usuários comuns
    }
    exit();

} else {
    $passwordTime = microtime(true) - $passwordStart;
    $totalTime = microtime(true) - $startTime;
    error_log("Login falhou em: " . round($totalTime * 1000, 2) . "ms (senha: " . round($passwordTime * 1000, 2) . "ms)");
    
    // Se o usuário não foi encontrado OU a senha está incorreta, o login falha.
    // Redireciona de volta para a página de login com um erro genérico para não dar pistas a invasores.
    header('Location: login.php?error=invalid');
    exit();
}
?>