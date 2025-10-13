<?php
session_start();
require 'config.php';

// CORREÇÃO 2: Verificação de role padronizada e segura
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Pega e valida o ID do usuário da URL
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de usuário inválido.");
}

// CORREÇÃO 1 (CSRF): Gera o token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// MELHORIA 1: Busca o usuário ANTES de processar o formulário
try {
    // MELHORIA: Otimiza a query
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Usuário não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar usuário.");
}

$erro = '';

// Processa o formulário APÓS buscar os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CORREÇÃO 1 (CSRF): Valida o token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Falha na validação CSRF.');
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // MELHORIA 2: Validações mais robustas
    if ($username === '') {
        $erro = "O nome de usuário não pode ficar vazio.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $erro = "Formato de nome de usuário inválido.";
    } elseif ($password !== '' && strlen($password) < 8) {
        $erro = "A nova senha deve ter no mínimo 8 caracteres.";
    }

    // Se não houver erros de validação, atualiza o banco
    if ($erro === '') {
        try {
            if ($password !== '') {
                // Se uma nova senha foi fornecida, atualiza tudo
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $hash, $id]);
            } else {
                // Se não, atualiza apenas o username
                $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$username, $id]);
            }
            // Redireciona com mensagem de sucesso
            header("Location: admin_painel.php?msg=user_updated");
            exit();
        } catch (PDOException $e) {
            // Trata o caso de username duplicado
            if ($e->getCode() == 23000) {
                 $erro = "Este nome de usuário já está em uso por outra conta.";
            } else {
                 $erro = "Erro ao atualizar o usuário.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Editar Usuário | HELMER</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white flex items-center justify-center min-h-screen p-4">

<div class="bg-gray-800/80 backdrop-blur-sm p-8 rounded-2xl w-full max-w-md shadow-2xl">
    <h2 class="text-2xl text-white font-extrabold mb-6 text-center">Editar Usuário</h2>

    <?php if (!empty($erro)): ?>
        <div class="mb-4 p-3 bg-red-500/20 text-red-300 border border-red-500 rounded-lg text-center"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div>
            <label for="username" class="block mb-1 text-sm font-medium text-gray-400">Nome de Usuário</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required
                   class="w-full p-3 bg-gray-700 rounded-lg border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500 transition">
        </div>

        <div>
            <label for="password" class="block mb-1 text-sm font-medium text-gray-400">Nova Senha</label>
            <input type="password" id="password" name="password" placeholder="Deixe em branco para não alterar"
                   class="w-full p-3 bg-gray-700 rounded-lg border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500 transition">
            <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres, se for alterar.</p>
        </div>
        
        <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 p-3 rounded-lg font-bold text-lg transition">Salvar Alterações</button>
    </form>
    
    <a href="admin_painel.php" class="text-sm text-gray-400 hover:underline mt-4 block text-center">Voltar ao Painel</a>
</div>

</body>
</html>