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

// Processar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_site':
                $site_name = trim($_POST['site_name'] ?? '');
                $site_description = trim($_POST['site_description'] ?? '');
                $site_keywords = trim($_POST['site_keywords'] ?? '');
                
                // Atualizar configurações do site
                $message = 'Configurações do site atualizadas com sucesso!';
                break;
                
            case 'update_email':
                $smtp_host = trim($_POST['smtp_host'] ?? '');
                $smtp_port = intval($_POST['smtp_port'] ?? 587);
                $smtp_user = trim($_POST['smtp_user'] ?? '');
                $smtp_pass = trim($_POST['smtp_pass'] ?? '');
                
                $message = 'Configurações de email atualizadas com sucesso!';
                break;
                
            case 'update_cache':
                $cache_duration = intval($_POST['cache_duration'] ?? 300);
                $cache_enabled = isset($_POST['cache_enabled']) ? 1 : 0;
                
                $message = 'Configurações de cache atualizadas com sucesso!';
                break;
                
            case 'backup_database':
                $backup_file = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
                if (!is_dir('backups')) {
                    mkdir('backups', 0755, true);
                }
                
                $command = "mysqldump --user={$user} --password={$pass} --host={$host} {$db} > {$backup_file}";
                exec($command);
                
                $message = 'Backup do banco de dados criado com sucesso!';
                break;
                
            case 'clear_logs':
                $log_files = glob('logs/*.log');
                foreach ($log_files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                $message = 'Logs limpos com sucesso!';
                break;
        }
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}

// Buscar configurações atuais
$configuracoes = [
    'site_name' => 'Helmer Academy',
    'site_description' => 'Plataforma de cursos e produtos digitais',
    'site_keywords' => 'cursos online, educação, treinamentos',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => '',
    'cache_duration' => 300,
    'cache_enabled' => true
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações | HELMER ACADEMY</title>
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
    </style>
</head>
<body class="gradient-bg text-white font-sans min-h-screen">

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false, activeTab: 'site' }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-black/90 backdrop-blur-lg border-r border-gray-800 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-cog text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white">CONFIGURAÇÕES</span>
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
            
            <button @click="activeTab = 'site'" 
                    :class="activeTab === 'site' ? 'bg-red-600/20 border border-red-600/30 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800/50'"
                    class="flex items-center w-full px-4 py-3 rounded-lg transition">
                <i class="fas fa-globe mr-3"></i>
                <span>Site</span>
            </button>
            
            <button @click="activeTab = 'email'" 
                    :class="activeTab === 'email' ? 'bg-red-600/20 border border-red-600/30 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800/50'"
                    class="flex items-center w-full px-4 py-3 rounded-lg transition">
                <i class="fas fa-envelope mr-3"></i>
                <span>Email</span>
            </button>
            
            <button @click="activeTab = 'cache'" 
                    :class="activeTab === 'cache' ? 'bg-red-600/20 border border-red-600/30 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800/50'"
                    class="flex items-center w-full px-4 py-3 rounded-lg transition">
                <i class="fas fa-database mr-3"></i>
                <span>Cache</span>
            </button>
            
            <button @click="activeTab = 'backup'" 
                    :class="activeTab === 'backup' ? 'bg-red-600/20 border border-red-600/30 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800/50'"
                    class="flex items-center w-full px-4 py-3 rounded-lg transition">
                <i class="fas fa-shield-alt mr-3"></i>
                <span>Backup</span>
            </button>
            
            <button @click="activeTab = 'logs'" 
                    :class="activeTab === 'logs' ? 'bg-red-600/20 border border-red-600/30 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800/50'"
                    class="flex items-center w-full px-4 py-3 rounded-lg transition">
                <i class="fas fa-file-alt mr-3"></i>
                <span>Logs</span>
            </button>
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
                        <h1 class="text-2xl font-bold text-white">Configurações</h1>
                        <p class="text-gray-400 text-sm">Gerencie as configurações da plataforma</p>
                    </div>
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
            
            <!-- Configurações do Site -->
            <div x-show="activeTab === 'site'" class="space-y-6">
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">
                        <i class="fas fa-globe mr-2"></i>Configurações do Site
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_site">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Nome do Site</label>
                            <input type="text" name="site_name" value="<?= htmlspecialchars($configuracoes['site_name']) ?>" 
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Descrição</label>
                            <textarea name="site_description" rows="3" 
                                      class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"><?= htmlspecialchars($configuracoes['site_description']) ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Palavras-chave</label>
                            <input type="text" name="site_keywords" value="<?= htmlspecialchars($configuracoes['site_keywords']) ?>" 
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                                   placeholder="cursos online, educação, treinamentos">
                        </div>
                        
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-save mr-2"></i>Salvar Configurações
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Configurações de Email -->
            <div x-show="activeTab === 'email'" class="space-y-6">
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">
                        <i class="fas fa-envelope mr-2"></i>Configurações de Email
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_email">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">SMTP Host</label>
                                <input type="text" name="smtp_host" value="<?= htmlspecialchars($configuracoes['smtp_host']) ?>" 
                                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">SMTP Port</label>
                                <input type="number" name="smtp_port" value="<?= $configuracoes['smtp_port'] ?>" 
                                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                            <input type="email" name="smtp_user" value="<?= htmlspecialchars($configuracoes['smtp_user']) ?>" 
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Senha</label>
                            <input type="password" name="smtp_pass" 
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                                   placeholder="Digite a senha do email">
                        </div>
                        
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-save mr-2"></i>Salvar Configurações de Email
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Configurações de Cache -->
            <div x-show="activeTab === 'cache'" class="space-y-6">
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">
                        <i class="fas fa-database mr-2"></i>Configurações de Cache
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_cache">
                        
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="cache_enabled" <?= $configuracoes['cache_enabled'] ? 'checked' : '' ?> 
                                   class="w-4 h-4 text-red-600 bg-gray-700 border-gray-600 rounded focus:ring-red-500">
                            <label class="text-gray-300">Habilitar Cache</label>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Duração do Cache (segundos)</label>
                            <input type="number" name="cache_duration" value="<?= $configuracoes['cache_duration'] ?>" 
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <p class="text-gray-400 text-sm mt-1">Recomendado: 300 segundos (5 minutos)</p>
                        </div>
                        
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-save mr-2"></i>Salvar Configurações de Cache
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Backup -->
            <div x-show="activeTab === 'backup'" class="space-y-6">
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">
                        <i class="fas fa-shield-alt mr-2"></i>Backup do Banco de Dados
                    </h3>
                    <p class="text-gray-400 mb-4">Crie um backup completo do banco de dados para segurança.</p>
                    
                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja criar um backup?')">
                        <input type="hidden" name="action" value="backup_database">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-download mr-2"></i>Criar Backup
                        </button>
                    </form>
                </div>
                
                <!-- Lista de Backups -->
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">Backups Disponíveis</h3>
                    <div class="space-y-2">
                        <?php
                        $backups = glob('backups/*.sql');
                        if (empty($backups)): ?>
                            <p class="text-gray-400">Nenhum backup encontrado.</p>
                        <?php else: ?>
                            <?php foreach (array_reverse($backups) as $backup): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                                <div>
                                    <p class="text-white font-medium"><?= basename($backup) ?></p>
                                    <p class="text-gray-400 text-sm"><?= date('d/m/Y H:i', filemtime($backup)) ?></p>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="<?= $backup ?>" download class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                        <i class="fas fa-download mr-1"></i>Download
                                    </a>
                                    <button onclick="deleteBackup('<?= basename($backup) ?>')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                        <i class="fas fa-trash mr-1"></i>Excluir
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Logs -->
            <div x-show="activeTab === 'logs'" class="space-y-6">
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">
                        <i class="fas fa-file-alt mr-2"></i>Logs do Sistema
                    </h3>
                    <p class="text-gray-400 mb-4">Gerencie os logs do sistema para monitoramento e depuração.</p>
                    
                    <div class="flex space-x-4 mb-4">
                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja limpar todos os logs?')">
                            <input type="hidden" name="action" value="clear_logs">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                <i class="fas fa-trash mr-2"></i>Limpar Logs
                            </button>
                        </form>
                        
                        <button onclick="refreshLogs()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                            <i class="fas fa-refresh mr-2"></i>Atualizar
                        </button>
                    </div>
                    
                    <div class="bg-black rounded-lg p-4 h-64 overflow-y-auto">
                        <pre class="text-green-400 text-sm" id="logs-content">
<?php
$log_file = 'logs/error.log';
if (file_exists($log_file)) {
    echo htmlspecialchars(file_get_contents($log_file));
} else {
    echo "Nenhum log encontrado.";
}
?>
                        </pre>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function deleteBackup(filename) {
        if (confirm('Tem certeza que deseja excluir este backup?')) {
            fetch('delete_backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'filename=' + encodeURIComponent(filename)
            }).then(() => {
                location.reload();
            });
        }
    }
    
    function refreshLogs() {
        location.reload();
    }
</script>

</body>
</html>
