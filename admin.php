<?php
// Lógica para exibir mensagens de erro vindas do script de login
$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $errorMessage = 'Usuário ou senha inválidos.';
            break;
        case 'empty_fields':
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
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white font-sans flex items-center justify-center min-h-screen p-4">

<div x-data="{ loading: false }" class="backdrop-blur-md bg-white/10 p-8 rounded-2xl shadow-2xl w-full max-w-md animate-fadeIn">
    
    <h2 class="text-2xl font-extrabold text-center text-red-500 mb-6">HELMER LOGIN 💀</h2>
    
    <?php if ($errorMessage): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4 text-center">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <form action="area-restrita.php" method="POST" class="space-y-6" @submit="loading = true">
        <div>
            <label for="username" class="sr-only">Usuário</label>
            <input type="text" id="username" name="username" placeholder="Usuário" required 
                   autocomplete="username"
                   class="w-full p-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 transition">
        </div>
        <div>
            <label for="password" class="sr-only">Senha</label>
            <input type="password" id="password" name="password" placeholder="Senha" required 
                   autocomplete="current-password"
                   class="w-full p-3 rounded-lg bg-gray-800 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 transition">
        </div>
        
        <button type="submit" :disabled="loading"
                class="w-full bg-red-600 hover:bg-red-700 p-3 rounded-lg font-bold text-lg flex items-center justify-center transition-all duration-300 disabled:opacity-70 disabled:cursor-wait">
            <span x-show="!loading">ENTRAR NO JOGO 🔥</span>
            <span x-show="loading">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Entrando...
            </span>
        </button>
    </form>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn { animation: fadeIn 0.8s ease-out; }
</style>

</body>
</html>