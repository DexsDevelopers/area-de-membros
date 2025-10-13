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

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false, modalOpen: false, editModalOpen: false, deleteModalOpen: false, selectedProduct: null }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-black/90 backdrop-blur-lg border-r border-gray-800 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white">PRODUTOS</span>
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
            
            <a href="produtos_moderno.php" class="flex items-center px-4 py-3 text-white bg-red-600/20 rounded-lg border border-red-600/30">
                <i class="fas fa-shopping-bag mr-3"></i>
                <span>Produtos</span>
            </a>
            
            <a href="gerenciar_categorias.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-tags mr-3"></i>
                <span>Categorias</span>
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
                        <h1 class="text-2xl font-bold text-white">Gerenciar Produtos</h1>
                        <p class="text-gray-400 text-sm">Crie e gerencie produtos da loja</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Novo Produto
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
                            <p class="text-red-100 text-sm font-medium">Total de Produtos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['total_produtos']) ?></p>
                        </div>
                        <i class="fas fa-shopping-bag text-red-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Produtos Ativos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['produtos_ativos']) ?></p>
                        </div>
                        <i class="fas fa-check-circle text-blue-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Em Estoque</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['produtos_estoque']) ?></p>
                        </div>
                        <i class="fas fa-boxes text-green-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Valor Total</p>
                            <p class="text-3xl font-bold text-white">R$ <?= number_format($stats['valor_total'], 2, ',', '.') ?></p>
                        </div>
                        <i class="fas fa-dollar-sign text-purple-300 text-3xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Grid de Produtos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($produtos as $produto): ?>
                <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl overflow-hidden border border-gray-700">
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
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $produto['estoque'] > 0 ? 'bg-green-600 text-white' : 'bg-red-600 text-white' ?>">
                                <?= $produto['estoque'] > 0 ? 'Em Estoque' : 'Sem Estoque' ?>
                            </span>
                        </div>
                        
                        <div class="absolute top-4 right-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $produto['ativo'] ? 'bg-green-600 text-white' : 'bg-gray-600 text-white' ?>">
                                <?= $produto['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-white mb-2"><?= htmlspecialchars($produto['nome']) ?></h3>
                        
                        <?php if ($produto['categoria_nome']): ?>
                        <p class="text-sm text-gray-400 mb-2">
                            <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($produto['categoria_nome']) ?>
                        </p>
                        <?php endif; ?>
                        
                        <p class="text-2xl font-bold text-red-500 mb-2">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                        
                        <p class="text-gray-300 text-sm mb-2">Estoque: <?= $produto['estoque'] ?> unidades</p>
                        
                        <p class="text-gray-300 text-sm mb-4 line-clamp-3"><?= htmlspecialchars($produto['descricao']) ?></p>
                        
                        <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                            <span><i class="fas fa-heart mr-1"></i><?= $produto['total_favoritos'] ?> favoritos</span>
                            <span><?= date('d/m/Y', strtotime($produto['data_cadastro'])) ?></span>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button @click="selectedProduct = <?= json_encode($produto) ?>; editModalOpen = true" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-edit mr-1"></i>Editar
                            </button>
                            
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                <input type="hidden" name="status" value="<?= $produto['ativo'] ? 'inactive' : 'active' ?>">
                                <button type="submit" class="px-3 py-2 <?= $produto['ativo'] ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' ?> text-white rounded-lg text-sm font-semibold transition">
                                    <i class="fas <?= $produto['ativo'] ? 'fa-pause' : 'fa-play' ?> mr-1"></i>
                                    <?= $produto['ativo'] ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>
                            
                            <button @click="selectedProduct = <?= $produto['id'] ?>; deleteModalOpen = true" 
                                    class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
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
                <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Criar Primeiro Produto
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center">
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg">
                        Anterior
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="px-4 py-2 rounded-lg <?= $i === $page ? 'bg-red-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-white' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg">
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
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-white mb-4">Novo Produto</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nome do Produto</label>
                        <input type="text" name="nome" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Preço (R$)</label>
                        <input type="number" name="preco" step="0.01" min="0" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Estoque</label>
                        <input type="number" name="estoque" min="0" value="0" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Categoria</label>
                        <select name="categoria_id" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="0">Sem categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                        <textarea name="descricao" rows="4" 
                                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Imagem do Produto</label>
                        <input type="file" name="imagem" accept="image/*" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="modalOpen = false" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Criar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Editar Produto -->
    <div x-show="editModalOpen" @click.away="editModalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-white mb-4">Editar Produto</h3>
            <form method="POST" enctype="multipart/form-data" x-show="selectedProduct">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="id" :value="selectedProduct?.id">
                <input type="hidden" name="imagem_atual" :value="selectedProduct?.imagem">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nome do Produto</label>
                        <input type="text" name="nome" :value="selectedProduct?.nome" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Preço (R$)</label>
                        <input type="number" name="preco" :value="selectedProduct?.preco" step="0.01" min="0" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Estoque</label>
                        <input type="number" name="estoque" :value="selectedProduct?.estoque" min="0" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Categoria</label>
                        <select name="categoria_id" :value="selectedProduct?.categoria_id" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="0">Sem categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                        <textarea name="descricao" rows="4" :value="selectedProduct?.descricao" 
                                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
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
            <p class="text-gray-300 mb-6">Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.</p>
            
            <div class="flex justify-end space-x-3">
                <button @click="deleteModalOpen = false" 
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Cancelar
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="id" :value="selectedProduct">
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
