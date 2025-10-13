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
            case 'add_category':
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $cor = trim($_POST['cor'] ?? '#e11d48');
                
                if (empty($nome)) {
                    $error = 'Nome da categoria é obrigatório.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categorias (nome, descricao, cor, ativo, data_criacao) VALUES (?, ?, ?, 1, NOW())");
                    $stmt->execute([$nome, $descricao, $cor]);
                    $message = 'Categoria adicionada com sucesso!';
                }
                break;
                
            case 'edit_category':
                $id = intval($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $cor = trim($_POST['cor'] ?? '#e11d48');
                
                if (empty($nome) || $id <= 0) {
                    $error = 'Dados inválidos.';
                } else {
                    $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, descricao = ?, cor = ? WHERE id = ?");
                    $stmt->execute([$nome, $descricao, $cor, $id]);
                    $message = 'Categoria atualizada com sucesso!';
                }
                break;
                
            case 'delete_category':
                $id = intval($_POST['id'] ?? 0);
                if ($id > 0) {
                    // Verificar se há cursos usando esta categoria
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cursos WHERE categoria_id = ?");
                    $stmt->execute([$id]);
                    $cursos_count = $stmt->fetchColumn();
                    
                    if ($cursos_count > 0) {
                        $error = "Não é possível excluir esta categoria pois há $cursos_count curso(s) vinculado(s) a ela.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
                        $stmt->execute([$id]);
                        $message = 'Categoria excluída com sucesso!';
                    }
                }
                break;
                
            case 'toggle_status':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? 'active';
    if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE categorias SET ativo = ? WHERE id = ?");
                    $stmt->execute([$status === 'active' ? 1 : 0, $id]);
                    $message = 'Status da categoria atualizado!';
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

// Buscar categorias
$categorias = safeQueryArray($pdo, "
    SELECT c.*, 
           (SELECT COUNT(*) FROM cursos WHERE categoria_id = c.id AND ativo = 1) as total_cursos
    FROM categorias c 
    ORDER BY c.data_criacao DESC
");

// Estatísticas com tratamento de erro
$stats = [
    'total_categorias' => safeQuery($pdo, "SELECT COUNT(*) FROM categorias"),
    'categorias_ativas' => safeQuery($pdo, "SELECT COUNT(*) FROM categorias WHERE ativo = 1"),
    'total_cursos' => safeQuery($pdo, "SELECT COUNT(*) FROM cursos WHERE ativo = 1")
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Categorias | HELMER ACADEMY</title>
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

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false, modalOpen: false, editModalOpen: false, deleteModalOpen: false, selectedCategory: null }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-black/90 backdrop-blur-lg border-r border-gray-800 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tags text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white">CATEGORIAS</span>
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
            
            <a href="cursos.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-graduation-cap mr-3"></i>
                <span>Cursos</span>
            </a>
            
            <a href="produtos.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-shopping-bag mr-3"></i>
                <span>Produtos</span>
            </a>
            
            <a href="gerenciar_categorias.php" class="flex items-center px-4 py-3 text-white bg-red-600/20 rounded-lg border border-red-600/30">
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
                        <h1 class="text-2xl font-bold text-white">Gerenciar Categorias</h1>
                        <p class="text-gray-400 text-sm">Organize os cursos por categorias</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Nova Categoria
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="fade-in card-hover bg-gradient-to-r from-red-600 to-red-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium">Total de Categorias</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['total_categorias']) ?></p>
                        </div>
                        <i class="fas fa-tags text-red-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Categorias Ativas</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['categorias_ativas']) ?></p>
                        </div>
                        <i class="fas fa-check-circle text-blue-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Total de Cursos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['total_cursos']) ?></p>
                        </div>
                        <i class="fas fa-graduation-cap text-green-300 text-3xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Grid de Categorias -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($categorias as $categoria): ?>
                <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: <?= htmlspecialchars($categoria['cor']) ?>">
                                <i class="fas fa-tag text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white"><?= htmlspecialchars($categoria['nome']) ?></h3>
                                <p class="text-sm text-gray-400"><?= $categoria['total_cursos'] ?> curso(s)</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $categoria['ativo'] ? 'bg-green-600 text-white' : 'bg-gray-600 text-white' ?>">
                                <?= $categoria['ativo'] ? 'Ativa' : 'Inativa' ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($categoria['descricao'])): ?>
                    <p class="text-gray-300 text-sm mb-4"><?= htmlspecialchars($categoria['descricao']) ?></p>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between text-sm text-gray-400 mb-4">
                        <span>Criada em <?= date('d/m/Y', strtotime($categoria['data_criacao'])) ?></span>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button @click="selectedCategory = <?= json_encode($categoria) ?>; editModalOpen = true" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-semibold transition">
                            <i class="fas fa-edit mr-1"></i>Editar
                        </button>
                        
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $categoria['id'] ?>">
                            <input type="hidden" name="status" value="<?= $categoria['ativo'] ? 'inactive' : 'active' ?>">
                            <button type="submit" class="px-3 py-2 <?= $categoria['ativo'] ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' ?> text-white rounded-lg text-sm font-semibold transition">
                                <i class="fas <?= $categoria['ativo'] ? 'fa-pause' : 'fa-play' ?> mr-1"></i>
                                <?= $categoria['ativo'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                        
                        <button @click="selectedCategory = <?= $categoria['id'] ?>; deleteModalOpen = true" 
                                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
                            <i class="fas fa-trash mr-1"></i>Excluir
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($categorias)): ?>
            <div class="text-center py-12">
                <i class="fas fa-tags text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-400 mb-2">Nenhuma categoria encontrada</h3>
                <p class="text-gray-500 mb-6">Comece criando sua primeira categoria</p>
                <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Criar Primeira Categoria
                </button>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal de Nova Categoria -->
    <div x-show="modalOpen" @click.away="modalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-white mb-4">Nova Categoria</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nome da Categoria</label>
                        <input type="text" name="nome" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                        <textarea name="descricao" rows="3" 
                                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Cor</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" name="cor" value="#e11d48" 
                                   class="w-12 h-12 bg-gray-700 border border-gray-600 rounded-lg cursor-pointer">
                            <input type="text" name="cor" value="#e11d48" 
                                   class="flex-1 px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="modalOpen = false" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Criar Categoria
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Editar Categoria -->
    <div x-show="editModalOpen" @click.away="editModalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-white mb-4">Editar Categoria</h3>
            <form method="POST" x-show="selectedCategory">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="id" :value="selectedCategory?.id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nome da Categoria</label>
                        <input type="text" name="nome" :value="selectedCategory?.nome" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                        <textarea name="descricao" rows="3" :value="selectedCategory?.descricao"
                                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Cor</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" name="cor" :value="selectedCategory?.cor" 
                                   class="w-12 h-12 bg-gray-700 border border-gray-600 rounded-lg cursor-pointer">
                            <input type="text" name="cor" :value="selectedCategory?.cor" 
                                   class="flex-1 px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
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
            <p class="text-gray-300 mb-6">Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita.</p>
            
            <div class="flex justify-end space-x-3">
                <button @click="deleteModalOpen = false" 
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Cancelar
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" :value="selectedCategory">
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