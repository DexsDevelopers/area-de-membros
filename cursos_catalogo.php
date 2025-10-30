<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require 'config.php';
require 'cache.php';

// Parâmetros
$search = trim($_GET['q'] ?? '');
$categoria = intval($_GET['categoria'] ?? 0);
$tipo = $_GET['tipo'] ?? 'all'; // all | gratuitos | premium
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$limite = 12;
$offset = ($pagina - 1) * $limite;

try {
    // Categorias via cache
    $categorias = $cache->remember('categorias_catalogo', function() use ($pdo) {
        return $pdo->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    }, 1800);

    // Montar filtros
    $where = ["c.ativo = 1", "(c.data_publicacao IS NULL OR c.data_publicacao <= NOW())"]; // consistência com index
    $params = [];

    if ($search !== '') {
        $where[] = "(c.titulo LIKE ? OR c.descricao LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($categoria > 0) {
        $where[] = "c.categoria_id = ?";
        $params[] = $categoria;
    }

    if (in_array($tipo, ['gratuitos', 'premium'], true)) {
        $where[] = "c.tipo = ?";
        $params[] = $tipo;
    }

    $whereClause = implode(' AND ', $where);

    // Total para paginação
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM cursos c WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPaginas = (int)ceil($total / $limite);

    // Buscar cursos
    $sql = "SELECT c.id, c.titulo, c.imagem, c.tipo, c.data_postagem, c.descricao, cat.nome AS categoria_nome,
                   (SELECT COUNT(*) FROM favoritos f WHERE f.curso_id = c.id) AS total_favoritos
            FROM cursos c
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE $whereClause
            ORDER BY c.data_postagem DESC
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([...$params, $limite, $offset]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Erro no catálogo de cursos: ' . $e->getMessage());
    $categorias = [];
    $cursos = [];
    $total = 0; $totalPaginas = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Cursos | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #000000 0%, #1a0000 25%, #2d0000 50%, #1a0000 75%, #000000 100%);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
        }
        @keyframes gradientShift { 0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%} }
        .card-premium { background: linear-gradient(145deg, rgba(220,38,38,0.12) 0%, rgba(0,0,0,0.35) 50%, rgba(220,38,38,0.06) 100%); backdrop-filter: blur(20px); border: 1px solid rgba(220,38,38,0.25); transition: transform .35s cubic-bezier(.4,0,.2,1), box-shadow .35s; }
        .card-premium:hover { transform: translateY(-6px); box-shadow: 0 25px 50px rgba(220,38,38,.25), 0 0 30px rgba(220,38,38,.2); }
        .fade-in { opacity: 0; transform: translateY(20px); transition: all .6s cubic-bezier(.4,0,.2,1); }
        .fade-in.show { opacity: 1; transform: translateY(0); }
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
    </style>
</head>
<body class="gradient-bg text-white font-sans min-h-screen">

<div x-data="{ filtros: false }" class="min-h-screen">
    <!-- Header / Hero -->
    <header class="p-6 lg:p-8 sticky top-0 z-40 backdrop-blur-xl border-b border-white/10 bg-black/30">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <a href="index.php" class="p-3 bg-white/10 rounded-xl hover:bg-white/20 transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-extrabold">Catálogo de Cursos</h1>
                    <p class="text-gray-400 text-sm">Explore todos os cursos disponíveis</p>
                </div>
            </div>
            <form method="GET" class="flex-1">
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar cursos..."
                               class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/10 focus:outline-none focus:ring-2 focus:ring-red-500 placeholder-gray-400" />
                        <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button type="button" @click="filtros = !filtros" class="px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/10">
                        <i class="fas fa-sliders-h mr-2"></i>Filtros
                    </button>
                    <button type="submit" class="px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 font-bold">
                        Buscar
                    </button>
                </div>
            </form>
        </div>
    </header>

    <!-- Filtros -->
    <section x-show="filtros" x-transition class="border-b border-white/10 bg-black/30">
        <div class="max-w-7xl mx-auto p-6 lg:p-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-300 mb-2">Categoria</label>
                    <select name="categoria" class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/10 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option value="0">Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoria === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-300 mb-2">Tipo</label>
                    <select name="tipo" class="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/10 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option value="all" <?= $tipo === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="gratuitos" <?= $tipo === 'gratuitos' ? 'selected' : '' ?>>Gratuitos</option>
                        <option value="premium" <?= $tipo === 'premium' ? 'selected' : '' ?>>Premium</option>
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 font-bold w-full">Aplicar</button>
                    <a href="cursos_catalogo.php" class="px-5 py-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/10 text-center w-full">Limpar</a>
                </div>
            </form>
        </div>
    </section>

    <!-- Conteúdo -->
    <main class="max-w-7xl mx-auto p-6 lg:p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl text-gray-300">Resultados</h2>
            <span class="text-gray-400 text-sm"><?= number_format($total) ?> curso(s)</span>
        </div>

        <!-- Grid de Cursos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($cursos as $curso): ?>
            <div class="card-premium rounded-2xl overflow-hidden fade-in border border-white/10">
                <a href="curso_pagina.php?id=<?= $curso['id'] ?>" class="block relative">
                    <?php if (!empty($curso['imagem'])): ?>
                        <img src="/<?= htmlspecialchars($curso['imagem']) ?>" alt="<?= htmlspecialchars($curso['titulo']) ?>" class="w-full h-48 object-cover" loading="lazy" />
                    <?php else: ?>
                        <div class="w-full h-48 bg-gray-800 flex items-center justify-center"><i class="fas fa-graduation-cap text-3xl text-gray-500"></i></div>
                    <?php endif; ?>
                    <span class="absolute top-3 left-3 text-xs px-2 py-1 rounded-full <?= $curso['tipo']==='premium' ? 'bg-purple-600' : 'bg-green-600' ?>"><?= ucfirst($curso['tipo']) ?></span>
                </a>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="text-lg font-bold line-clamp-2"><?= htmlspecialchars($curso['titulo']) ?></h3>
                        <button onclick="toggleFavorite(<?= $curso['id'] ?>)" class="text-xl text-red-500/70 hover:text-red-400" title="Adicionar aos favoritos">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                    <p class="text-sm text-gray-400 mb-2"><?= $curso['categoria_nome'] ? htmlspecialchars($curso['categoria_nome']) : 'Sem categoria' ?></p>
                    <p class="text-sm text-gray-300 line-clamp-3 mb-4"><?= htmlspecialchars($curso['descricao']) ?></p>
                    <div class="flex items-center justify-between text-sm text-gray-400">
                        <span><i class="fas fa-heart mr-1"></i><?= (int)$curso['total_favoritos'] ?> favoritos</span>
                        <span><?= date('d/m/Y', strtotime($curso['data_postagem'])) ?></span>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="curso_pagina.php?id=<?= $curso['id'] ?>" class="flex-1 text-center bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Ver Curso</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($cursos)): ?>
        <div class="text-center py-16">
            <i class="fas fa-graduation-cap text-6xl text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-400 mb-2">Nenhum curso encontrado</h3>
            <p class="text-gray-500">Tente ajustar os filtros ou a busca.</p>
        </div>
        <?php endif; ?>

        <!-- Paginação -->
        <?php if ($totalPaginas > 1): ?>
        <div class="mt-10 flex justify-center">
            <nav class="flex gap-2">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <?php $q = $_GET; $q['pagina'] = $i; $href = '?' . http_build_query($q); ?>
                    <a href="<?= $href ?>" class="px-4 py-2 rounded-lg <?= $i === $pagina ? 'bg-red-600 text-white' : 'bg-white/10 hover:bg-white/20 border border-white/10' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
// Fade-in on view
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('show'); });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

// Favoritos
async function toggleFavorite(cursoId) {
    try {
        const form = new FormData();
        form.append('action', 'toggle');
        form.append('curso_id', String(cursoId));
        const res = await fetch('favoritos.php', { method: 'POST', body: form });
        const data = await res.json();
        showToast(data.message || 'Atualizado');
    } catch (e) {
        showToast('Erro ao atualizar favorito', 'error');
    }
}

function showToast(msg, type='info') {
    const el = document.createElement('div');
    el.className = `fixed bottom-6 right-6 z-50 px-4 py-3 rounded-lg shadow-lg text-white ${type==='error'?'bg-red-600':'bg-green-600'}`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(()=>{ el.remove(); }, 2500);
}
</script>

</body>
</html>


