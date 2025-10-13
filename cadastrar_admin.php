<?php
session_start();
require 'config.php';

// Verificação de segurança: Apenas admins logados podem acessar.
// Usar $_SESSION['role'] é mais específico e seguro.
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

// SUGESTÃO 1 (CSRF): Gere o token se ele não existir na sessão.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$feedbackMessage = '';
$feedbackType = 'error';

// Processa o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // SUGESTÃO 1 (CSRF): Valida o token.
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Falha na validação CSRF.');
    }

    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // SUGESTÃO 3 (Validação): Validações mais robustas
    if ($usuario === '' || $senha === '') {
        header('Location: cadastrar_admin.php?error=empty_fields');
        exit();
    }
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $usuario)) {
        header('Location: cadastrar_admin.php?error=invalid_username');
        exit();
    }
    if (strlen($senha) < 8) {
        header('Location: cadastrar_admin.php?error=password_too_short');
        exit();
    }

    try {
        // Verifica se usuário já existe
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE usuario = ?');
        $stmt->execute([$usuario]);
        if ($stmt->fetch()) {
            // SUGESTÃO 2 (UX): Redireciona com erro específico
            header('Location: cadastrar_admin.php?error=user_exists');
            exit();
        }

        // Cria hash seguro da senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Insere no banco
        $stmt = $pdo->prepare('INSERT INTO admins (usuario, senha_hash) VALUES (?, ?)');
        $stmt->execute([$usuario, $senhaHash]);
        
        // Regenera o token após o uso
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Redireciona para o painel principal com mensagem de sucesso
        header('Location: painel.php?msg=admin_added_successfully');
        exit();

    } catch (PDOException $e) {
        // Em caso de erro de banco, redireciona com mensagem genérica
        error_log($e->getMessage()); // Loga o erro real para o desenvolvedor
        header('Location: cadastrar_admin.php?error=database_error');
        exit();
    }
}

// Lógica para exibir mensagens de erro baseadas nos parâmetros da URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'empty_fields':
            $feedbackMessage = 'Preencha todos os campos!';
            break;
        case 'user_exists':
            $feedbackMessage = 'Este nome de usuário já existe.';
            break;
        case 'invalid_username':
            $feedbackMessage = 'Usuário deve ter entre 4-20 caracteres (letras, números e _).';
            break;
        case 'password_too_short':
            $feedbackMessage = 'A senha deve ter no mínimo 8 caracteres.';
            break;
        default:
            $feedbackMessage = 'Ocorreu um erro inesperado.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Administrador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <form method="post" class="p-8 bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-2xl space-y-6">
            <h2 class="text-2xl font-bold text-center text-white">Cadastrar Novo Admin</h2>

            <?php if ($feedbackMessage): ?>
                <div class="p-3 rounded-lg text-center text-sm <?php echo $feedbackType === 'error' ? 'bg-red-500/20 text-red-300' : 'bg-emerald-500/20 text-emerald-300'; ?>">
                    <?php echo htmlspecialchars($feedbackMessage); ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div>
                <label for="usuario" class="block mb-1 text-sm font-medium text-gray-400">Usuário</label>
                <input id="usuario" name="usuario" placeholder="Novo usuário admin" class="w-full p-3 rounded-lg bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500 transition" required>
            </div>
            <div>
                <label for="senha" class="block mb-1 text-sm font-medium text-gray-400">Senha</label>
                <input id="senha" type="password" name="senha" placeholder="Senha forte" class="w-full p-3 rounded-lg bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500 transition" required>
                <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres.</p>
            </div>
            <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 p-3 rounded-lg font-bold text-lg transition">Cadastrar Administrador</button>
        </form>
        <a href="painel.php" class="block w-full text-center mt-4 text-sm text-gray-400 hover:underline">Voltar ao Painel</a>
    </div>
</body>
</html>