<?php
/**
 * Sistema de Favoritos
 * Permite aos usuários favoritar cursos e produtos
 */

session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Criar tabela de favoritos se não existir
$pdo->exec("
    CREATE TABLE IF NOT EXISTS favoritos (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        curso_id INT NULL,
        produto_id INT NULL,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
        UNIQUE KEY unique_favorite (user_id, curso_id, produto_id)
    )
");

// Processar ações (adicionar/remover favorito)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $curso_id = intval($_POST['curso_id'] ?? 0);
    $produto_id = intval($_POST['produto_id'] ?? 0);
    
    if ($action === 'toggle') {
        if ($curso_id > 0) {
            // Verificar se já é favorito
            $stmt = $pdo->prepare("SELECT id FROM favoritos WHERE user_id = ? AND curso_id = ?");
            $stmt->execute([$user_id, $curso_id]);
            
            if ($stmt->fetch()) {
                // Remover favorito
                $stmt = $pdo->prepare("DELETE FROM favoritos WHERE user_id = ? AND curso_id = ?");
                $stmt->execute([$user_id, $curso_id]);
                echo json_encode(['status' => 'removed', 'message' => 'Removido dos favoritos']);
            } else {
                // Adicionar favorito
                $stmt = $pdo->prepare("INSERT INTO favoritos (user_id, curso_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $curso_id]);
                echo json_encode(['status' => 'added', 'message' => 'Adicionado aos favoritos']);
            }
        } elseif ($produto_id > 0) {
            // Verificar se já é favorito
            $stmt = $pdo->prepare("SELECT id FROM favoritos WHERE user_id = ? AND produto_id = ?");
            $stmt->execute([$user_id, $produto_id]);
            
            if ($stmt->fetch()) {
                // Remover favorito
                $stmt = $pdo->prepare("DELETE FROM favoritos WHERE user_id = ? AND produto_id = ?");
                $stmt->execute([$user_id, $produto_id]);
                echo json_encode(['status' => 'removed', 'message' => 'Removido dos favoritos']);
            } else {
                // Adicionar favorito
                $stmt = $pdo->prepare("INSERT INTO favoritos (user_id, produto_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $produto_id]);
                echo json_encode(['status' => 'added', 'message' => 'Adicionado aos favoritos']);
            }
        }
        exit();
    }
}

// Buscar favoritos do usuário
try {
    $favoritos_cursos = $pdo->prepare("
        SELECT c.id, c.titulo, c.imagem, c.tipo, c.data_postagem, c.descricao,
               cat.nome as categoria_nome, f.data_criacao as favoritado_em
        FROM favoritos f
        JOIN cursos c ON f.curso_id = c.id
        LEFT JOIN categorias cat ON c.categoria_id = cat.id
        WHERE f.user_id = ? AND c.ativo = 1
        ORDER BY f.data_criacao DESC
    ");
    $favoritos_cursos->execute([$user_id]);
    $cursos_favoritos = $favoritos_cursos->fetchAll(PDO::FETCH_ASSOC);
    
    $favoritos_produtos = $pdo->prepare("
        SELECT p.id, p.nome, p.imagem, p.preco, p.descricao, p.data_cadastro,
               f.data_criacao as favoritado_em
        FROM favoritos f
        JOIN produtos p ON f.produto_id = p.id
        WHERE f.user_id = ? AND p.ativo = 1
        ORDER BY f.data_criacao DESC
    ");
    $favoritos_produtos->execute([$user_id]);
    $produtos_favoritos = $favoritos_produtos->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erro ao buscar favoritos: " . $e->getMessage());
    $cursos_favoritos = [];
    $produtos_favoritos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Favoritos | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in { opacity: 0; transform: translateY(20px); transition: all 0.6s ease-out; }
        .fade-in.show { opacity: 1; transform: translateY(0); }
        .favorite-btn { transition: all 0.3s ease; }
        .favorite-btn:hover { transform: scale(1.1); }
        .favorite-btn.favorited { color: #ef4444; }
    </style>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white font-sans">

<div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-gray-800/50 backdrop-blur-sm p-6">
        <div class="flex justify-between items-center mb-6">
            <span class="text-xl font-bold text-white">HELMER ACADEMY</span>
        </div>
        <div class="text-sm text-gray-400 mb-6">Bem-vindo, <?= htmlspecialchars($_SESSION['user']) ?></div>
        
        <nav class="flex flex-col space-y-1">
            <a href="index.php" class="px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                <i class="fas fa-home mr-2"></i>Início
            </a>
            <a href="busca_avancada.php" class="px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                <i class="fas fa-search mr-2"></i>Busca Avançada
            </a>
            <a href="favoritos.php" class="px-4 py-2 rounded-lg bg-red-600 text-white">
                <i class="fas fa-heart mr-2"></i>Meus Favoritos
            </a>
            <a href="logout.php" class="px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i>Sair
            </a>
        </nav>
    </aside>
    
    <!-- Conteúdo Principal -->
    <main class="flex-1 p-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Meus Favoritos</h1>
            <p class="text-gray-400">Cursos e produtos que você salvou</p>
        </div>
        
        <!-- Cursos Favoritos -->
        <?php if (!empty($cursos_favoritos)): ?>
        <section class="fade-in mb-12">
            <h2 class="text-2xl font-bold mb-6 text-red-400">
                <i class="fas fa-graduation-cap mr-2"></i>Cursos Favoritos (<?= count($cursos_favoritos) ?>)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($cursos_favoritos as $curso): ?>
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl overflow-hidden group hover:shadow-2xl hover:shadow-red-500/20 transition-all duration-300">
                    <a href="curso_pagina.php?id=<?= $curso['id'] ?>" class="block">
                        <img src="/<?= htmlspecialchars($curso['imagem']) ?>" 
                             alt="<?= htmlspecialchars($curso['titulo']) ?>" 
                             class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                    </a>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-bold text-white line-clamp-2">
                                <?= htmlspecialchars($curso['titulo']) ?>
                            </h3>
                            <button onclick="toggleFavorite(<?= $curso['id'] ?>, 'curso')" 
                                    class="favorite-btn text-2xl text-red-500 hover:text-red-400 favorited"
                                    title="Remover dos favoritos">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <p class="text-sm text-gray-400 mb-2">
                            <?= $curso['categoria_nome'] ? htmlspecialchars($curso['categoria_nome']) : 'Sem categoria' ?>
                        </p>
                        <p class="text-sm text-gray-300 line-clamp-3 mb-4">
                            <?= htmlspecialchars($curso['descricao']) ?>
                        </p>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">
                                Favoritado em <?= date('d/m/Y', strtotime($curso['favoritado_em'])) ?>
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
        
        <!-- Produtos Favoritos -->
        <?php if (!empty($produtos_favoritos)): ?>
        <section class="fade-in mb-12">
            <h2 class="text-2xl font-bold mb-6 text-red-400">
                <i class="fas fa-shopping-bag mr-2"></i>Produtos Favoritos (<?= count($produtos_favoritos) ?>)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($produtos_favoritos as $produto): ?>
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl overflow-hidden group hover:shadow-2xl hover:shadow-red-500/20 transition-all duration-300">
                    <a href="produtos_pagina.php?id=<?= $produto['id'] ?>" class="block">
                        <img src="/<?= htmlspecialchars($produto['imagem']) ?>" 
                             alt="<?= htmlspecialchars($produto['nome']) ?>" 
                             class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                    </a>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-bold text-white line-clamp-2">
                                <?= htmlspecialchars($produto['nome']) ?>
                            </h3>
                            <button onclick="toggleFavorite(<?= $produto['id'] ?>, 'produto')" 
                                    class="favorite-btn text-2xl text-red-500 hover:text-red-400 favorited"
                                    title="Remover dos favoritos">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <p class="text-2xl font-bold text-red-500 mb-2">
                            R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                        </p>
                        <p class="text-sm text-gray-300 line-clamp-3 mb-4">
                            <?= htmlspecialchars($produto['descricao']) ?>
                        </p>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">
                                Favoritado em <?= date('d/m/Y', strtotime($produto['favoritado_em'])) ?>
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
        
        <!-- Nenhum favorito -->
        <?php if (empty($cursos_favoritos) && empty($produtos_favoritos)): ?>
        <div class="text-center py-12 fade-in">
            <i class="fas fa-heart text-6xl text-gray-600 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-400 mb-2">Nenhum favorito ainda</h3>
            <p class="text-gray-500 mb-6">Explore nossos cursos e produtos e adicione aos favoritos!</p>
            <a href="index.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-search mr-2"></i>Explorar Conteúdo
            </a>
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
    
    // Toggle favorito
    async function toggleFavorite(id, type) {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle');
            if (type === 'curso') {
                formData.append('curso_id', id);
            } else {
                formData.append('produto_id', id);
            }
            
            const response = await fetch('favoritos.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.status === 'removed') {
                // Remove o card da página
                const card = event.target.closest('.bg-gray-800\\/50');
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        card.remove();
                        // Atualiza contadores
                        updateCounters();
                    }, 300);
                }
            }
            
            // Mostra notificação
            showNotification(result.message, result.status === 'added' ? 'success' : 'info');
            
        } catch (error) {
            console.error('Erro ao alterar favorito:', error);
            showNotification('Erro ao alterar favorito', 'error');
        }
    }
    
    // Atualizar contadores
    function updateCounters() {
        const cursoCards = document.querySelectorAll('section:first-of-type .bg-gray-800\\/50');
        const produtoCards = document.querySelectorAll('section:last-of-type .bg-gray-800\\/50');
        
        // Atualiza contador de cursos
        const cursoCounter = document.querySelector('h2:first-of-type');
        if (cursoCounter) {
            cursoCounter.innerHTML = `<i class="fas fa-graduation-cap mr-2"></i>Cursos Favoritos (${cursoCards.length})`;
        }
        
        // Atualiza contador de produtos
        const produtoCounter = document.querySelector('h2:last-of-type');
        if (produtoCounter) {
            produtoCounter.innerHTML = `<i class="fas fa-shopping-bag mr-2"></i>Produtos Favoritos (${produtoCards.length})`;
        }
    }
    
    // Mostrar notificação
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 'bg-blue-600'
        } text-white`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
</script>

</body>
</html>
