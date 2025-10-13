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

// Filtros de data
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$tipo_relatorio = $_GET['tipo'] ?? 'geral';

// Função para executar consulta com segurança
function safeQuery($pdo, $sql, $default = 0, $params = []) {
    try {
        if (!empty($params)) {
            $stmt = $pdo->prepare($sql);
            if ($stmt && $stmt->execute($params)) {
                $result = $stmt->fetchColumn();
                return $result !== false ? $result : $default;
            }
        } else {
            $stmt = $pdo->query($sql);
            if ($stmt) {
                $result = $stmt->fetchColumn();
                return $result !== false ? $result : $default;
            }
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

// Relatório geral do período
$total_usuarios_periodo = safeQuery($pdo, "
    SELECT COUNT(*) FROM users 
    WHERE role = 'user' AND data_cadastro BETWEEN ? AND ?
", 0, [$data_inicio, $data_fim]);

$total_cursos_periodo = safeQuery($pdo, "
    SELECT COUNT(*) FROM cursos 
    WHERE ativo = 1 AND data_postagem BETWEEN ? AND ?
", 0, [$data_inicio, $data_fim]);

$total_produtos_periodo = safeQuery($pdo, "
    SELECT COUNT(*) FROM produtos 
    WHERE ativo = 1 AND data_cadastro BETWEEN ? AND ?
", 0, [$data_inicio, $data_fim]);

// Relatório de usuários por mês
$usuarios_mes = safeQueryArray($pdo, "
    SELECT DATE_FORMAT(data_cadastro, '%Y-%m') as mes, COUNT(*) as total
    FROM users 
    WHERE role = 'user' AND data_cadastro >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mes
    ORDER BY mes ASC
");

// Relatório de cursos por categoria
$cursos_categoria = safeQueryArray($pdo, "
    SELECT c.nome as categoria, COUNT(cur.id) as total_cursos
    FROM categorias c
    LEFT JOIN cursos cur ON c.id = cur.categoria_id AND cur.ativo = 1
    GROUP BY c.id, c.nome
    ORDER BY total_cursos DESC
");

// Relatório de favoritos
$favoritos_curso = safeQueryArray($pdo, "
    SELECT c.titulo, COUNT(f.id) as total_favoritos
    FROM cursos c
    LEFT JOIN favoritos f ON c.id = f.curso_id
    WHERE c.ativo = 1
    GROUP BY c.id, c.titulo
    ORDER BY total_favoritos DESC
    LIMIT 10
");

$favoritos_produto = safeQueryArray($pdo, "
    SELECT p.nome, COUNT(f.id) as total_favoritos
    FROM produtos p
    LEFT JOIN favoritos f ON p.id = f.produto_id
    WHERE p.ativo = 1
    GROUP BY p.id, p.nome
    ORDER BY total_favoritos DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-black/90 backdrop-blur-lg border-r border-gray-800 lg:translate-x-0 lg:static lg:inset-0" 
         :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-white">RELATÓRIOS</span>
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
            
            <a href="admin_painel_simples.php" class="flex items-center px-4 py-3 text-gray-300 hover:text-white hover:bg-gray-800/50 rounded-lg transition">
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
            
            <a href="relatorios_simples.php" class="flex items-center px-4 py-3 text-white bg-red-600/20 rounded-lg border border-red-600/30">
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
                        <h1 class="text-2xl font-bold text-white">Relatórios e Analytics</h1>
                        <p class="text-gray-400 text-sm">Análise de dados e métricas do sistema</p>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Filtros -->
            <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 p-6 mb-8">
                <h3 class="text-lg font-semibold text-white mb-4">Filtros de Período</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Data Início</label>
                        <input type="date" name="data_inicio" value="<?= $data_inicio ?>" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Data Fim</label>
                        <input type="date" name="data_fim" value="<?= $data_fim ?>" 
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tipo de Relatório</label>
                        <select name="tipo" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="geral" <?= $tipo_relatorio === 'geral' ? 'selected' : '' ?>>Geral</option>
                            <option value="usuarios" <?= $tipo_relatorio === 'usuarios' ? 'selected' : '' ?>>Usuários</option>
                            <option value="cursos" <?= $tipo_relatorio === 'cursos' ? 'selected' : '' ?>>Cursos</option>
                            <option value="produtos" <?= $tipo_relatorio === 'produtos' ? 'selected' : '' ?>>Produtos</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                            <i class="fas fa-filter mr-2"></i>Filtrar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="card-hover bg-gradient-to-r from-red-600 to-red-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100 text-sm font-medium">Usuários no Período</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($total_usuarios_periodo) ?></p>
                            <p class="text-red-200 text-xs"><?= date('d/m/Y', strtotime($data_inicio)) ?> - <?= date('d/m/Y', strtotime($data_fim)) ?></p>
                        </div>
                        <i class="fas fa-users text-red-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="card-hover bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Cursos no Período</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($total_cursos_periodo) ?></p>
                            <p class="text-blue-200 text-xs"><?= date('d/m/Y', strtotime($data_inicio)) ?> - <?= date('d/m/Y', strtotime($data_fim)) ?></p>
                        </div>
                        <i class="fas fa-graduation-cap text-blue-300 text-3xl"></i>
                    </div>
                </div>
                
                <div class="card-hover bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Produtos no Período</p>
                            <p class="text-3xl font-bold text-white"><?= number_format($total_produtos_periodo) ?></p>
                            <p class="text-green-200 text-xs"><?= date('d/m/Y', strtotime($data_inicio)) ?> - <?= date('d/m/Y', strtotime($data_fim)) ?></p>
                        </div>
                        <i class="fas fa-shopping-bag text-green-300 text-3xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Gráfico de Usuários por Mês -->
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Usuários por Mês</h3>
                    <canvas id="usuariosChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Gráfico de Cursos por Categoria -->
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Cursos por Categoria</h3>
                    <canvas id="cursosChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Tabelas de Favoritos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Cursos Mais Favoritados -->
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Cursos Mais Favoritados</h3>
                    <div class="space-y-3">
                        <?php if (!empty($favoritos_curso)): ?>
                            <?php foreach ($favoritos_curso as $index => $curso): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="w-6 h-6 bg-red-600 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                        <?= $index + 1 ?>
                                    </span>
                                    <span class="text-white text-sm"><?= htmlspecialchars($curso['titulo']) ?></span>
                                </div>
                                <span class="text-red-400 font-semibold"><?= $curso['total_favoritos'] ?> ❤️</span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-400 text-center py-4">Nenhum curso favoritado ainda</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Produtos Mais Favoritados -->
                <div class="card-hover bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Produtos Mais Favoritados</h3>
                    <div class="space-y-3">
                        <?php if (!empty($favoritos_produto)): ?>
                            <?php foreach ($favoritos_produto as $index => $produto): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                        <?= $index + 1 ?>
                                    </span>
                                    <span class="text-white text-sm"><?= htmlspecialchars($produto['nome']) ?></span>
                                </div>
                                <span class="text-blue-400 font-semibold"><?= $produto['total_favoritos'] ?> ❤️</span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-400 text-center py-4">Nenhum produto favoritado ainda</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Gráfico de Usuários por Mês
const usuariosCtx = document.getElementById('usuariosChart').getContext('2d');
new Chart(usuariosCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($usuarios_mes, 'mes')) ?>,
        datasets: [{
            label: 'Usuários',
            data: <?= json_encode(array_column($usuarios_mes, 'total')) ?>,
            borderColor: '#e11d48',
            backgroundColor: 'rgba(225, 29, 72, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: {
                    color: '#ffffff'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#9ca3af'
                },
                grid: {
                    color: 'rgba(156, 163, 175, 0.1)'
                }
            },
            y: {
                ticks: {
                    color: '#9ca3af'
                },
                grid: {
                    color: 'rgba(156, 163, 175, 0.1)'
                }
            }
        }
    }
});

// Gráfico de Cursos por Categoria
const cursosCtx = document.getElementById('cursosChart').getContext('2d');
new Chart(cursosCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($cursos_categoria, 'categoria')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($cursos_categoria, 'total_cursos')) ?>,
            backgroundColor: [
                '#e11d48', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6',
                '#ef4444', '#06b6d4', '#84cc16', '#f97316', '#ec4899'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: {
                    color: '#ffffff'
                }
            }
        }
    }
});
</script>

</body>
</html>
