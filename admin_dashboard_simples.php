<?php
session_start();
require 'config.php';

// Verificar se é administrador
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Inicializar variáveis
$total_usuarios = 0;
$total_cursos = 0;
$total_produtos = 0;
$total_vendas = 0;
$usuarios_recentes = [];
$cursos_populares = [];
$atividade_recente = [];
$labels_grafico = [];
$valores_grafico = [];

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
function safeQueryArray($pdo, $sql, $default = []) {
    try {
        $stmt = $pdo->query($sql);
        if ($stmt) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result ? $result : $default;
        }
        return $default;
    } catch (Exception $e) {
        error_log("Erro na consulta: " . $e->getMessage() . " - SQL: " . $sql);
        return $default;
    }
}

// Buscar estatísticas básicas
$total_usuarios = safeQuery($pdo, "SELECT COUNT(*) FROM users WHERE role = 'user'");
$total_cursos = safeQuery($pdo, "SELECT COUNT(*) FROM cursos WHERE ativo = 1");
$total_produtos = safeQuery($pdo, "SELECT COUNT(*) FROM produtos WHERE ativo = 1");

// Verificar se tabela vendas existe e contar
$vendas_exists = safeQuery($pdo, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'vendas'");
if ($vendas_exists > 0) {
    $total_vendas = safeQuery($pdo, "SELECT COUNT(*) FROM vendas");
}

// Buscar dados adicionais
$usuarios_recentes = safeQueryArray($pdo, "
    SELECT username, data_cadastro 
    FROM users 
    WHERE role = 'user' AND data_cadastro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY data_cadastro DESC 
    LIMIT 5
");

$cursos_populares = safeQueryArray($pdo, "
    SELECT c.titulo, COUNT(f.id) as favoritos
    FROM cursos c
    LEFT JOIN favoritos f ON c.id = f.curso_id
    WHERE c.ativo = 1
    GROUP BY c.id, c.titulo
    ORDER BY favoritos DESC
    LIMIT 5
");

$atividade_recente = safeQueryArray($pdo, "
    SELECT 'curso' as tipo, titulo as nome, data_postagem as data
    FROM cursos 
    WHERE ativo = 1
    UNION ALL
    SELECT 'produto' as tipo, nome, data_cadastro as data
    FROM produtos 
    WHERE ativo = 1
    ORDER BY data DESC
    LIMIT 10
");

$dados_grafico = safeQueryArray($pdo, "
    SELECT DATE_FORMAT(data_cadastro, '%Y-%m') as mes, COUNT(*) as total
    FROM users
    WHERE role = 'user' AND data_cadastro >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mes
    ORDER BY mes ASC
");

// Preparar dados para o gráfico
foreach ($dados_grafico as $dado) {
    try {
        $data = DateTime::createFromFormat('!Y-m', $dado['mes']);
        if ($data) {
            $labels_grafico[] = $data->format('M/y');
            $valores_grafico[] = intval($dado['total']);
        }
    } catch (Exception $e) {
        // Ignorar erros de data
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        }
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
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
        .fade-in:nth-child(5) { animation-delay: 0.5s; }
        .fade-in:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body class="gradient-bg text-white font-sans min-h-screen">

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    <!-- Sidebar -->
    <div class="sidebar-transition fixed inset-y-0 left-0 z-50 w-64 bg-black/90 backdrop-blur-lg border-r border-gray-800 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <!-- Logo -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-crown text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white">HELMER ADMIN</span>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="admin_dashboard_simples.php" class="flex items-center px-4 py-3 text-white bg-red-600/20 rounded-lg border border-red-600/30">
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
            
            <a href="gerenciar_banners.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
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
        
        <!-- User Info -->
        <div class="px-4 py-4 border-t border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-white"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($_SESSION['user']) ?></p>
                    <p class="text-xs text-gray-400">Administrador</p>
                </div>
            </div>
            <a href="logout.php" class="block mt-3 w-full text-center bg-gray-700 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition">
                <i class="fas fa-sign-out-alt mr-2"></i>Sair
            </a>
        </div>
    </div>
    
    <!-- Overlay para mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/50 z-40 lg:hidden" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>
    
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
                        <h1 class="text-2xl font-bold text-white">Dashboard</h1>
                        <p class="text-gray-400 text-sm">Visão geral da plataforma</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Debug Link -->
                    <a href="debug_dashboard.php" class="text-blue-400 hover:text-blue-300 text-sm">
                        <i class="fas fa-bug mr-1"></i>Debug
                    </a>
                    
                    <!-- Data atual -->
                    <div class="text-right">
                        <p class="text-sm text-white"><?= date('d/m/Y') ?></p>
                        <p class="text-xs text-gray-400"><?= date('H:i') ?></p>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="fade-in card-hover bg-gradient-to-r from-red-600 to-red-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium">Total de Usuários</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($total_usuarios) ?></p>
                            <p class="text-red-200 text-xs">Usuários registrados</p>
                        </div>
                        <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-red-300 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Cursos Ativos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($total_cursos) ?></p>
                            <p class="text-blue-200 text-xs">Cursos disponíveis</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-blue-300 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Produtos</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($total_produtos) ?></p>
                            <p class="text-green-200 text-xs">Produtos ativos</p>
                        </div>
                        <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-green-300 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="fade-in card-hover bg-gradient-to-r from-purple-600 to-purple-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Vendas</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($total_vendas) ?></p>
                            <p class="text-purple-200 text-xs">Total de vendas</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-300 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico e Tabelas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Gráfico de Usuários -->
                <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">Crescimento de Usuários</h3>
                    <div class="h-64">
                        <canvas id="usersChart"></canvas>
                    </div>
                </div>
                
                <!-- Cursos Populares -->
                <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold text-white mb-4">Cursos Mais Populares</h3>
                    <div class="space-y-3">
                        <?php if (!empty($cursos_populares)): ?>
                            <?php foreach ($cursos_populares as $index => $curso): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium"><?= htmlspecialchars($curso['titulo']) ?></p>
                                        <p class="text-gray-400 text-sm"><?= $curso['favoritos'] ?> favoritos</p>
                                    </div>
                                </div>
                                <i class="fas fa-heart text-red-500"></i>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-400 text-center">Nenhum curso encontrado</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tabelas de Atividade -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Usuários Recentes -->
                <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">Usuários Recentes</h3>
                        <a href="admin_painel_moderno.php" class="text-red-400 hover:text-red-300 text-sm">Ver todos</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (!empty($usuarios_recentes)): ?>
                            <?php foreach ($usuarios_recentes as $usuario): ?>
                            <div class="flex items-center space-x-3 p-3 bg-gray-700/50 rounded-lg">
                                <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-medium"><?= htmlspecialchars($usuario['username']) ?></p>
                                    <p class="text-gray-400 text-sm"><?= date('d/m/Y H:i', strtotime($usuario['data_cadastro'])) ?></p>
                                </div>
                                <span class="text-green-400 text-xs bg-green-400/20 px-2 py-1 rounded-full">Novo</span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-400 text-center">Nenhum usuário recente</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Atividade Recente -->
                <div class="fade-in card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">Atividade Recente</h3>
                        <a href="#" class="text-red-400 hover:text-red-300 text-sm">Ver todas</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (!empty($atividade_recente)): ?>
                            <?php foreach ($atividade_recente as $atividade): ?>
                            <div class="flex items-center space-x-3 p-3 bg-gray-700/50 rounded-lg">
                                <div class="w-10 h-10 <?= $atividade['tipo'] === 'curso' ? 'bg-blue-600' : 'bg-green-600' ?> rounded-full flex items-center justify-center">
                                    <i class="fas <?= $atividade['tipo'] === 'curso' ? 'fa-graduation-cap' : 'fa-shopping-bag' ?> text-white text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-medium"><?= htmlspecialchars($atividade['nome']) ?></p>
                                    <p class="text-gray-400 text-sm"><?= ucfirst($atividade['tipo']) ?> • <?= date('d/m/Y H:i', strtotime($atividade['data'])) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-400 text-center">Nenhuma atividade recente</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Gráfico de usuários
    const ctx = document.getElementById('usersChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_grafico) ?>,
            datasets: [{
                label: 'Novos Usuários',
                data: <?= json_encode($valores_grafico) ?>,
                borderColor: '#e11d48',
                backgroundColor: 'rgba(225, 29, 72, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#e11d48',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#9ca3af'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#9ca3af'
                    }
                }
            }
        }
    });
    
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
