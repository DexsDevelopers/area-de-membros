<?php
session_start();
require 'config.php';
require_once 'image_optimizer.php';

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
            case 'add_product':
                $nome = trim($_POST['nome'] ?? '');
                $preco = floatval(str_replace(',', '.', $_POST['preco'] ?? '0'));
                $descricao = trim($_POST['descricao'] ?? '');
                $categoria_id = intval($_POST['categoria_id'] ?? 0);
                $estoque = intval($_POST['estoque'] ?? 0);
                
                if (empty($nome) || $preco <= 0) {
                    $error = 'Nome e preço válido são obrigatórios.';
                } else {
                    // Upload da imagem
                    $imagem = '';
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/produtos/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = uniqid('prod_', true) . '.' . $fileExtension;
                            $uploadPath = $uploadDir . $newFileName;
                            
                            // Usar o otimizador de imagem
                            $optimizer = new ImageOptimizer(800, 600, 85);
                            if ($optimizer->processUpload($_FILES['imagem'], dirname($uploadPath), basename($newFileName))) {
                                $imagem = $uploadPath;
                                
                                // Gerar imagens responsivas
                                $optimizer->generateResponsiveImages(
                                    $uploadPath, 
                                    pathinfo($newFileName, PATHINFO_FILENAME),
                                    [400, 800, 1200]
                                );
                            }
                        }
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO produtos (nome, preco, descricao, categoria_id, estoque, imagem, ativo, data_cadastro) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$nome, $preco, $descricao, $categoria_id, $estoque, $imagem]);
                    $message = 'Produto adicionado com sucesso!';
                }
                break;
                
            case 'edit_product':
                $id = intval($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $preco = floatval(str_replace(',', '.', $_POST['preco'] ?? '0'));
                $descricao = trim($_POST['descricao'] ?? '');
                $categoria_id = intval($_POST['categoria_id'] ?? 0);
                $estoque = intval($_POST['estoque'] ?? 0);
                
                if (empty($nome) || $preco <= 0 || $id <= 0) {
                    $error = 'Dados inválidos.';
                } else {
                    // Upload da nova imagem se fornecida
                    $imagem = $_POST['imagem_atual'] ?? '';
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/produtos/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = uniqid('prod_', true) . '.' . $fileExtension;
                            $uploadPath = $uploadDir . $newFileName;
                            
                            // Usar o otimizador de imagem
                            $optimizer = new ImageOptimizer(800, 600, 85);
                            if ($optimizer->processUpload($_FILES['imagem'], dirname($uploadPath), basename($newFileName))) {
                                // Deletar imagem antiga
                                if (!empty($imagem) && file_exists($imagem)) {
                                    unlink($imagem);
                                }
                                $imagem = $uploadPath;
                                
                                // Gerar imagens responsivas
                                $optimizer->generateResponsiveImages(
                                    $uploadPath, 
                                    pathinfo($newFileName, PATHINFO_FILENAME),
                                    [400, 800, 1200]
                                );
                            }
                        }
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE produtos 
                        SET nome = ?, preco = ?, descricao = ?, categoria_id = ?, estoque = ?, imagem = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$nome, $preco, $descricao, $categoria_id, $estoque, $imagem, $id]);
                    $message = 'Produto atualizado com sucesso!';
                }
                break;
                
            case 'delete_product':
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    // Buscar imagem para deletar
                    $stmt = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
                    $stmt->execute([$id]);
                    $produto = $stmt->fetch();
                    
                    if ($produto && !empty($produto['imagem']) && file_exists($produto['imagem'])) {
                        unlink($produto['imagem']);
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Produto excluído com sucesso!';
                }
                break;
                
            case 'toggle_status':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? 'active';
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE produtos SET ativo = ? WHERE id = ?");
                    $stmt->execute([$status === 'active' ? 1 : 0, $id]);
                    $message = 'Status do produto atualizado!';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}

// Buscar produtos com paginação
$page = intval($_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

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

// Contar total de produtos
$total_produtos = safeQuery($pdo, "SELECT COUNT(*) FROM produtos");
$total_pages = ceil($total_produtos / $limit);

// Buscar produtos
$produtos = safeQueryArray($pdo, "
    SELECT p.*, cat.nome as categoria_nome,
           (SELECT COUNT(*) FROM favoritos WHERE produto_id = p.id) as total_favoritos
    FROM produtos p
    LEFT JOIN categorias cat ON p.categoria_id = cat.id
    ORDER BY p.data_cadastro DESC 
    LIMIT ? OFFSET ?
", [$limit, $offset]);

// Buscar categorias para o formulário
$categorias = safeQueryArray($pdo, "SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC");

// Estatísticas com tratamento de erro
$stats = [
    'total_produtos' => safeQuery($pdo, "SELECT COUNT(*) FROM produtos"),
    'produtos_ativos' => safeQuery($pdo, "SELECT COUNT(*) FROM produtos WHERE ativo = 1"),
    'produtos_estoque' => safeQuery($pdo, "SELECT COUNT(*) FROM produtos WHERE estoque > 0 AND ativo = 1"),
    'valor_total' => safeQuery($pdo, "SELECT SUM(preco) FROM produtos WHERE ativo = 1")
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
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
        
        .hero-gradient {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 25%, #f97316 50%, #dc2626 75%, #ef4444 100%);
            background-size: 200% 200%;
            animation: heroPulse 3s ease infinite;
        }
        
        @keyframes heroPulse {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .card-premium {
            background: linear-gradient(145deg, rgba(220,38,38,0.1) 0%, rgba(0,0,0,0.3) 50%, rgba(220,38,38,0.05) 100%);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(220,38,38,0.3);
            box-shadow: 0 8px 32px rgba(220,38,38,0.2), 0 0 0 1px rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .card-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .card-premium:hover::before {
            left: 100%;
        }
        
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.3), 0 0 30px rgba(220, 38, 38, 0.2);
            border-color: rgba(220, 38, 38, 0.6);
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #dc2626, #ef4444, #f97316);
            background-size: 200% 200%;
            box-shadow: 0 4px 15px rgba(220,38,38,0.4);
            transition: all 0.3s ease;
            animation: buttonPulse 2s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }
        
        .btn-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-premium:hover::before {
            left: 100%;
        }
        
        .btn-premium:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 25px rgba(220,38,38,0.6);
        }
        
        @keyframes buttonPulse {
            0%, 100% { 
                background-position: 0% 50%;
                box-shadow: 0 4px 15px rgba(220,38,38,0.4);
            }
            50% { 
                background-position: 100% 50%;
                box-shadow: 0 6px 20px rgba(220,38,38,0.6);
            }
        }
        
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .fade-in.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .stats-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 35px rgba(220,38,38,0.2);
        }
        
        .dopamine-text {
            background: linear-gradient(45deg, #dc2626, #ef4444, #f97316, #dc2626);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: textShimmer 3s ease-in-out infinite;
        }
        
        @keyframes textShimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(220,38,38,0.8) 0%, rgba(220,38,38,0.2) 50%, transparent 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: particleFloat 6s ease-in-out infinite;
        }
        
        @keyframes particleFloat {
            0%, 100% { 
                transform: translateY(0px) scale(1);
                opacity: 0.7;
            }
            50% { 
                transform: translateY(-20px) scale(1.2);
                opacity: 1;
            }
        }
        
        .glass-effect {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .input-field {
            background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            backdrop-filter: blur(15px);
            border: 1px solid rgba(220,38,38,0.3);
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            border-color: rgba(220,38,38,0.6);
            box-shadow: 0 0 20px rgba(220,38,38,0.3);
            transform: scale(1.02);
        }
        
        .modal-backdrop {
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
        }
        
        .modal-content {
            background: linear-gradient(145deg, rgba(31,41,55,0.9), rgba(17,24,39,0.9));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(220,38,38,0.3);
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen relative overflow-hidden">

<!-- Partículas de fundo -->
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="particle w-2 h-2 top-1/4 left-1/4" style="animation-delay: 0s;"></div>
    <div class="particle w-3 h-3 top-1/3 right-1/4" style="animation-delay: 1s;"></div>
    <div class="particle w-2 h-2 bottom-1/4 left-1/3" style="animation-delay: 2s;"></div>
    <div class="particle w-3 h-3 bottom-1/3 right-1/3" style="animation-delay: 3s;"></div>
</div>

<div class="flex h-screen overflow-hidden relative z-10" x-data="{ sidebarOpen: false, modalOpen: false, editModalOpen: false, deleteModalOpen: false, selectedProduct: null }">
    
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 glass-effect border-r border-white/10 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex items-center justify-between h-16 px-6 border-b border-white/10">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 hero-gradient rounded-xl flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-white text-lg"></i>
                </div>
                <span class="text-xl font-bold text-white">PRODUTOS</span>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="admin_dashboard_moderno.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-300">
                <i class="fas fa-tachometer-alt mr-3"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="admin_painel_moderno.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-300">
                <i class="fas fa-users mr-3"></i>
                <span>Usuários</span>
            </a>
            
            <a href="cursos_moderno.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-300">
                <i class="fas fa-graduation-cap mr-3"></i>
                <span>Cursos</span>
            </a>
            
            <a href="produtos_moderno.php" class="flex items-center px-4 py-3 text-white bg-red-600/20 rounded-xl border border-red-600/30">
                <i class="fas fa-shopping-bag mr-3"></i>
                <span>Produtos</span>
            </a>
            
            <a href="gerenciar_categorias.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-300">
                <i class="fas fa-tags mr-3"></i>
                <span>Categorias</span>
            </a>
            
            <a href="relatorios.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-300">
                <i class="fas fa-chart-line mr-3"></i>
                <span>Relatórios</span>
            </a>
            
            <a href="configuracoes.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-300">
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
        <header class="glass-effect border-b border-white/10 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            <span class="dopamine-text">Gerenciar Produtos</span>
                        </h1>
                        <p class="text-gray-400 text-sm">Crie e gerencie produtos da loja</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="modalOpen = true" class="btn-premium text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Novo Produto</span>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
            <?php if ($message): ?>
            <div class="bg-green-600/20 border border-green-500 text-green-300 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-600/20 border border-red-500 text-red-300 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="fade-in stats-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium">Total de Produtos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['total_produtos']) ?></p>
                        </div>
                        <i class="fas fa-shopping-bag text-red-400 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in stats-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Produtos Ativos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['produtos_ativos']) ?></p>
                        </div>
                        <i class="fas fa-check-circle text-blue-400 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in stats-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Em Estoque</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['produtos_estoque']) ?></p>
                        </div>
                        <i class="fas fa-boxes text-green-400 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in stats-card rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Valor Total</p>
                            <p class="text-3xl font-bold text-white">R$ <?= number_format($stats['valor_total'], 2, ',', '.') ?></p>
                        </div>
                        <i class="fas fa-dollar-sign text-purple-400 text-3xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Grid de Produtos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($produtos as $produto): ?>
                <div class="fade-in card-premium rounded-xl overflow-hidden card-hover">
                    <div class="relative">
                        <?php if (!empty($produto['imagem'])): ?>
                        <img src="/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" 
                             class="w-full h-48 object-cover">
                        <?php else: ?>
                        <div class="w-full h-48 bg-gray-700 flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-4xl text-gray-500"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-4 left-4">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $produto['estoque'] > 0 ? 'bg-green-600 text-white' : 'bg-red-600 text-white' ?>">
                                <?= $produto['estoque'] > 0 ? 'Em Estoque' : 'Sem Estoque' ?>
                            </span>
                        </div>
                        
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $produto['ativo'] ? 'bg-green-600 text-white' : 'bg-gray-600 text-white' ?>">
                                <?= $produto['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-white mb-2"><?= htmlspecialchars($produto['nome']) ?></h3>
                        
                        <?php if ($produto['categoria_nome']): ?>
                        <p class="text-sm text-gray-400 mb-2">
                            <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($produto['categoria_nome']) ?>
                        </p>
                        <?php endif; ?>
                        
                        <p class="text-2xl font-bold text-red-400 mb-2">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                        
                        <p class="text-gray-300 text-sm mb-2">Estoque: <?= $produto['estoque'] ?> unidades</p>
                        
                        <p class="text-gray-300 text-sm mb-4 line-clamp-3"><?= htmlspecialchars($produto['descricao']) ?></p>
                        
                        <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                            <span><i class="fas fa-heart mr-1"></i><?= $produto['total_favoritos'] ?> favoritos</span>
                            <span><?= date('d/m/Y', strtotime($produto['data_cadastro'])) ?></span>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button @click="selectedProduct = <?= json_encode($produto) ?>; editModalOpen = true" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-300 hover:scale-105">
                                <i class="fas fa-edit mr-1"></i>Editar
                            </button>
                            
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                <input type="hidden" name="status" value="<?= $produto['ativo'] ? 'inactive' : 'active' ?>">
                                <button type="submit" class="px-3 py-2 <?= $produto['ativo'] ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' ?> text-white rounded-lg text-sm font-semibold transition-all duration-300 hover:scale-105">
                                    <i class="fas <?= $produto['ativo'] ? 'fa-pause' : 'fa-play' ?> mr-1"></i>
                                    <?= $produto['ativo'] ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>
                            
                            <button @click="selectedProduct = <?= $produto['id'] ?>; deleteModalOpen = true" 
                                    class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition-all duration-300 hover:scale-105">
                                <i class="fas fa-trash mr-1"></i>Excluir
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($produtos)): ?>
            <div class="text-center py-12">
                <i class="fas fa-shopping-bag text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-400 mb-2">Nenhum produto encontrado</h3>
                <p class="text-gray-500 mb-6">Comece criando seu primeiro produto</p>
                <button @click="modalOpen = true" class="btn-premium text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 hover:scale-105">
                    <i class="fas fa-plus mr-2"></i>Criar Primeiro Produto
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center">
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-all duration-300">
                        Anterior
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="px-4 py-2 rounded-lg transition-all duration-300 <?= $i === $page ? 'bg-red-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-white' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-all duration-300">
                        Próxima
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal de Novo Produto -->
    <div x-show="modalOpen" @click.away="modalOpen = false" 
         class="fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
        <div class="modal-content rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold text-white mb-6">Novo Produto</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nome do Produto</label>
                        <input type="text" name="nome" required 
                               class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Preço (R$)</label>
                        <input type="number" name="preco" step="0.01" min="0" required 
                               class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Estoque</label>
                        <input type="number" name="estoque" min="0" value="0" 
                               class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Categoria</label>
                        <select name="categoria_id" class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                            <option value="0">Sem categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                        <textarea name="descricao" rows="4" 
                                  class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Imagem do Produto</label>
                        <input type="file" name="imagem" accept="image/*" 
                               class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="modalOpen = false" 
                            class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-premium text-white px-6 py-3 rounded-xl transition-all duration-300">
                        Criar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Editar Produto -->
    <div x-show="editModalOpen" @click.away="editModalOpen = false" 
         class="fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
        <div class="modal-content rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold text-white mb-6">Editar Produto</h3>
            <form method="POST" enctype="multipart/form-data" x-show="selectedProduct">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="id" :value="selectedProduct?.id">
                <input type="hidden" name="imagem_atual" :value="selectedProduct?.imagem">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nome do Produto</label>
                        <input type="text" name="nome" :value="selectedProduct?.nome" required 
                               class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Preço (R$)</label>
                        <input type="number" name="preco" :value="selectedProduct?.preco" step="0.01" min="0" required 
                               class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Estoque</label>
                        <input type="number" name="estoque" :value="selectedProduct?.estoque" min="0" 
                               class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Categoria</label>
                        <select name="categoria_id" :value="selectedProduct?.categoria_id" class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                            <option value="0">Sem categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                        <textarea name="descricao" rows="4" :value="selectedProduct?.descricao" 
                                  class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nova Imagem (opcional)</label>
                        <input type="file" name="imagem" accept="image/*" 
                               class="w-full px-4 py-3 input-field rounded-xl text-white focus:outline-none transition-all duration-300">
                        <p class="text-xs text-gray-400 mt-1">Deixe em branco para manter a imagem atual</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="editModalOpen = false" 
                            class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-premium text-white px-6 py-3 rounded-xl transition-all duration-300">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Exclusão -->
    <div x-show="deleteModalOpen" @click.away="deleteModalOpen = false" 
         class="fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
        <div class="modal-content rounded-xl p-6 w-full max-w-md">
            <h3 class="text-xl font-bold text-white mb-4">Confirmar Exclusão</h3>
            <p class="text-gray-300 mb-6">Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.</p>
            
            <div class="flex justify-end space-x-3">
                <button @click="deleteModalOpen = false" 
                        class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300">
                    Cancelar
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="id" :value="selectedProduct">
                    <button type="submit" class="btn-premium text-white px-6 py-3 rounded-xl transition-all duration-300">
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
    
    // Efeito de partículas interativas
    document.addEventListener('mousemove', function(e) {
        const particles = document.querySelectorAll('.particle');
        particles.forEach((particle, index) => {
            const speed = (index + 1) * 0.1;
            const x = e.clientX * speed / 200;
            const y = e.clientY * speed / 200;
            particle.style.transform = `translate(${x}px, ${y}px)`;
        });
    });
</script>

</body>
</html>
