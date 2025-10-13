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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn { 
            animation: fadeIn 0.6s ease-out; 
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #ef4444, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .input-focus:focus {
            transform: scale(1.01);
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.3);
        }
        
        .btn-hover:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }
        
        /* Garantir que elementos interativos funcionem */
        input, button, a, select, textarea {
            pointer-events: auto !important;
            cursor: pointer !important;
            user-select: auto !important;
        }
        
        /* Garantir que o formulário seja clicável */
        form {
            pointer-events: auto !important;
            position: relative;
            z-index: 10;
        }
        
        /* Responsividade melhorada */
        @media (max-width: 640px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-black to-red-900 text-white font-sans flex items-center justify-center min-h-screen p-4">
    
    <!-- Container principal -->
    <div x-data="{ 
        loading: false, 
        showPassword: false,
        rememberMe: false 
    }" class="w-full max-w-md">
        
        <!-- Card de login -->
        <div class="glass-effect rounded-2xl shadow-2xl p-6 sm:p-8 animate-fadeIn login-container">
            
            <!-- Logo e título -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-500 to-red-700 rounded-full mb-4">
                    <i class="fas fa-graduation-cap text-2xl text-white"></i>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold gradient-text mb-2">HELMER ACADEMY</h1>
                <p class="text-gray-400 text-sm">Entre na sua conta para continuar</p>
            </div>
            
            <!-- Mensagem de erro -->
            <?php if ($errorMessage): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-xl mb-6 text-center text-sm backdrop-blur-sm animate-fadeInUp">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de login -->
            <form action="processa_login.php" method="POST" class="space-y-6" @submit="loading = true">
                
                <!-- Campo de usuário -->
                <div class="space-y-2">
                    <label for="username" class="block text-sm font-medium text-gray-300">
                        <i class="fas fa-user mr-2"></i>E-mail ou usuário
                    </label>
                    <div class="relative">
                        <input type="text" id="username" name="username" required autocomplete="username"
                               class="w-full p-4 pl-12 rounded-xl bg-gray-800/50 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 input-focus"
                               placeholder="Digite seu e-mail ou usuário">
                        <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Campo de senha -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-gray-300">
                        <i class="fas fa-lock mr-2"></i>Senha
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                               class="w-full p-4 pl-12 pr-12 rounded-xl bg-gray-800/50 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 input-focus"
                               placeholder="Digite sua senha">
                        <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <button type="button" @click="showPassword = !showPassword" 
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Opções adicionais -->
                <div class="flex items-center justify-between">
                    <label for="remember" class="flex items-center text-sm cursor-pointer group">
                        <input type="checkbox" id="remember" name="remember" x-model="rememberMe"
                               class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-red-600 focus:ring-red-500 focus:ring-2">
                        <span class="ml-2 text-gray-300 group-hover:text-white transition-colors">Lembrar-me</span>
                    </label>
                    <a href="#" class="text-sm text-red-400 hover:text-red-300 hover:underline transition-colors">
                        Esqueceu a senha?
                    </a>
                </div>
                
                <!-- Botão de login -->
                <button type="submit" :disabled="loading"
                        class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 btn-hover disabled:opacity-70 disabled:cursor-wait relative overflow-hidden group">
                    <div class="shimmer absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <span x-show="!loading" class="relative z-10 flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        ENTRAR NO JOGO
                        <i class="fas fa-rocket ml-2"></i>
                    </span>
                    <span x-show="loading" class="relative z-10 flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Entrando...
                    </span>
                </button>
            </form>
            
            
            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-500">
                    © <?php echo date('Y'); ?> Helmer Academy. Todos os direitos reservados.
                </p>
                <p class="text-xs text-gray-600 mt-1">
                    Área restrita para membros premium
                </p>
            </div>
        </div>
    </div>

    <!-- Script simplificado -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Login page loaded');
            
            // Efeitos nos campos de input
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            if (usernameInput) {
                usernameInput.addEventListener('focus', function() {
                    this.style.background = 'rgba(239, 68, 68, 0.1)';
                    this.style.borderColor = '#ef4444';
                });
                
                usernameInput.addEventListener('blur', function() {
                    this.style.background = 'rgba(31, 41, 55, 0.5)';
                    this.style.borderColor = '#4b5563';
                });
            }
            
            if (passwordInput) {
                passwordInput.addEventListener('focus', function() {
                    this.style.background = 'rgba(239, 68, 68, 0.1)';
                    this.style.borderColor = '#ef4444';
                });
                
                passwordInput.addEventListener('blur', function() {
                    this.style.background = 'rgba(31, 41, 55, 0.5)';
                    this.style.borderColor = '#4b5563';
                });
            }
        });
    </script>
</body>
</html>