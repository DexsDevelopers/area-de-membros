<?php
/**
 * Script para limpar cache
 * Acesso apenas para administradores
 */

session_start();
require 'config.php';

// Verificar se é administrador
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'cache.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'clear_all':
                $cache->clear();
                $message = 'Todo o cache foi limpo com sucesso!';
                break;
                
            case 'clear_cursos':
                $cache->delete('cursos_categoria_0');
                $cache->delete('cursos_categoria_1');
                $cache->delete('cursos_categoria_2');
                $cache->delete('cursos_categoria_3');
                $cache->delete('cursos_categoria_4');
                $cache->delete('cursos_categoria_5');
                $message = 'Cache de cursos foi limpo com sucesso!';
                break;
                
            case 'clear_produtos':
                $cache->delete('produtos_ativos');
                $message = 'Cache de produtos foi limpo com sucesso!';
                break;
                
            case 'clear_categorias':
                $cache->delete('categorias_menu');
                $cache->delete('categorias_busca');
                $message = 'Cache de categorias foi limpo com sucesso!';
                break;
                
            case 'clear_banners':
                $cache->delete('banners_ativos');
                $message = 'Cache de banners foi limpo com sucesso!';
                break;
                
            default:
                $error = 'Ação inválida!';
        }
    } catch (Exception $e) {
        $error = 'Erro ao limpar cache: ' . $e->getMessage();
    }
}

// Verificar status do cache
$cache_files = glob('cache/*.cache');
$cache_size = 0;
foreach ($cache_files as $file) {
    $cache_size += filesize($file);
}
$cache_size_mb = round($cache_size / 1024 / 1024, 2);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cache | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white font-sans">

<div class="min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Gerenciar Cache</h1>
            <p class="text-gray-400">Gerencie o cache do sistema para melhorar a performance</p>
        </div>
        
        <?php if ($message): ?>
        <div class="bg-green-600/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="bg-red-600/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <!-- Status do Cache -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-red-400">
                <i class="fas fa-chart-pie mr-2"></i>Status do Cache
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-700/50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-blue-400"><?= count($cache_files) ?></div>
                    <div class="text-sm text-gray-400">Arquivos em Cache</div>
                </div>
                <div class="bg-gray-700/50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-green-400"><?= $cache_size_mb ?> MB</div>
                    <div class="text-sm text-gray-400">Tamanho Total</div>
                </div>
                <div class="bg-gray-700/50 rounded-lg p-4">
                    <div class="text-2xl font-bold text-yellow-400">
                        <?= $cache_size > 0 ? 'Ativo' : 'Vazio' ?>
                    </div>
                    <div class="text-sm text-gray-400">Status</div>
                </div>
            </div>
        </div>
        
        <!-- Ações do Cache -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Limpar Todo Cache -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4 text-red-400">
                    <i class="fas fa-trash-alt mr-2"></i>Limpar Todo Cache
                </h3>
                <p class="text-gray-400 text-sm mb-4">
                    Remove todos os arquivos de cache do sistema. Isso pode causar lentidão temporária.
                </p>
                <form method="POST" onsubmit="return confirm('Tem certeza que deseja limpar todo o cache?')">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition">
                        <i class="fas fa-broom mr-2"></i>Limpar Tudo
                    </button>
                </form>
            </div>
            
            <!-- Limpar Cache de Cursos -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4 text-blue-400">
                    <i class="fas fa-graduation-cap mr-2"></i>Cache de Cursos
                </h3>
                <p class="text-gray-400 text-sm mb-4">
                    Limpa apenas o cache relacionado aos cursos e suas categorias.
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_cursos">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition">
                        <i class="fas fa-refresh mr-2"></i>Limpar Cursos
                    </button>
                </form>
            </div>
            
            <!-- Limpar Cache de Produtos -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4 text-green-400">
                    <i class="fas fa-shopping-bag mr-2"></i>Cache de Produtos
                </h3>
                <p class="text-gray-400 text-sm mb-4">
                    Limpa apenas o cache relacionado aos produtos digitais.
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_produtos">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition">
                        <i class="fas fa-refresh mr-2"></i>Limpar Produtos
                    </button>
                </form>
            </div>
            
            <!-- Limpar Cache de Categorias -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4 text-purple-400">
                    <i class="fas fa-tags mr-2"></i>Cache de Categorias
                </h3>
                <p class="text-gray-400 text-sm mb-4">
                    Limpa o cache das categorias de cursos.
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_categorias">
                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition">
                        <i class="fas fa-refresh mr-2"></i>Limpar Categorias
                    </button>
                </form>
            </div>
            
            <!-- Limpar Cache de Banners -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4 text-yellow-400">
                    <i class="fas fa-images mr-2"></i>Cache de Banners
                </h3>
                <p class="text-gray-400 text-sm mb-4">
                    Limpa o cache dos banners do carrossel.
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_banners">
                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 px-4 rounded-lg transition">
                        <i class="fas fa-refresh mr-2"></i>Limpar Banners
                    </button>
                </form>
            </div>
            
            <!-- Voltar ao Painel -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4 text-gray-400">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                </h3>
                <p class="text-gray-400 text-sm mb-4">
                    Retorna ao painel administrativo.
                </p>
                <a href="admin_painel.php" class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded-lg transition text-center">
                    <i class="fas fa-home mr-2"></i>Painel Admin
                </a>
            </div>
        </div>
        
        <!-- Informações Adicionais -->
        <div class="mt-8 bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6">
            <h3 class="text-lg font-bold mb-4 text-gray-300">
                <i class="fas fa-info-circle mr-2"></i>Informações sobre o Cache
            </h3>
            <div class="text-sm text-gray-400 space-y-2">
                <p>• O cache melhora significativamente a performance do site</p>
                <p>• Cursos: Cache de 5 minutos</p>
                <p>• Produtos: Cache de 10 minutos</p>
                <p>• Categorias: Cache de 30 minutos</p>
                <p>• Banners: Cache de 15 minutos</p>
                <p>• Limpar o cache pode causar lentidão temporária</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
