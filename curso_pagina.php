<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require 'config.php';

// ===================================================================
// 1. ORGANIZAÇÃO DA LÓGICA PHP
// ===================================================================

// Função para converter links do YouTube para o formato de embed
function getYouTubeEmbedUrl($url) {
    $youtube_id = '';
    if (preg_match('/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $youtube_id = $matches[1];
    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $youtube_id = $matches[1];
    }
    return $youtube_id ? 'https://www.youtube.com/embed/' . $youtube_id : $url;
}

// Geração de token CSRF para os formulários da página
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: 404.php");
    exit();
}

try {
    // Busca todos os dados necessários em um único bloco try...catch
    $stmt = $pdo->prepare(
        "SELECT id, titulo, descricao, imagem, data_postagem, tipo, link, video, topicos FROM cursos WHERE id = ?"
    );
    $stmt->execute([$id]);
    $curso = $stmt->fetch();

    if (!$curso) {
        header("Location: 404.php");
        exit();
    }

    $progresso_status = null;
    if (isset($_SESSION['user_id'])) {
        $progresso_stmt = $pdo->prepare("SELECT status FROM user_progresso WHERE user_id = ? AND curso_id = ?");
        $progresso_stmt->execute([$_SESSION['user_id'], $id]);
        if ($resultado = $progresso_stmt->fetch()) {
            $progresso_status = $resultado['status'];
        }
    }

    $comentarios_stmt = $pdo->prepare(
        "SELECT c.*, u.username FROM comentarios c JOIN users u ON c.user_id = u.id WHERE c.conteudo_id = ? AND c.tipo_conteudo = ? ORDER BY c.data_publicacao DESC"
    );
    $comentarios_stmt->execute([$id, 'curso']);
    $comentarios = $comentarios_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca as categorias para o menu
    $categorias_menu = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro na página do curso (ID: $id): " . $e->getMessage());
    die("Ocorreu um erro ao carregar a página do curso.");
}

// Tratamento de campos opcionais e preparação de variáveis
$curso['descricao'] = $curso['descricao'] ?? 'Descrição não disponível.';
$curso['topicos'] = $curso['topicos'] ?? '';
$curso['video'] = $curso['video'] ?? '';
$curso['link'] = $curso['link'] ?? '#';
$curso['imagem'] = $curso['imagem'] ?: 'fotos/padrao.png';
$embedUrl = !empty($curso['video']) ? getYouTubeEmbedUrl($curso['video']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($curso['titulo']); ?> | HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .titulo-section { color: #fff; text-shadow: 0 0 5px #f43f5e, 0 0 10px #f43f5e; }
        .fade-in { opacity: 0; transform: translateY(20px); transition: all 0.6s ease-out; }
        .fade-in.show { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white font-sans">

<div class="flex flex-col md:flex-row min-h-screen">
    <aside id="menu" x-data="{ openCategories: false }" class="fixed top-0 left-0 z-50 bg-black/90 backdrop-blur-md text-white w-full max-w-xs h-full p-6 space-y-6 transform -translate-x-full transition-transform duration-300 md:translate-x-0 md:relative md:block md:w-64">
        <div class="flex justify-between items-center mb-6">
            <span class="text-xl font-bold text-white">HELMER ACADEMY</span>
            <button aria-label="Fechar menu" class="md:hidden text-2xl" onclick="fecharMenu()">&times;</button>
        </div>
        <div class="text-sm text-gray-400">Bem-vindo, <?php echo htmlspecialchars($_SESSION['user']); ?></div>
        <nav class="flex flex-col space-y-1 pt-4 border-t border-gray-800">
            <a href="index.php" class="menu-link px-4 py-2 rounded-lg hover:bg-rose-600 transition-colors">Início</a>
            <div>
                <button @click="openCategories = !openCategories" class="menu-link w-full flex justify-between items-center px-4 py-2 rounded-lg hover:bg-rose-600 transition-colors">
                    <span>Cursos</span>
                    <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': openCategories}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openCategories" x-transition class="pl-4 mt-1 space-y-1 border-l-2 border-gray-700">
                    <a href="index.php" class="block text-sm px-4 py-2 rounded-lg text-gray-300 hover:bg-gray-700/50 hover:text-white transition-colors">Todos os Cursos</a>
                    <?php foreach ($categorias_menu as $cat): ?>
                        <a href="index.php?categoria_id=<?php echo $cat['id']; ?>" class="block text-sm px-4 py-2 rounded-lg text-gray-300 hover:bg-gray-700/50 hover:text-white transition-colors">
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="index.php#produtos" class="menu-link px-4 py-2 rounded-lg hover:bg-rose-600 transition-colors">Produtos</a>
        </nav>
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="block w-full bg-rose-600 text-white px-4 py-2 rounded-lg hover:bg-rose-700 text-center font-semibold transition-colors">Sair</a>
        </div>
    </aside>

    <main class="flex-1 md:ml-64 p-4 sm:p-6 lg:p-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
            <div class="lg:col-span-2 space-y-10">
                <?php if ($embedUrl): ?>
                <div class="fade-in">
                    <div class="aspect-video w-full rounded-2xl shadow-lg shadow-rose-500/20 overflow-hidden">
                        <iframe class="w-full h-full" src="<?php echo htmlspecialchars($embedUrl); ?>" title="Player de vídeo: <?php echo htmlspecialchars($curso['titulo']); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    </div>
                </div>
                <?php endif; ?>

                <section class="fade-in">
                    <h2 class="text-2xl font-bold mb-4">Sobre o Curso</h2>
                    <p class="text-gray-300 text-base leading-relaxed"><?php echo nl2br(htmlspecialchars($curso['descricao'])); ?></p>
                </section>

                <?php if (!empty($curso['topicos'])): ?>
                <section class="fade-in">
                    <h2 class="text-2xl font-bold mb-4">O que você vai aprender:</h2>
                    <ul class="space-y-3">
                        <?php
                            $topicos = explode("\n", $curso['topicos']);
                            foreach ($topicos as $t) {
                                if (trim($t)) {
                                    echo '<li class="flex items-center gap-3 text-gray-200"><svg class="w-5 h-5 text-rose-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg><span>' . htmlspecialchars(trim($t)) . '</span></li>';
                                }
                            }
                        ?>
                    </ul>
                </section>
                <?php endif; ?>

                <section id="comentarios" class="fade-in pt-8 border-t border-gray-800">
                    <h2 class="text-2xl font-bold mb-6">Comentários (<?= count($comentarios) ?>)</h2>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="bg-gray-800 p-6 rounded-2xl mb-8">
                        <form action="adicionar_comentario.php" method="POST" class="space-y-4">
                            <input type="hidden" name="conteudo_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="tipo_conteudo" value="curso">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                            <textarea name="comentario" rows="4" placeholder="Deixe seu comentário ou dúvida..." required class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
                            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-6 rounded-lg transition">Enviar Comentário</button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <div class="space-y-6">
                        <?php foreach ($comentarios as $comentario): ?>
                        <div id="comentario-<?= $comentario['id'] ?>" class="flex gap-4">
                            <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center font-bold text-rose-400 flex-shrink-0"><?= strtoupper(substr($comentario['username'], 0, 1)) ?></div>
                            <div>
                                <p class="font-bold text-white"><?= htmlspecialchars($comentario['username']) ?></p>
                                <p class="text-gray-300 mt-1"><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-gray-800/80 backdrop-blur-sm p-6 rounded-2xl sticky top-8">
                    <img src="/<?php echo htmlspecialchars($curso['imagem']); ?>" alt="Banner do curso" class="w-full rounded-xl shadow-lg mb-6">
                    <h1 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
                    <p class="text-sm text-gray-400 mb-6">Postado em: <?php echo date("d/m/Y", strtotime($curso['data_postagem'])); ?></p>
                    <div class="space-y-4">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($progresso_status === 'concluido'): ?>
                                <div class="w-full flex items-center justify-center gap-2 px-4 py-3 text-base font-bold text-emerald-400 bg-emerald-500/10 rounded-lg"><span>Curso Concluído ✓</span></div>
                            <?php else: ?>
                                <form action="marcar_concluido.php" method="POST">
                                    <input type="hidden" name="curso_id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <button type="submit" class="w-full text-center bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-4 text-base rounded-lg transition">Marcar como Concluído</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($curso['link']) && $curso['link'] !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($curso['link']); ?>" target="_blank" class="block w-full text-center bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 px-4 text-base rounded-lg transition-transform transform hover:scale-105">Acessar Material</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function abrirMenu() { /* ... */ }
    function fecharMenu() { /* ... */ }

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('show');
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
</script>
</body>
</html>