<?php
/**
 * Sistema de Busca Avançada
 * Permite buscar cursos e produtos com múltiplos filtros
 */

session_start();
require 'config.php';
require 'cache.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Parâmetros de busca
$search = trim($_GET['search'] ?? '');
$categoria = intval($_GET['categoria'] ?? 0);
$tipo = $_GET['tipo'] ?? 'all';
$preco_min = floatval($_GET['preco_min'] ?? 0);
$preco_max = floatval($_GET['preco_max'] ?? 0);
$ordenar = $_GET['ordenar'] ?? 'recentes';
$pagina = intval($_GET['pagina'] ?? 1);
$limite = 12;
$offset = ($pagina - 1) * $limite;

try {
    // Buscar categorias para o filtro
    $categorias = $cache->remember('categorias_busca', function() use ($pdo) {
        return $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    }, 1800);
    
    // Construir query de busca para cursos
    $where_conditions = ["c.ativo = 1"];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(c.titulo LIKE ? OR c.descricao LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($categoria > 0) {
        $where_conditions[] = "c.categoria_id = ?";
        $params[] = $categoria;
    }
    
    if ($tipo !== 'all') {
        $where_conditions[] = "c.tipo = ?";
        $params[] = $tipo;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Ordenação
    $order_clause = "ORDER BY c.data_postagem DESC";
    switch ($ordenar) {
        case 'alfabetica':
            $order_clause = "ORDER BY c.titulo ASC";
            break;
        case 'antigos':
            $order_clause = "ORDER BY c.data_postagem ASC";
            break;
        case 'tipo':
            $order_clause = "ORDER BY c.tipo ASC, c.data_postagem DESC";
            break;
    }
    
    // Query para cursos
    $sql_cursos = "SELECT c.id, c.titulo, c.imagem, c.tipo, c.data_postagem, c.descricao, 
                          cat.nome as categoria_nome
                   FROM cursos c 
                   LEFT JOIN categorias cat ON c.categoria_id = cat.id
                   WHERE $where_clause 
                   $order_clause 
                   LIMIT $limite OFFSET $offset";
    
    $stmt_cursos = $pdo->prepare($sql_cursos);
    $stmt_cursos->execute($params);
    $cursos = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);
    
    // Query para produtos (se não há filtro de tipo específico para cursos)
    $produtos = [];
    if ($tipo === 'all' || $tipo === 'produtos') {
        $where_produtos = ["p.ativo = 1"];
        $params_produtos = [];
        
        if ($search) {
            $where_produtos[] = "(p.nome LIKE ? OR p.descricao LIKE ?)";
            $params_produtos[] = "%$search%";
            $params_produtos[] = "%$search%";
        }
        
        if ($preco_min > 0) {
            $where_produtos[] = "p.preco >= ?";
            $params_produtos[] = $preco_min;
        }
        
        if ($preco_max > 0) {
            $where_produtos[] = "p.preco <= ?";
            $params_produtos[] = $preco_max;
        }
        
        $where_produtos_clause = implode(' AND ', $where_produtos);
        
        $sql_produtos = "SELECT p.id, p.nome, p.imagem, p.preco, p.descricao, p.data_cadastro
                         FROM produtos p 
                         WHERE $where_produtos_clause 
                         ORDER BY p.data_cadastro DESC
                         LIMIT $limite OFFSET $offset";
        
        $stmt_produtos = $pdo->prepare($sql_produtos);
        $stmt_produtos->execute($params_produtos);
        $produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Contar total de resultados para paginação
    $count_sql = "SELECT COUNT(*) as total FROM cursos c WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_cursos = $count_stmt->fetchColumn();
    
    $total_paginas = ceil($total_cursos / $limite);
    
} catch (Exception $e) {
    error_log("Erro na busca avançada: " . $e->getMessage());
    $cursos = [];
    $produtos = [];
    $categorias = [];
    $total_paginas = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busca Avançada | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in { opacity: 0; transform: translateY(20px); transition: all 0.6s ease-out; }
        .fade-in.show { opacity: 1; transform: translateY(0); }
        .search-highlight { background-color: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white font-sans">

<div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar de Filtros -->
    <aside class="w-full md:w-80 bg-gray-800/50 backdrop-blur-sm p-6">
        <h2 class="text-xl font-bold mb-6 text-red-400">Filtros de Busca</h2>
        
        <form method="GET" class="space-y-6">
            <!-- Busca por texto -->
            <div>
                <label class="block text-sm font-medium mb-2">Buscar</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Digite o que procura..." 
                       class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            
            <!-- Filtro por categoria -->
            <div>
                <label class="block text-sm font-medium mb-2">Categoria</label>
                <select name="categoria" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="0">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoria == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro por tipo -->
            <div>
                <label class="block text-sm font-medium mb-2">Tipo</label>
                <select name="tipo" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="all" <?= $tipo === 'all' ? 'selected' : '' ?>>Todos</option>
                    <option value="gratuitos" <?= $tipo === 'gratuitos' ? 'selected' : '' ?>>Gratuitos</option>
                    <option value="premium" <?= $tipo === 'premium' ? 'selected' : '' ?>>Premium</option>
                    <option value="produtos" <?= $tipo === 'produtos' ? 'selected' : '' ?>>Produtos</option>
                </select>
            </div>
            
            <!-- Filtro por preço (apenas para produtos) -->
            <div x-show="tipo === 'produtos' || tipo === 'all'">
                <label class="block text-sm font-medium mb-2">Faixa de Preço</label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" name="preco_min" value="<?= $preco_min ?>" 
                           placeholder="Mín" step="0.01" min="0"
                           class="p-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <input type="number" name="preco_max" value="<?= $preco_max ?>" 
                           placeholder="Máx" step="0.01" min="0"
                           class="p-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>
            
            <!-- Ordenação -->
            <div>
                <label class="block text-sm font-medium mb-2">Ordenar por</label>
                <select name="ordenar" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="recentes" <?= $ordenar === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                    <option value="alfabetica" <?= $ordenar === 'alfabetica' ? 'selected' : '' ?>>A-Z</option>
                    <option value="antigos" <?= $ordenar === 'antigos' ? 'selected' : '' ?>>Mais antigos</option>
                    <option value="tipo" <?= $ordenar === 'tipo' ? 'selected' : '' ?>>Por tipo</option>
                </select>
            </div>
            
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Buscar
            </button>
            
            <a href="index.php" class="block w-full text-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded-lg transition">
                <i class="fas fa-times mr-2"></i>Limpar Filtros
            </a>
        </form>
    </aside>
    
    <!-- Conteúdo Principal -->
    <main class="flex-1 p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Resultados da Busca</h1>
            <div class="text-gray-400">
                <?= count($cursos) + count($produtos) ?> resultado(s) encontrado(s)
            </div>
        </div>
        
        <!-- Resultados -->
        <div class="space-y-8">
            <!-- Cursos -->
            <?php if (!empty($cursos)): ?>
            <section class="fade-in">
                <h2 class="text-2xl font-bold mb-4 text-red-400">Cursos</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($cursos as $curso): ?>
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl overflow-hidden group hover:shadow-2xl hover:shadow-red-500/20 transition-all duration-300">
                        <a href="curso_pagina.php?id=<?= $curso['id'] ?>" class="block">
                            <img src="/<?= htmlspecialchars($curso['imagem']) ?>" 
                                 alt="<?= htmlspecialchars($curso['titulo']) ?>" 
                                 class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                        </a>
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-bold text-white line-clamp-2">
                                    <?= $this->highlightSearch($curso['titulo'], $search) ?>
                                </h3>
                                <span class="text-xs bg-red-600 text-white px-2 py-1 rounded-full">
                                    <?= htmlspecialchars($curso['tipo']) ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-400 mb-2">
                                <?= $curso['categoria_nome'] ? htmlspecialchars($curso['categoria_nome']) : 'Sem categoria' ?>
                            </p>
                            <p class="text-sm text-gray-300 line-clamp-3">
                                <?= $this->highlightSearch($curso['descricao'], $search) ?>
                            </p>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-xs text-gray-500">
                                    <?= date('d/m/Y', strtotime($curso['data_postagem'])) ?>
                                </span>
                                <a href="curso_pagina.php?id=<?= $curso['id'] ?>" 
                                   class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                    Ver Curso
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Produtos -->
            <?php if (!empty($produtos)): ?>
            <section class="fade-in">
                <h2 class="text-2xl font-bold mb-4 text-red-400">Produtos</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($produtos as $produto): ?>
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl overflow-hidden group hover:shadow-2xl hover:shadow-red-500/20 transition-all duration-300">
                        <a href="produtos_pagina.php?id=<?= $produto['id'] ?>" class="block">
                            <img src="/<?= htmlspecialchars($produto['imagem']) ?>" 
                                 alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                 class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                        </a>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-white mb-2 line-clamp-2">
                                <?= $this->highlightSearch($produto['nome'], $search) ?>
                            </h3>
                            <p class="text-2xl font-bold text-red-500 mb-2">
                                R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                            </p>
                            <p class="text-sm text-gray-300 line-clamp-3">
                                <?= $this->highlightSearch($produto['descricao'], $search) ?>
                            </p>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-xs text-gray-500">
                                    <?= date('d/m/Y', strtotime($produto['data_cadastro'])) ?>
                                </span>
                                <a href="produtos_pagina.php?id=<?= $produto['id'] ?>" 
                                   class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                    Ver Produto
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Nenhum resultado -->
            <?php if (empty($cursos) && empty($produtos)): ?>
            <div class="text-center py-12">
                <i class="fas fa-search text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-400 mb-2">Nenhum resultado encontrado</h3>
                <p class="text-gray-500">Tente ajustar os filtros ou usar termos diferentes.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="flex space-x-2">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>" 
                       class="px-4 py-2 rounded-lg <?= $i == $pagina ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
    // Animação de fade-in
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

<?php
// Função para destacar termos de busca
function highlightSearch($text, $search) {
    if (empty($search)) {
        return htmlspecialchars($text);
    }
    
    $highlighted = preg_replace(
        '/(' . preg_quote($search, '/') . ')/i',
        '<span class="search-highlight">$1</span>',
        htmlspecialchars($text)
    );
    
    return $highlighted;
}
?>
