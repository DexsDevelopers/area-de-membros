<?php
session_start();
require 'config.php';

// Verificar se é administrador
if (!isset($_SESSION['user']) && !isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Verificar role se estiver usando $_SESSION['user']
if (isset($_SESSION['user']) && ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_banner':
    $titulo = trim($_POST['titulo'] ?? '');
                $link = trim($_POST['link'] ?? '');
                $posicao = $_POST['posicao'] ?? 'top';

                if (empty($titulo)) {
                    $error = 'Título do banner é obrigatório.';
    } else {
                    // Upload da imagem
                    $imagem = '';
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/banners/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = uniqid('banner_', true) . '.' . $fileExtension;
                            $uploadPath = $uploadDir . $newFileName;
                            
                            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadPath)) {
                                $imagem = $uploadPath;
                            }
                        }
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO banners (titulo, link, imagem, posicao, ativo, data_criacao) 
                        VALUES (?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$titulo, $link, $imagem, $posicao]);
                    $message = 'Banner adicionado com sucesso!';
                }
                break;
                
            case 'edit_banner':
                $id = intval($_POST['id'] ?? 0);
                $titulo = trim($_POST['titulo'] ?? '');
                $link = trim($_POST['link'] ?? '');
                $posicao = $_POST['posicao'] ?? 'top';
                
                if (empty($titulo) || $id <= 0) {
                    $error = 'Dados inválidos.';
        } else {
                    // Upload da nova imagem se fornecida
                    $imagem = $_POST['imagem_atual'] ?? '';
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/banners/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = uniqid('banner_', true) . '.' . $fileExtension;
                            $uploadPath = $uploadDir . $newFileName;
                            
                            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadPath)) {
                                // Deletar imagem antiga
                                if (!empty($imagem) && file_exists($imagem)) {
                                    unlink($imagem);
                                }
                                $imagem = $uploadPath;
                            }
                        }
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE banners 
                        SET titulo = ?, link = ?, imagem = ?, posicao = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$titulo, $link, $imagem, $posicao, $id]);
                    $message = 'Banner atualizado com sucesso!';
                }
                break;
                
            case 'delete_banner':
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    // Buscar imagem para deletar
                    $stmt = $pdo->prepare("SELECT imagem FROM banners WHERE id = ?");
    $stmt->execute([$id]);
                    $banner = $stmt->fetch();
                    
                    if ($banner && !empty($banner['imagem']) && file_exists($banner['imagem'])) {
                        unlink($banner['imagem']);
                    }
                    
    $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->execute([$id]);
                    $message = 'Banner excluído com sucesso!';
                }
                break;
                
            case 'toggle_status':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? 'active';
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE banners SET ativo = ? WHERE id = ?");
                    $stmt->execute([$status === 'active' ? 1 : 0, $id]);
                    $message = 'Status do banner atualizado!';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}

// Função para executar consulta com segurança
function safeQuery($pdo, $sql, $default = 0) {
    try {
        $stmt = $pdo->query($sql);
        if ($stmt) {
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : $default;
        }
        return $default;
    } catch (Exception $e) {
        error_log("Erro na consulta: " . $e->getMessage() . " - SQL: " . $sql);
        return $default;
    }
}

// Função para executar consulta que retorna array
function safeQueryArray($pdo, $sql, $params = [], $default = []) {
    try {
        $stmt = $pdo->prepare($sql);
        if ($stmt && $stmt->execute($params)) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result ? $result : $default;
        }
        return $default;
    } catch (Exception $e) {
        error_log("Erro na consulta: " . $e->getMessage() . " - SQL: " . $sql);
        return $default;
    }
}

// Buscar banners
$banners = safeQueryArray($pdo, "
    SELECT * FROM banners 
    ORDER BY data_criacao DESC
");

// Estatísticas com tratamento de erro
$stats = [
    'total_banners' => safeQuery($pdo, "SELECT COUNT(*) FROM banners"),
    'banners_ativos' => safeQuery($pdo, "SELECT COUNT(*) FROM banners WHERE ativo = 1"),
    'banners_top' => safeQuery($pdo, "SELECT COUNT(*) FROM banners WHERE posicao = 'top' AND ativo = 1"),
    'banners_sidebar' => safeQuery($pdo, "SELECT COUNT(*) FROM banners WHERE posicao = 'sidebar' AND ativo = 1")
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Banners | HELMER ACADEMY</title>
<script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #000000 100%);
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease-out forwards;
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in:nth-child(1) { animation-delay: 0.1s; }
        .fade-in:nth-child(2) { animation-delay: 0.2s; }
        .fade-in:nth-child(3) { animation-delay: 0.3s; }
        .fade-in:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body class="gradient-bg text-white font-sans min-h-screen">

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false, modalOpen: false, editModalOpen: false, deleteModalOpen: false, selectedBanner: null }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-black/90 backdrop-blur-lg border-r border-gray-800 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-images text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white">BANNERS</span>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="admin_dashboard_moderno.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-tachometer-alt mr-3"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="admin_painel_moderno.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-users mr-3"></i>
                <span>Usuários</span>
            </a>
            
            <a href="cursos_moderno.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-graduation-cap mr-3"></i>
                <span>Cursos</span>
            </a>
            
            <a href="produtos_moderno.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-shopping-bag mr-3"></i>
                <span>Produtos</span>
            </a>
            
            <a href="gerenciar_categorias.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-tags mr-3"></i>
                <span>Categorias</span>
            </a>
            
            <a href="gerenciar_banners.php" class="flex items-center px-4 py-3 text-white bg-red-600/20 rounded-lg border border-red-600/30">
                <i class="fas fa-images mr-3"></i>
                <span>Banners</span>
            </a>
            
            <a href="relatorios.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-chart-line mr-3"></i>
                <span>Relatórios</span>
            </a>
            
            <a href="configuracoes.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-cog mr-3"></i>
                <span>Configurações</span>
            </a>
        </nav>
    </div>
    
    <!-- Overlay para mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden lg:ml-0">
        <!-- Header -->
        <header class="bg-black/50 backdrop-blur-lg border-b border-gray-800 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-400 hover:text-white">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Gerenciar Banners</h1>
                        <p class="text-gray-400 text-sm">Gerencie banners promocionais do site</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Novo Banner
                    </button>
                </div>
            </div>
    </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
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
            
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="fade-in card-hover bg-gradient-to-r from-red-600 to-red-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium">Total de Banners</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['total_banners']) ?></p>
                        </div>
                        <i class="fas fa-images text-red-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Banners Ativos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['banners_ativos']) ?></p>
                        </div>
                        <i class="fas fa-check-circle text-blue-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Topo</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['banners_top']) ?></p>
                        </div>
                        <i class="fas fa-arrow-up text-green-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
            <div>
                            <p class="text-purple-100 text-sm font-medium">Sidebar</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['banners_sidebar']) ?></p>
                        </div>
                        <i class="fas fa-bars text-purple-300 text-3xl"></i>
                    </div>
            </div>
    </div>

            <!-- Grid de Banners -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($banners as $banner): ?>
                <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl overflow-hidden border border-gray-700">
                    <div class="relative">
                        <?php if (!empty($banner['imagem'])): ?>
                        <img src="/<?= htmlspecialchars($banner['imagem']) ?>" alt="<?= htmlspecialchars($banner['titulo']) ?>" 
                             class="w-full h-48 object-cover">
                        <?php else: ?>
                        <div class="w-full h-48 bg-gray-700 flex items-center justify-center">
                            <i class="fas fa-image text-4xl text-gray-500"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-4 left-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $banner['posicao'] === 'top' ? 'bg-blue-600 text-white' : 'bg-green-600 text-white' ?>">
                                <?= ucfirst($banner['posicao']) ?>
                            </span>
                        </div>
                        
                        <div class="absolute top-4 right-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $banner['ativo'] ? 'bg-green-600 text-white' : 'bg-gray-600 text-white' ?>">
                                <?= $banner['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-white mb-2"><?= htmlspecialchars($banner['titulo']) ?></h3>
                        
                        <?php if (!empty($banner['link'])): ?>
                        <p class="text-sm text-gray-400 mb-2">
                            <i class="fas fa-link mr-1"></i>
                            <a href="<?= htmlspecialchars($banner['link']) ?>" target="_blank" class="text-blue-400 hover:text-blue-300">
                                <?= htmlspecialchars($banner['link']) ?>
                            </a>
                        </p>
            <?php endif; ?>
                        
                        <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                            <span>Criado em <?= date('d/m/Y', strtotime($banner['data_criacao'])) ?></span>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button @click="selectedBanner = <?= json_encode($banner) ?>; editModalOpen = true" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-edit mr-1"></i>Editar
                            </button>
                            
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                                <input type="hidden" name="status" value="<?= $banner['ativo'] ? 'inactive' : 'active' ?>">
                                <button type="submit" class="px-3 py-2 <?= $banner['ativo'] ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' ?> text-white rounded-lg text-sm font-semibold transition">
                                    <i class="fas <?= $banner['ativo'] ? 'fa-pause' : 'fa-play' ?> mr-1"></i>
                                    <?= $banner['ativo'] ? 'Desativar' : 'Ativar' ?>
    </button>
</form>
                            
                            <button @click="selectedBanner = <?= $banner['id'] ?>; deleteModalOpen = true" 
                                    class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-trash mr-1"></i>Excluir
                            </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
            </div>
            
            <?php if (empty($banners)): ?>
            <div class="text-center py-12">
                <i class="fas fa-images text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-400 mb-2">Nenhum banner encontrado</h3>
                <p class="text-gray-500 mb-6">Comece criando seu primeiro banner</p>
                <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Criar Primeiro Banner
                </button>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal de Novo Banner -->
    <div x-show="modalOpen" @click.away="modalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl">
            <h3 class="text-lg font-semibold text-white mb-4">Novo Banner</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_banner">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Título do Banner</label>
                        <input type="text" name="titulo" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Link (opcional)</label>
                        <input type="url" name="link" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                               placeholder="https://exemplo.com">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Posição</label>
                        <select name="posicao" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="top">Topo da página</option>
                            <option value="sidebar">Barra lateral</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Imagem do Banner</label>
                        <input type="file" name="imagem" accept="image/*" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        <p class="text-xs text-gray-400 mt-1">Recomendado: 1200x400px para topo, 300x600px para sidebar</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="modalOpen = false" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Criar Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Editar Banner -->
    <div x-show="editModalOpen" @click.away="editModalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl">
            <h3 class="text-lg font-semibold text-white mb-4">Editar Banner</h3>
            <form method="POST" enctype="multipart/form-data" x-show="selectedBanner">
                <input type="hidden" name="action" value="edit_banner">
                <input type="hidden" name="id" :value="selectedBanner?.id">
                <input type="hidden" name="imagem_atual" :value="selectedBanner?.imagem">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Título do Banner</label>
                        <input type="text" name="titulo" :value="selectedBanner?.titulo" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Link (opcional)</label>
                        <input type="url" name="link" :value="selectedBanner?.link" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Posição</label>
                        <select name="posicao" :value="selectedBanner?.posicao" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="top">Topo da página</option>
                            <option value="sidebar">Barra lateral</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nova Imagem (opcional)</label>
                        <input type="file" name="imagem" accept="image/*" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        <p class="text-xs text-gray-400 mt-1">Deixe em branco para manter a imagem atual</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="editModalOpen = false" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Exclusão -->
    <div x-show="deleteModalOpen" @click.away="deleteModalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-white mb-4">Confirmar Exclusão</h3>
            <p class="text-gray-300 mb-6">Tem certeza que deseja excluir este banner? Esta ação não pode ser desfeita.</p>
            
            <div class="flex justify-end space-x-3">
                <button @click="deleteModalOpen = false" 
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Cancelar
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="delete_banner">
                    <input type="hidden" name="id" :value="selectedBanner">
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Animações
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
</script>

</body>
</html>