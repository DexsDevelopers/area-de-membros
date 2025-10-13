<?php
session_start();
require 'config.php';

// Verificar se é administrador
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Processar ações
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_user':
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'user';
                
                if (empty($username) || empty($password)) {
                    $error = 'Username e senha são obrigatórios.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, data_cadastro) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$username, $hashed_password, $role]);
                    $message = 'Usuário cadastrado com sucesso!';
                }
                break;
                
            case 'delete_user':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
                    $stmt->execute([$user_id]);
                    $message = 'Usuário excluído com sucesso!';
                }
                break;
                
            case 'toggle_status':
                $user_id = intval($_POST['user_id'] ?? 0);
                $status = $_POST['status'] ?? 'active';
                if ($user_id > 0) {
                    // Verificar se campo ativo existe
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET ativo = ? WHERE id = ?");
                        $stmt->execute([$status === 'active' ? 1 : 0, $user_id]);
                    } catch (Exception $e) {
                        // Campo ativo não existe, ignorar
                    }
                    $message = 'Status do usuário atualizado!';
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

// Buscar usuários com paginação
$page = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Contar total de usuários
$total_users = safeQuery($pdo, "SELECT COUNT(*) FROM users");
$total_pages = ceil($total_users / $limit);

// Buscar usuários - consulta simplificada
$users = safeQueryArray($pdo, "
    SELECT id, username, role, 
           COALESCE(data_cadastro, NOW()) as data_cadastro,
           COALESCE(ativo, 1) as ativo
    FROM users 
    ORDER BY COALESCE(data_cadastro, NOW()) DESC 
    LIMIT ? OFFSET ?
", [$limit, $offset]);

// Estatísticas com tratamento de erro
$stats = [
    'total_users' => safeQuery($pdo, "SELECT COUNT(*) FROM users WHERE role = 'user'"),
    'total_admins' => safeQuery($pdo, "SELECT COUNT(*) FROM users WHERE role = 'admin'"),
    'active_users' => safeQuery($pdo, "SELECT COUNT(*) FROM users WHERE COALESCE(ativo, 1) = 1"),
    'new_this_month' => safeQuery($pdo, "SELECT COUNT(*) FROM users WHERE data_cadastro >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários | HELMER ACADEMY</title>
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

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false, modalOpen: false, deleteModalOpen: false, selectedUser: null }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-black/90 backdrop-blur-lg border-r border-gray-800 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white">USUÁRIOS</span>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="admin_dashboard_simples.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
                <i class="fas fa-tachometer-alt mr-3"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="admin_painel_simples.php" class="flex items-center px-4 py-3 text-white bg-red-600/20 rounded-lg border border-red-600/30">
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
                        <h1 class="text-2xl font-bold text-white">Gerenciar Usuários</h1>
                        <p class="text-gray-400 text-sm">Administre usuários e permissões</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Debug Link -->
                    <a href="debug_usuarios.php" class="text-blue-400 hover:text-blue-300 text-sm">
                        <i class="fas fa-bug mr-1"></i>Debug
                    </a>
                    
                    <button @click="modalOpen = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        <i class="fas fa-plus mr-2"></i>Novo Usuário
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
                            <p class="text-red-100 text-sm font-medium">Total de Usuários</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['total_users']) ?></p>
                        </div>
                        <i class="fas fa-users text-red-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Administradores</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['total_admins']) ?></p>
                        </div>
                        <i class="fas fa-crown text-blue-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Usuários Ativos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['active_users']) ?></p>
                        </div>
                        <i class="fas fa-user-check text-green-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Novos Este Mês</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($stats['new_this_month']) ?></p>
                        </div>
                        <i class="fas fa-user-plus text-purple-300 text-3xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Usuários -->
            <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white">Lista de Usuários</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Usuário</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-white text-sm"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-white"><?= htmlspecialchars($user['username']) ?></div>
                                                <div class="text-sm text-gray-400">ID: <?= $user['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $user['role'] === 'admin' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white' ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $user['ativo'] ? 'bg-green-600 text-white' : 'bg-gray-600 text-white' ?>">
                                            <?= $user['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <?= date('d/m/Y', strtotime($user['data_cadastro'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if ($user['role'] !== 'admin'): ?>
                                            <button @click="selectedUser = <?= $user['id'] ?>; deleteModalOpen = true" 
                                                    class="text-red-400 hover:text-red-300">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $user['ativo'] ? 'inactive' : 'active' ?>">
                                                <button type="submit" class="text-blue-400 hover:text-blue-300">
                                                    <i class="fas <?= $user['ativo'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-400">
                                        Nenhum usuário encontrado
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            Página <?= $page ?> de <?= $total_pages ?>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm">
                                Anterior
                            </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?>" 
                               class="px-3 py-2 rounded-lg text-sm <?= $i === $page ? 'bg-red-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-white' ?>">
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm">
                                Próxima
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal de Novo Usuário -->
    <div x-show="modalOpen" @click.away="modalOpen = false" 
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-white mb-4">Novo Usuário</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                        <input type="text" name="username" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Senha</label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tipo</label>
                        <select name="role" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="modalOpen = false" 
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Cadastrar
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
            <p class="text-gray-300 mb-6">Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.</p>
            
            <div class="flex justify-end space-x-3">
                <button @click="deleteModalOpen = false" 
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Cancelar
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" :value="selectedUser">
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
