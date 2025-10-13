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
            case 'add_course':
                $titulo = trim($_POST['titulo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $tipo = $_POST['tipo'] ?? 'gratuitos';
                $categoria_id = intval($_POST['categoria_id'] ?? 0);
                $video_url = trim($_POST['video_url'] ?? '');
                $topics = trim($_POST['topics'] ?? '');
                
                if (empty($titulo) || empty($descricao)) {
                    $error = 'Título e descrição são obrigatórios.';
                } else {
                    // Upload da imagem
                    $imagem = '';
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/cursos/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = uniqid() . '.' . $fileExtension;
                            $uploadPath = $uploadDir . $newFileName;
                            
                            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadPath)) {
                                $imagem = $uploadPath;
                            }
                        }
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO cursos (titulo, descricao, tipo, categoria_id, video_url, topics, imagem, ativo, data_postagem) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$titulo, $descricao, $tipo, $categoria_id, $video_url, $topics, $imagem]);
                    $message = 'Curso adicionado com sucesso!';
                }
                break;
                
            case 'edit_course':
                $id = intval($_POST['id'] ?? 0);
                $titulo = trim($_POST['titulo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $tipo = $_POST['tipo'] ?? 'gratuitos';
                $categoria_id = intval($_POST['categoria_id'] ?? 0);
                $video_url = trim($_POST['video_url'] ?? '');
                $topics = trim($_POST['topics'] ?? '');
                
                if (empty($titulo) || empty($descricao) || $id <= 0) {
                    $error = 'Dados inválidos.';
                } else {
                    // Upload da nova imagem se fornecida
                    $imagem = $_POST['imagem_atual'] ?? '';
                    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/cursos/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileExtension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = uniqid() . '.' . $fileExtension;
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
                        UPDATE cursos 
                        SET titulo = ?, descricao = ?, tipo = ?, categoria_id = ?, video_url = ?, topics = ?, imagem = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$titulo, $descricao, $tipo, $categoria_id, $video_url, $topics, $imagem, $id]);
                    $message = 'Curso atualizado com sucesso!';
                }
                break;
                
            case 'delete_course':
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    // Buscar imagem para deletar
                    $stmt = $pdo->prepare("SELECT imagem FROM cursos WHERE id = ?");
                    $stmt->execute([$id]);
                    $curso = $stmt->fetch();
                    
                    if ($curso && !empty($curso['imagem']) && file_exists($curso['imagem'])) {
                        unlink($curso['imagem']);
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM cursos WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Curso excluído com sucesso!';
                }
                break;
                
            case 'toggle_status':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? 'active';
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE cursos SET ativo = ? WHERE id = ?");
                    $stmt->execute([$status === 'active' ? 1 : 0, $id]);
                    $message = 'Status do curso atualizado!';
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}

// Buscar cursos com paginação
$page = intval($_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    // Contar total de cursos
    $total_cursos = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
    $total_pages = ceil($total_cursos / $limit);
    
    // Buscar cursos
    $stmt = $pdo->prepare("
        SELECT c.*, cat.nome as categoria_nome,
               (SELECT COUNT(*) FROM favoritos WHERE curso_id = c.id) as total_favoritos
        FROM cursos c
        LEFT JOIN categorias cat ON c.categoria_id = cat.id
        ORDER BY c.data_postagem DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar categorias para o formulário
    $categorias = $pdo->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas
    $stats = [
        'total_cursos' => $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn(),
        'cursos_ativos' => $pdo->query("SELECT COUNT(*) FROM cursos WHERE ativo = 1")->fetchColumn(),
        'cursos_gratuitos' => $pdo->query("SELECT COUNT(*) FROM cursos WHERE tipo = 'gratuitos' AND ativo = 1")->fetchColumn(),
        'cursos_premium' => $pdo->query("SELECT COUNT(*) FROM cursos WHERE tipo = 'premium' AND ativo = 1")->fetchColumn()
    ];
    
} catch (Exception $e) {
    error_log("Erro ao buscar cursos: " . $e->getMessage());
    $cursos = [];
    $categorias = [];
    $stats = ['total_cursos' => 0, 'cursos_ativos' => 0, 'cursos_gratuitos' => 0, 'cursos_premium' => 0];
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cursos | HELMER ACADEMY</title>
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

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false, modalOpen: false, editModalOpen: false, deleteModalOpen: false, selectedCourse: null }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-black/90 backdrop-blur-lg border-r border-gray-800 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white">CURSOS</span>
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
            
            <a href="cursos_moderno.php" class="flex items-center px-4 py-3 text-white bg-red-600/20 rounded-lg border border-red-600/30">
                <i class="fas fa-graduation-cap mr-3"></i>
                <span>Cursos</span>
            </a>
            
            <a href="produtos.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
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
                        <h1 class="text-2xl font-bold text-white">Gerenciar Cursos</h1>
                        <p class="text-gray-400 text-sm">Crie e gerencie cursos da plataforma</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Novo Curso
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
                            <p class="text-red-100 text-sm font-medium">Total de Cursos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['total_cursos']) ?></p>
                        </div>
                        <i class="fas fa-graduation-cap text-red-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Cursos Ativos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['cursos_ativos']) ?></p>
                        </div>
                        <i class="fas fa-check-circle text-blue-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Gratuitos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['cursos_gratuitos']) ?></p>
                        </div>
                        <i class="fas fa-gift text-green-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Premium</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['cursos_premium']) ?></p>
                        </div>
                        <i class="fas fa-crown text-purple-300 text-3xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Grid de Cursos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($cursos as $curso): ?>
                <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl overflow-hidden border border-gray-700">
                    <div class="relative">
                        <?php if (!empty($curso['imagem'])): ?>
                        <img src="/<?= htmlspecialchars($curso['imagem']) ?>" alt="<?= htmlspecialchars($curso['titulo']) ?>" 
                             class="w-full h-48 object-cover">
                        <?php else: ?>
                        <div class="w-full h-48 bg-gray-700 flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-4xl text-gray-500"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-4 left-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $curso['tipo'] === 'premium' ? 'bg-purple-600 text-white' : 'bg-green-600 text-white' ?>">
                                <?= ucfirst($curso['tipo']) ?>
                            </span>
                        </div>
                        
                        <div class="absolute top-4 right-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $curso['ativo'] ? 'bg-green-600 text-white' : 'bg-gray-600 text-white' ?>">
                                <?= $curso['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-white mb-2"><?= htmlspecialchars($curso['titulo']) ?></h3>
                        
                        <?php if ($curso['categoria_nome']): ?>
                        <p class="text-sm text-gray-400 mb-2">
                            <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($curso['categoria_nome']) ?>
                        </p>
                        <?php endif; ?>
                        
                        <p class="text-gray-300 text-sm mb-4 line-clamp-3"><?= htmlspecialchars($curso['descricao']) ?></p>
                        
                        <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                            <span><i class="fas fa-heart mr-1"></i><?= $curso['total_favoritos'] ?> favoritos</span>
                            <span><?= date('d/m/Y', strtotime($curso['data_postagem'])) ?></span>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button @click="selectedCourse = <?= json_encode($curso) ?>; editModalOpen = true" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-edit mr-1"></i>Editar
                            </button>
                            
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $curso['id'] ?>">
                                <input type="hidden" name="status" value="<?= $curso['ativo'] ? 'inactive' : 'active' ?>">
                                <button type="submit" class="px-3 py-2 <?= $curso['ativo'] ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' ?> text-white rounded-lg text-sm font-semibold transition">
                                    <i class="fas <?= $curso['ativo'] ? 'fa-pause' : 'fa-play' ?> mr-1"></i>
                                    <?= $curso['ativo'] ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>
                            
                            <button @click="selectedCourse = <?= $curso['id'] ?>; deleteModalOpen = true" 
                                    class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
                                <i class="fas fa-trash mr-1"></i>Excluir
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($cursos)): ?>
            <div class="text-center py-12">
                <i class="fas fa-graduation-cap text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-400 mb-2">Nenhum curso encontrado</h3>
                <p class="text-gray-500 mb-6">Comece criando seu primeiro curso</p>
                <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Criar Primeiro Curso
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
    
    <!-- Modal de Novo Curso -->
    <div x-show="modalOpen" @click.away="modalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-white mb-4">Novo Curso</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_course">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Título do Curso</label>
                        <input type="text" name="titulo" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                        <textarea name="descricao" rows="4" required 
                                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tipo</label>
                        <select name="tipo" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="gratuitos">Gratuito</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Categoria</label>
                        <select name="categoria_id" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="0">Sem categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">URL do Vídeo</label>
                        <input type="url" name="video_url" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                               placeholder="https://youtube.com/watch?v=...">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tópicos (um por linha)</label>
                        <textarea name="topics" rows="4" 
                                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                                  placeholder="Tópico 1&#10;Tópico 2&#10;Tópico 3"></textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Imagem do Curso</label>
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
                        Criar Curso
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Editar Curso -->
    <div x-show="editModalOpen" @click.away="editModalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-white mb-4">Editar Curso</h3>
            <form method="POST" enctype="multipart/form-data" x-show="selectedCourse">
                <input type="hidden" name="action" value="edit_course">
                <input type="hidden" name="id" :value="selectedCourse?.id">
                <input type="hidden" name="imagem_atual" :value="selectedCourse?.imagem">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Título do Curso</label>
                        <input type="text" name="titulo" :value="selectedCourse?.titulo" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                        <textarea name="descricao" rows="4" :value="selectedCourse?.descricao" required 
                                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tipo</label>
                        <select name="tipo" :value="selectedCourse?.tipo" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="gratuitos">Gratuito</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Categoria</label>
                        <select name="categoria_id" :value="selectedCourse?.categoria_id" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="0">Sem categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">URL do Vídeo</label>
                        <input type="url" name="video_url" :value="selectedCourse?.video_url" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tópicos (um por linha)</label>
                        <textarea name="topics" rows="4" :value="selectedCourse?.topics" 
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
            <p class="text-gray-300 mb-6">Tem certeza que deseja excluir este curso? Esta ação não pode ser desfeita.</p>
            
            <div class="flex justify-end space-x-3">
                <button @click="deleteModalOpen = false" 
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Cancelar
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="id" :value="selectedCourse">
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
