<?php
session_start();
require 'config.php';

// Gerar token CSRF específico para registro
if (empty($_SESSION['csrf_token_register'])) {
    $_SESSION['csrf_token_register'] = bin2hex(random_bytes(32));
}

// Mensagens de feedback simples via querystring
$msg = '';
$type = 'info';
if (isset($_GET['error'])) {
    $type = 'error';
    switch ($_GET['error']) {
        case 'invalid_csrf':
            $msg = 'Sessão expirada. Atualize a página e tente novamente.';
            break;
        case 'invalid_username':
            $msg = 'Usuário inválido. Use 3 a 30 caracteres (letras, números, _ . -).';
            break;
        case 'password_short':
            $msg = 'A senha deve ter no mínimo 8 caracteres.';
            break;
        case 'password_mismatch':
            $msg = 'As senhas não coincidem.';
            break;
        case 'username_exists':
            $msg = 'Este usuário já está em uso. Escolha outro.';
            break;
        default:
            $msg = 'Não foi possível concluir o cadastro.';
    }
} elseif (isset($_GET['success'])) {
    $type = 'success';
    $msg = 'Cadastro realizado com sucesso!';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px);} to { opacity: 1; transform: translateY(0);} }
        .animate-fadeIn { animation: fadeIn .6s ease-out; }
        .glass { background: rgba(255,255,255,.08); backdrop-filter: blur(12px); border:1px solid rgba(255,255,255,.15); }
    </style>
    </head>
<body class="bg-gradient-to-br from-gray-900 via-black to-red-900 text-white min-h-screen flex items-center justify-center p-4">

<div x-data="{ loading: false, showPassword: false }" class="w-full max-w-md animate-fadeIn">
    <div class="glass rounded-2xl p-6 sm:p-8 shadow-2xl">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-500 to-red-700 rounded-full mb-4">
                <i class="fas fa-user-plus text-2xl text-white"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold">Criar conta</h1>
            <p class="text-gray-400 text-sm mt-1">Registre-se para acessar os conteúdos</p>
        </div>

        <?php if ($msg): ?>
            <div class="mb-4 px-4 py-3 rounded-xl text-sm <?= $type==='success' ? 'bg-green-500/20 border border-green-500 text-green-300' : ($type==='error' ? 'bg-red-500/20 border border-red-500 text-red-300' : 'bg-blue-500/20 border border-blue-500 text-blue-300') ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form action="processa_registro.php" method="POST" class="space-y-5" @submit="loading = true">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token_register']) ?>">

            <div>
                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Usuário</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="30"
                       placeholder="seu_usuario" autocomplete="username"
                       class="w-full p-4 rounded-xl bg-gray-800/60 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                <p class="text-xs text-gray-500 mt-1">Use letras, números, ponto, hífen ou sublinhado.</p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Senha</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required minlength="8" autocomplete="new-password"
                           class="w-full p-4 pr-12 rounded-xl bg-gray-800/60 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                           placeholder="mínimo 8 caracteres">
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div>
                <label for="confirm" class="block text-sm font-medium text-gray-300 mb-2">Confirmar senha</label>
                <input type="password" id="confirm" name="confirm" required minlength="8" autocomplete="new-password"
                       class="w-full p-4 rounded-xl bg-gray-800/60 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>

            <button type="submit" :disabled="loading"
                    class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-4 px-6 rounded-xl transition disabled:opacity-70 disabled:cursor-wait">
                <span x-show="!loading">Criar conta</span>
                <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Registrando...</span>
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-400">
            Já tem conta? <a href="login.php" class="text-red-400 hover:text-red-300 font-semibold">Entrar</a>
        </div>
    </div>
</div>

</body>
</html>


