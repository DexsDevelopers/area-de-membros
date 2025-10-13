<?php
// Lógica para exibir mensagens de erro vindas do script de processamento
$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid':
            $errorMessage = 'E-mail, usuário ou senha inválidos.';
            break;
        case 'empty':
            $errorMessage = 'Por favor, preencha todos os campos.';
            break;
        default:
            $errorMessage = 'Ocorreu um erro. Tente novamente.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn { animation: fadeIn 0.8s ease-out; }
    </style>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white font-sans flex items-center justify-center min-h-screen p-4">

<div x-data="{ loading: false }" class="backdrop-blur-md bg-white/10 p-8 rounded-2xl shadow-2xl w-full max-w-md animate-fadeIn">
    <div class="flex justify-center mb-6">
        <span class="text-xl font-extrabold tracking-widest text-center text-red-500">HELMER ACADEMY</span>
    </div>
    
    <?php if ($errorMessage): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6 text-center text-sm">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <form action="processa_login.php" method="POST" class="space-y-6" @submit="loading = true">
        <div>
            <label for="username" class="block mb-2 text-sm text-gray-300">E-mail ou usuário</label>
            <input type="text" id="username" name="username" required autocomplete="username"
                   class="w-full p-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 transition">
        </div>
        <div>
            <label for="password" class="block mb-2 text-sm text-gray-300">Senha</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   class="w-full p-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 transition">
        </div>

        <div class="flex items-center justify-between">
            <label for="remember" class="flex items-center text-sm cursor-pointer">
                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-red-600 focus:ring-red-500">
                <span class="ml-2 text-gray-300">Lembrar-me</span>
            </label>
            <a href="#" class="text-sm text-red-500 hover:underline">Esqueceu a senha?</a>
        </div>
        
        <button type="submit" :disabled="loading"
                class="w-full bg-red-600 hover:bg-red-700 transition rounded p-3 font-bold text-lg flex items-center justify-center disabled:opacity-70 disabled:cursor-wait">
            <span x-show="!loading">ENTRAR NO JOGO 💀</span>
            <span x-show="loading">Entrando...</span>
        </button>
    </form>
    <p class="text-center text-xs mt-6 text-gray-500">© <?php echo date('Y'); ?> Área restrita só pros malucos que querem faturar sujo</p>
</div>

</body>
</html>