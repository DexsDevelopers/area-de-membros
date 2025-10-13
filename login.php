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
        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.3); }
            50% { box-shadow: 0 0 40px rgba(239, 68, 68, 0.6); }
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .animate-fadeInUp { 
            animation: fadeInUp 1s ease-out; 
        }
        
        .animate-float { 
            animation: float 3s ease-in-out infinite; 
        }
        
        .animate-glow { 
            animation: glow 2s ease-in-out infinite; 
        }
        
        .shimmer {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #ef4444, #f97316, #eab308);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .input-focus:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3);
        }
        
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);
        }
        
        .particle {
            position: absolute;
            background: rgba(239, 68, 68, 0.6);
            border-radius: 50%;
            pointer-events: none;
            animation: float 4s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-black to-red-900 text-white font-sans flex items-center justify-center min-h-screen p-4 relative overflow-hidden">
    
    <!-- Partículas de fundo -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="particle w-2 h-2 top-1/4 left-1/4" style="animation-delay: 0s;"></div>
        <div class="particle w-3 h-3 top-1/3 right-1/4" style="animation-delay: 1s;"></div>
        <div class="particle w-1 h-1 bottom-1/4 left-1/3" style="animation-delay: 2s;"></div>
        <div class="particle w-2 h-2 bottom-1/3 right-1/3" style="animation-delay: 3s;"></div>
    </div>

    <!-- Container principal -->
    <div x-data="{ 
        loading: false, 
        showPassword: false,
        rememberMe: false 
    }" class="relative z-10 w-full max-w-md">
        
        <!-- Card de login com efeito glass -->
        <div class="glass-effect rounded-3xl shadow-2xl p-8 animate-fadeInUp">
            
            <!-- Logo e título -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-500 to-red-700 rounded-full mb-4 animate-glow">
                    <i class="fas fa-graduation-cap text-3xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">HELMER ACADEMY</h1>
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
            
            <!-- Links adicionais -->
            <div class="mt-8 text-center space-y-4">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-600"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-transparent text-gray-400">Ou</span>
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <button class="flex-1 bg-gray-800 hover:bg-gray-700 text-white py-3 px-4 rounded-xl transition-all duration-300 flex items-center justify-center group">
                        <i class="fab fa-google mr-2 group-hover:scale-110 transition-transform"></i>
                        Google
                    </button>
                    <button class="flex-1 bg-gray-800 hover:bg-gray-700 text-white py-3 px-4 rounded-xl transition-all duration-300 flex items-center justify-center group">
                        <i class="fab fa-microsoft mr-2 group-hover:scale-110 transition-transform"></i>
                        Microsoft
                    </button>
                </div>
            </div>
            
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
        
        <!-- Efeito de brilho no fundo -->
        <div class="absolute -inset-1 bg-gradient-to-r from-red-600 via-red-500 to-red-600 rounded-3xl blur opacity-20 animate-glow"></div>
    </div>

    <!-- Script para efeitos visuais -->
    <script>
        // Efeito de partículas interativas
        document.addEventListener('mousemove', function(e) {
            const particles = document.querySelectorAll('.particle');
            particles.forEach((particle, index) => {
                const speed = (index + 1) * 0.5;
                const x = e.clientX * speed / 100;
                const y = e.clientY * speed / 100;
                particle.style.transform = `translate(${x}px, ${y}px)`;
            });
        });
        
        // Efeito de digitação no placeholder
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        usernameInput.addEventListener('focus', function() {
            this.style.background = 'rgba(239, 68, 68, 0.1)';
        });
        
        usernameInput.addEventListener('blur', function() {
            this.style.background = 'rgba(31, 41, 55, 0.5)';
        });
        
        passwordInput.addEventListener('focus', function() {
            this.style.background = 'rgba(239, 68, 68, 0.1)';
        });
        
        passwordInput.addEventListener('blur', function() {
            this.style.background = 'rgba(31, 41, 55, 0.5)';
        });
    </script>
</body>
</html>