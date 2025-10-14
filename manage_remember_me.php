<?php
/**
 * GERENCIAR TOKENS "LEMBRE-ME"
 * HELMER ACADEMY - HOSTINGER
 * 
 * Página para o usuário gerenciar seus tokens de "Lembre-me"
 */

session_start();

// Incluir funções do sistema "Lembre-me"
require 'remember_me_functions.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user'];

// Processar ações
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'revoke_token':
                $token_id = (int)$_POST['token_id'];
                if (revokeTokenById($token_id, $user_id)) {
                    $message = 'Token revogado com sucesso!';
                } else {
                    $error = 'Erro ao revogar token.';
                }
                break;
                
            case 'revoke_all':
                if (revokeAllUserTokens($user_id)) {
                    $message = 'Todos os tokens foram revogados!';
                } else {
                    $error = 'Erro ao revogar tokens.';
                }
                break;
        }
    }
}

// Buscar tokens do usuário
$tokens = getUserRememberTokens($user_id);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar "Lembre-me" - HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        .gradient-bg {
            background: linear-gradient(135deg, #000000 0%, #1a0000 25%, #2d0000 50%, #1a0000 75%, #000000 100%);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .card-premium {
            background: linear-gradient(145deg, rgba(220,38,38,0.1) 0%, rgba(0,0,0,0.3) 50%, rgba(220,38,38,0.05) 100%);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(220,38,38,0.3);
            box-shadow: 0 8px 32px rgba(220,38,38,0.2);
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #dc2626, #ef4444, #f97316);
            background-size: 200% 200%;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
            transition: all 0.3s ease;
            animation: buttonPulse 2s ease-in-out infinite;
        }
        
        .btn-premium:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.6);
        }
        
        @keyframes buttonPulse {
            0%, 100% { 
                background-position: 0% 50%; 
                box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4); 
            }
            50% { 
                background-position: 100% 50%; 
                box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6); 
            }
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4">
                <i class="fas fa-memory text-red-400 mr-3"></i>
                Gerenciar "Lembre-me"
            </h1>
            <p class="text-gray-400">Gerencie seus dispositivos conectados</p>
        </div>

        <!-- Mensagens -->
        <?php if ($message): ?>
            <div class="bg-green-800/50 border border-green-500/50 text-green-300 px-4 py-3 rounded-xl mb-6 text-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-800/50 border border-red-500/50 text-red-300 px-4 py-3 rounded-xl mb-6 text-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Informações do Usuário -->
        <div class="card-premium p-6 rounded-xl mb-8">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-user text-blue-400 mr-2"></i>
                Informações da Conta
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-400">Usuário:</p>
                    <p class="text-white font-semibold"><?php echo htmlspecialchars($username); ?></p>
                </div>
                <div>
                    <p class="text-gray-400">Tokens Ativos:</p>
                    <p class="text-white font-semibold"><?php echo count($tokens); ?></p>
                </div>
            </div>
        </div>

        <!-- Tokens Ativos -->
        <div class="card-premium p-6 rounded-xl mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-key text-green-400 mr-2"></i>
                    Dispositivos Conectados
                </h2>
                <?php if (count($tokens) > 0): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja revogar TODOS os tokens?')">
                        <input type="hidden" name="action" value="revoke_all">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Revogar Todos
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (count($tokens) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($tokens as $token): ?>
                        <div class="bg-gray-800/50 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-gray-400 text-sm">Criado em:</p>
                                            <p class="text-white"><?php echo date('d/m/Y H:i', strtotime($token['created_at'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm">Último uso:</p>
                                            <p class="text-white">
                                                <?php 
                                                if ($token['last_used_at']) {
                                                    echo date('d/m/Y H:i', strtotime($token['last_used_at']));
                                                } else {
                                                    echo 'Nunca usado';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm">Expira em:</p>
                                            <p class="text-white"><?php echo date('d/m/Y H:i', strtotime($token['expires_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($token['user_agent']): ?>
                                        <div class="mt-2">
                                            <p class="text-gray-400 text-sm">Dispositivo:</p>
                                            <p class="text-white text-sm"><?php echo htmlspecialchars($token['user_agent']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($token['ip_address']): ?>
                                        <div class="mt-1">
                                            <p class="text-gray-400 text-sm">IP:</p>
                                            <p class="text-white text-sm"><?php echo htmlspecialchars($token['ip_address']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="ml-4">
                                    <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja revogar este token?')">
                                        <input type="hidden" name="action" value="revoke_token">
                                        <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                                            <i class="fas fa-times mr-1"></i>
                                            Revogar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-key text-4xl text-gray-500 mb-4"></i>
                    <p class="text-gray-500 text-lg">Nenhum dispositivo conectado</p>
                    <p class="text-gray-600 text-sm">Use a opção "Lembre-me" no login para conectar dispositivos</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Informações de Segurança -->
        <div class="card-premium p-6 rounded-xl mb-8">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-shield-alt text-yellow-400 mr-2"></i>
                Informações de Segurança
            </h2>
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-blue-400 mt-1"></i>
                    <div>
                        <p class="text-white font-semibold">O que são tokens "Lembre-me"?</p>
                        <p class="text-gray-400 text-sm">Tokens seguros que permitem acesso automático sem precisar digitar usuário e senha.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <i class="fas fa-clock text-green-400 mt-1"></i>
                    <div>
                        <p class="text-white font-semibold">Expiração automática</p>
                        <p class="text-gray-400 text-sm">Todos os tokens expiram automaticamente em 30 dias por segurança.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <i class="fas fa-lock text-red-400 mt-1"></i>
                    <div>
                        <p class="text-white font-semibold">Revogação segura</p>
                        <p class="text-gray-400 text-sm">Você pode revogar qualquer token a qualquer momento para maior segurança.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação -->
        <div class="text-center">
            <a href="index.php" class="btn-premium text-white font-bold px-8 py-4 rounded-xl transition-all duration-300 inline-flex items-center">
                <i class="fas fa-home mr-2"></i>
                Voltar ao Início
            </a>
        </div>
    </div>
</body>
</html>
