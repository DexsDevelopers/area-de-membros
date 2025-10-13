<?php
session_start();
require 'config.php';

// Verificação de segurança (está perfeita como estava)
if(!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin'){
    header("Location: login.php");
    exit();
}

// SUGESTÃO 1: Gerar um token CSRF para proteger o formulário
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// SUGESTÃO 2: Lógica para exibir mensagens de feedback (erros, sucesso)
$feedbackMessage = '';
$feedbackType = 'error'; // 'error' ou 'success'
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'username_exists':
            $feedbackMessage = 'Este nome de usuário já está em uso.';
            break;
        case 'invalid_username':
            $feedbackMessage = 'Formato de nome de usuário inválido.';
            break;
        case 'password_too_short':
            $feedbackMessage = 'A senha deve ter no mínimo 8 caracteres.';
            break;
        default:
            $feedbackMessage = 'Ocorreu um erro desconhecido.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Cadastrar Novo Usuário | HELMER</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white min-h-screen flex items-center justify-center p-4">

<div x-data="{ loading: false }" class="w-full max-w-md">
    <div class="bg-gray-800/80 backdrop-blur-sm p-8 rounded-2xl shadow-2xl">
    
        <h1 class="text-2xl font-extrabold text-white text-center mb-6">Cadastrar Novo Usuário</h1>

        <?php if ($feedbackMessage): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4 text-center">
                <?php echo htmlspecialchars($feedbackMessage); ?>
            </div>
        <?php endif; ?>

        <form action="adicionar.php" method="POST" class="space-y-6" @submit="loading = true">
            
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div>
                <label for="username" class="block mb-1 text-sm font-medium text-gray-400">Usuário</label>
                <input type="text" id="username" name="username" required 
                       class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500 transition">
            </div>
            
            <div>
                <label for="password" class="block mb-1 text-sm font-medium text-gray-400">Senha</label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                       class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500 transition">
                <p class="text-xs text-gray-500 mt-1">Mínimo de 8 caracteres.</p>
            </div>
            
            <button type="submit" :disabled="loading"
                    class="w-full bg-rose-600 hover:bg-rose-700 p-3 rounded-lg font-bold text-lg flex items-center justify-center transition disabled:opacity-70 disabled:cursor-wait">
                <span x-show="!loading">Cadastrar</span>
                <span x-show="loading">Cadastrando...</span>
            </button>
        </form>
    </div>
    <a href="admin_painel.php" class="block w-full text-center mt-4 text-sm text-gray-400 hover:underline">Voltar ao Painel</a>
</div>

</body>
</html>