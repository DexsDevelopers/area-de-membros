<?php
session_start();
// Garante que apenas administradores possam acessar esta página
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Redirecionar para o dashboard simples que funciona
header("Location: admin_dashboard_simples.php");
exit();
require 'config.php';

try {
    // --- DADOS PARA OS CARDS (sem alteração) ---
    $totalMembros = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'user'")->fetchColumn();
    $totalCursos = $pdo->query("SELECT COUNT(id) FROM cursos WHERE ativo = 1")->fetchColumn();
    $totalConclusoes = $pdo->query("SELECT COUNT(id) FROM user_progresso")->fetchColumn();
    $atividadesRecentes = $pdo->query(
        "SELECT u.username, c.titulo AS curso_titulo, up.data_conclusao FROM user_progresso up
         JOIN users u ON up.user_id = u.id JOIN cursos c ON up.curso_id = c.id
         ORDER BY up.data_conclusao DESC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    // ===================================================================
    // 1. PREPARAÇÃO DOS DADOS PARA O GRÁFICO (PHP)
    // ===================================================================
    $dadosGraficoStmt = $pdo->query(
        "SELECT DATE_FORMAT(data_cadastro, '%Y-%m') as mes, COUNT(id) as total
         FROM users
         WHERE role = 'user' AND data_cadastro >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
         GROUP BY mes
         ORDER BY mes ASC"
    );
    $dadosGrafico = $dadosGraficoStmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata os dados para o JavaScript
    $labelsGrafico = [];
    $valoresGrafico = [];
    foreach ($dadosGrafico as $dado) {
        // Converte '2025-07' para 'Jul/25'
        $data = DateTime::createFromFormat('!Y-m', $dado['mes']);
        $labelsGrafico[] = $data->format('M/y');
        $valoresGrafico[] = $dado['total'];
    }
    // Converte os arrays PHP para JSON para serem usados no JavaScript
    $jsonLabels = json_encode($labelsGrafico);
    $jsonValores = json_encode($valoresGrafico);

} catch (PDOException $e) {
    error_log("Erro no dashboard do admin: " . $e->getMessage());
    die("Ocorreu um erro ao carregar as estatísticas. Verifique o log de erros.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Dashboard | HELMER ACADEMY</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .fade-in { animation: fadeIn 0.8s ease-out forwards; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-gray-200 min-h-screen font-sans">

<div class="flex flex-col md:flex-row min-h-screen">
    <aside class="bg-black/30 backdrop-blur-md w-full md:w-64 p-6 flex-shrink-0">
        <h1 class="text-2xl font-bold text-white mb-8">ADMIN PAINEL</h1>
        <nav class="flex flex-col space-y-2">
            <a href="dashboard_admin.php" class="px-4 py-2 rounded-lg bg-rose-600 text-white transition-colors"><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</a>
            <a href="admin_painel.php" class="px-4 py-2 rounded-lg hover:bg-rose-600 transition-colors"><i class="fas fa-users mr-2"></i>Gerenciar Usuários</a>
            <a href="cadastro-painel.php" class="px-4 py-2 rounded-lg hover:bg-rose-600 transition-colors"><i class="fas fa-plus-circle mr-2"></i>Cadastrar Conteúdo</a>
            <a href="gerenciar_categorias.php" class="px-4 py-2 rounded-lg hover:bg-rose-600 transition-colors"><i class="fas fa-tags mr-2"></i>Gerenciar Categorias</a>
            <a href="gerenciar_banners.php" class="px-4 py-2 rounded-lg hover:bg-rose-600 transition-colors"><i class="fas fa-images mr-2"></i>Gerenciar Banners</a>
        </nav>
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="block w-full bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-center font-semibold transition-colors">Sair</a>
        </div>
    </aside>

    <main class="flex-1 p-4 sm:p-6 lg:p-8">
        <div class="container mx-auto">
            <header class="mb-10 fade-in">
                <h2 class="text-3xl md:text-4xl font-extrabold text-white">Dashboard de Visão Geral</h2>
                <p class="text-gray-400 mt-2">Acompanhe as métricas e o engajamento da sua plataforma.</p>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                </div>

            <div class="bg-gray-800/80 backdrop-blur-sm p-6 rounded-2xl shadow-2xl mb-10 fade-in" style="animation-delay: 400ms;">
                <h3 class="text-xl font-bold text-white mb-4">Novos Membros (Últimos 12 Meses)</h3>
                <div>
                    <canvas id="graficoNovosMembros"></canvas>
                </div>
            </div>

            <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-2xl overflow-hidden fade-in" style="animation-delay: 500ms;">
                </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('graficoNovosMembros').getContext('2d');
        
        // Pega os dados que o PHP preparou
        const labels = <?php echo $jsonLabels; ?>;
        const dataValues = <?php echo $jsonValores; ?>;

        const data = {
            labels: labels,
            datasets: [{
                label: 'Novos Membros',
                data: dataValues,
                backgroundColor: 'rgba(225, 29, 72, 0.5)', // Cor Rose-600 com 50% de opacidade
                borderColor: 'rgba(225, 29, 72, 1)',     // Cor Rose-600 sólida
                borderWidth: 2,
                borderRadius: 5,
                hoverBackgroundColor: 'rgba(225, 29, 72, 0.8)'
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#f3f4f6',
                        bodyColor: '#d1d5db',
                        padding: 10,
                        cornerRadius: 5,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            color: '#9ca3af',
                            stepSize: 1 // Força a escala a ser de 1 em 1, se fizer sentido para poucos usuários
                        },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    x: {
                        ticks: { color: '#9ca3af' },
                        grid: { display: false }
                    }
                }
            }
        };

        new Chart(ctx, config);
    });
</script>

</body>
</html>