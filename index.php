<?php
// ===================================================================
// BLOCO PHP COMPLETO COM TODAS AS BUSCAS + CACHE
// ===================================================================
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require 'config.php'; // Conexão PDO
require 'cache.php'; // Sistema de Cache

try {
    // --- Lógica de filtro por categoria ---
    $categoria_id = intval($_GET['categoria_id'] ?? 0);
    
    // Cache para cursos (5 minutos)
    $cache_key_cursos = "cursos_categoria_{$categoria_id}";
    $cursos = $cache->remember($cache_key_cursos, function() use ($pdo, $categoria_id) {
    $params_cursos = [];
    $sql_cursos = "SELECT id, titulo, imagem, tipo, data_postagem, descricao 
                   FROM cursos 
                   WHERE ativo = 1 AND (data_publicacao IS NULL OR data_publicacao <= NOW())";

    if ($categoria_id > 0) {
        $sql_cursos .= " AND categoria_id = ?";
        $params_cursos[] = $categoria_id;
    }
    $sql_cursos .= " ORDER BY data_postagem DESC";
    $stmt_cursos = $pdo->prepare($sql_cursos);
    $stmt_cursos->execute($params_cursos);
        return $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);
    }, 300);

    // Cache para produtos (10 minutos)
    $produtos = $cache->remember('produtos_ativos', function() use ($pdo) {
        return $pdo->query(
        "SELECT id, nome, imagem, preco, descricao 
         FROM produtos 
         WHERE ativo = 1 AND (data_publicacao IS NULL OR data_publicacao <= NOW()) 
         ORDER BY id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    }, 600);

    // Cache para categorias (30 minutos)
    $categorias_menu = $cache->remember('categorias_menu', function() use ($pdo) {
        return $pdo->query("SELECT id, nome, imagem_url FROM categorias ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    }, 1800);
    
    // Cache para banners (15 minutos)
    $banners = $cache->remember('banners_ativos', function() use ($pdo) {
    $stmt_banners = $pdo->query("SELECT imagem_url, link_destino FROM banners WHERE ativo = 1 ORDER BY id DESC");
        return $stmt_banners->fetchAll(PDO::FETCH_ASSOC);
    }, 900);

    // --- Lógica de Notificações (sem cache - dados pessoais) ---
    $notificacoes = [];
    $totalNaoLidas = 0;
    if (isset($_SESSION['user_id'])) {
        $stmt_notif = $pdo->prepare("SELECT * FROM notificacoes WHERE user_id = ? ORDER BY data_criacao DESC LIMIT 5");
        $stmt_notif->execute([$_SESSION['user_id']]);
        $notificacoes = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);

        $stmt_count = $pdo->prepare("SELECT COUNT(id) FROM notificacoes WHERE user_id = ? AND lida = 0");
        $stmt_count->execute([$_SESSION['user_id']]);
        $totalNaoLidas = $stmt_count->fetchColumn();
    }

} catch (Exception $e) {
    $cursos = []; $produtos = []; $categorias_menu = []; $banners = []; $notificacoes = []; $totalNaoLidas = 0;
    error_log("Erro de banco de dados na index.php: " . $e->getMessage());
}
// ===================================================================
// FIM DO BLOCO PHP
// ===================================================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>HELMER ACADEMY</title>
<meta name="description" content="Plataforma de cursos e produtos digitais com foco em educação online">
<meta name="keywords" content="cursos online, educação, treinamentos, produtos digitais">
<meta name="author" content="Helmer Academy">

<!-- PWA Meta Tags -->
<meta name="theme-color" content="#e11d48">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Helmer Academy">
<meta name="msapplication-TileColor" content="#e11d48">
<meta name="msapplication-config" content="/browserconfig.xml">

<!-- Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- Icons -->
<link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/icons/icon-16x16.png">
<link rel="apple-touch-icon" href="/icons/icon-192x192.png">

<!-- Stylesheets -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
<link rel="stylesheet" href="css/responsive.css" />
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .fade-in { opacity: 0; transform: translateY(20px); transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
    .fade-in.show { opacity: 1; transform: translateY(0); }
    .swiper-button-next, .swiper-button-prev { color: #fff; background-color: rgba(0, 0, 0, 0.3); border-radius: 50%; width: 44px; height: 44px; transition: all 0.3s ease; }
    .swiper:hover .swiper-button-next, .swiper:hover .swiper-button-prev { opacity: 1; }
    .swiper-button-next:hover, .swiper-button-prev:hover { background-color: rgba(225, 29, 72, 0.8); }
    .swiper-button-next::after, .swiper-button-prev::after { font-size: 18px; font-weight: bold; }
    .swiper-pagination-bullet-active { background-color: #e11d48; }
    .swiper-slide { height: auto; }
    .swiper-slide-hidden { display: none; }
</style>
</head>
<body class="bg-black text-gray-300 font-sans">

<div class="flex flex-col md:flex-row min-h-screen">

    <aside id="menu" x-data="{ openCategories: false }" class="fixed top-0 left-0 z-50 bg-black/80 backdrop-blur-lg border-r border-gray-800 text-white w-full max-w-xs h-full p-6 space-y-6 transform -translate-x-full md:translate-x-0 md:relative md:block md:w-64 flex-shrink-0 safe-area-left">
        <div class="flex justify-between items-center mb-6">
            <span class="text-xl font-bold text-white tracking-widest">HELMER ACADEMY</span>
            <button aria-label="Fechar menu" class="md:hidden text-2xl" onclick="fecharMenu()">&times;</button>
        </div>
        <div class="text-sm text-gray-400">Bem-vindo, <?php echo htmlspecialchars($_SESSION['user']); ?></div>
        <nav class="flex flex-col space-y-1 pt-4 border-t border-gray-800">
            <a href="index.php" class="menu-link px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">Início</a>
            <div>
                <button @click="openCategories = !openCategories" class="menu-link w-full flex justify-between items-center px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">
                    <span>Cursos</span>
                    <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': openCategories}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openCategories" x-transition class="pl-4 mt-1 space-y-1 border-l-2 border-gray-700">
                    <a href="index.php" class="block text-sm px-4 py-2 rounded-lg text-gray-400 hover:bg-gray-700/50 hover:text-white transition-colors">Todos os Cursos</a>
                    <?php foreach ($categorias_menu as $cat): ?>
                        <a href="index.php?categoria_id=<?php echo $cat['id']; ?>" class="block text-sm px-4 py-2 rounded-lg text-gray-400 hover:bg-gray-700/50 hover:text-white transition-colors">
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="#produtos" class="menu-link px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">Produtos</a>
            <a href="chat.php" class="menu-link px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">Chat Ao Vivo</a>
        </nav>
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="block w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-center font-semibold transition-colors">Sair</a>
        </div>
    </aside>

    <div class="flex-1 h-screen overflow-y-auto">
        <header class="p-4 sm:p-6 lg:p-8 flex justify-between items-center sticky top-0 bg-black/50 backdrop-blur-lg z-40 border-b border-gray-800 safe-area-top">
            <div class="flex items-center gap-4">
                <button aria-label="Abrir menu" onclick="abrirMenu()" class="p-2 bg-red-600 rounded-lg md:hidden">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h1 class="text-lg font-bold text-white hidden md:block">HELMER ACADEMY</h1>
            </div>
            
            <div x-data="{ open: false, unreadCount: <?php echo $totalNaoLidas; ?> }" class="relative">
                <button @click="open = !open; if(unreadCount > 0) marcarNotificacoesComoLidas()" class="relative text-gray-400 hover:text-white transition">
                    <i class="fas fa-bell fa-lg"></i>
                    <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center animate-pulse"></span>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-80 bg-gray-800 border border-gray-700 rounded-lg shadow-lg z-50" style="display: none;">
                    <div class="p-4 font-bold border-b border-gray-700 text-white">Notificações</div>
                    <div class="max-h-96 overflow-y-auto">
                        <?php if (empty($notificacoes)): ?>
                            <p class="text-gray-400 text-center p-4">Nenhuma notificação.</p>
                        <?php else: ?>
                            <?php foreach ($notificacoes as $notif): ?>
                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="block px-4 py-3 hover:bg-gray-700/50 transition <?php echo !$notif['lida'] ? 'bg-sky-500/10' : ''; ?>">
                                <p class="text-sm text-gray-200"><?php echo htmlspecialchars($notif['mensagem']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('d/m/Y H:i', strtotime($notif['data_criacao'])); ?></p>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8 pt-6 space-y-16">
            
            <section class="fade-in">
                <?php if (!empty($banners)): ?>
                <div class="swiper banner-swiper relative w-full max-w-lg mx-auto rounded-2xl shadow-xl">
                    <div class="swiper-wrapper">
                        <?php foreach($banners as $banner): ?>
                        <div class="swiper-slide">
                            <a href="<?php echo htmlspecialchars($banner['link_destino']); ?>" aria-label="Saiba mais" class="block w-full aspect-[4/5] bg-cover bg-center bg-no-repeat" style="background-image: url('/<?php echo htmlspecialchars($banner['imagem_url']); ?>');"></a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
                <?php endif; ?>
            </section>

            <section class="fade-in">
                <div class="flex gap-4 overflow-x-auto pb-4 -mx-4 px-4">
                    <?php foreach ($categorias_menu as $categoria): ?>
                        <a href="index.php?categoria_id=<?php echo $categoria['id']; ?>" class="flex flex-col items-center gap-2 flex-shrink-0 group">
                            <div class="w-20 h-20 rounded-full ring-2 ring-gray-700 group-hover:ring-red-500 transition-all duration-300 p-1">
                                <img src="/<?php echo htmlspecialchars($categoria['imagem_url'] ?: 'fotos/padrao.png'); ?>" alt="<?php echo htmlspecialchars($categoria['nome']); ?>" class="w-full h-full object-cover rounded-full transform transition-transform duration-300 group-hover:scale-110">
                            </div>
                            <span class="text-sm font-semibold text-gray-300 group-hover:text-white transition-colors"><?php echo htmlspecialchars($categoria['nome']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="flex flex-col md:flex-row gap-4 fade-in">
                <div class="flex-1 relative">
                    <input type="text" id="search" placeholder="Buscar por nome..." class="w-full p-3 pr-12 rounded-lg bg-gray-900 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition mobile-input" autocomplete="off">
                    <button onclick="performSearch()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition mobile-touch-target">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <select id="filter" class="p-3 rounded-lg bg-gray-900 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition appearance-none mobile-input" style="background-image: url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e&quot;); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                    <option value="all">Todos os Tipos</option>
                    <option value="gratuitos">Gratuitos</option>
                    <option value="premium">Premium</option>
                </select>
                <a href="busca_avancada.php" class="px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition flex items-center mobile-touch-target">
                    <i class="fas fa-filter mr-2"></i>Filtros
                </a>
                <a href="favoritos.php" class="px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center mobile-touch-target">
                    <i class="fas fa-heart mr-2"></i>Favoritos
                </a>
            </div>

            <section id="cursos" class="fade-in">
                <h2 class="text-3xl font-bold text-white mb-8">🚀 Cursos e Treinamentos</h2>
                <div class="swiper cursos-swiper relative">
                    <div class="swiper-wrapper pb-12">
                        <?php if(count($cursos) > 0): ?>
                            <?php foreach($cursos as $curso): ?>
                            <div class="swiper-slide h-auto mobile-card" data-tipo="<?php echo htmlspecialchars($curso['tipo'] ?? 'premium'); ?>">
                                <div class="bg-gray-900/50 backdrop-blur-sm ring-1 ring-white/10 rounded-2xl overflow-hidden group h-full flex flex-col transform transition-all duration-300 hover:shadow-2xl hover:shadow-red-500/20 hover:-translate-y-2 card-hover">
                                    <div class="relative">
                                        <a href="curso_pagina.php?id=<?php echo $curso['id']; ?>" class="block">
                                            <img src="/<?php echo htmlspecialchars($curso['imagem']); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" class="w-full h-44 object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                        </a>
                                        <div class="absolute bottom-4 left-4">
                                            <span class="text-xs font-bold uppercase tracking-wider text-white bg-red-600 px-2.5 py-1 rounded-full"><?php echo htmlspecialchars($curso['tipo']); ?></span>
                                        </div>
                                        <button onclick="toggleFavorite(<?php echo $curso['id']; ?>, 'curso')" 
                                                class="absolute top-4 right-4 p-2 bg-black/50 rounded-full text-gray-300 hover:text-red-500 hover:bg-black/70 transition-all duration-300 favorite-btn mobile-touch-target"
                                                title="Adicionar aos favoritos">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                    <div class="p-5 flex flex-col flex-grow">
                                        <h3 class="text-lg font-bold text-white mb-2 flex-grow"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                                        <p class="text-sm text-gray-400 line-clamp-3 mb-4"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                        <a href="curso_pagina.php?id=<?php echo $curso['id']; ?>" class="block w-full mt-auto text-center bg-gray-700/50 text-white font-semibold py-2.5 rounded-lg border border-gray-600 hover:bg-red-600 hover:border-red-600 transition-colors duration-300">Acessar Curso</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="swiper-slide h-auto"><div class="flex items-center justify-center h-full p-8 text-center text-gray-400">Nenhum curso encontrado.</div></div>
                        <?php endif; ?>
                    </div>
                    <div class="swiper-pagination"></div><div class="swiper-button-next"></div><div class="swiper-button-prev"></div>
                </div>
            </section>

            <section id="produtos" class="fade-in">
                <h2 class="text-3xl font-bold text-white mb-8">💰 Produtos Exclusivos</h2>
                <div class="swiper produtos-swiper relative">
                    <div class="swiper-wrapper pb-12">
                        <?php if(count($produtos) > 0): ?>
                            <?php foreach($produtos as $p): ?>
                            <div class="swiper-slide h-auto" data-tipo="premium">
                                <div class="bg-gray-900/50 backdrop-blur-sm ring-1 ring-white/10 rounded-2xl overflow-hidden group h-full flex flex-col transform transition-all duration-300 hover:shadow-2xl hover:shadow-red-500/20 hover:-translate-y-2">
                                   <div class="relative">
                                       <a href="produtos_pagina.php?id=<?php echo $p['id']; ?>" class="block">
                                           <img src="/<?php echo htmlspecialchars($p['imagem']); ?>" alt="<?php echo htmlspecialchars($p['nome']); ?>" class="w-full h-44 object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                                           <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                       </a>
                                       <button onclick="toggleFavorite(<?php echo $p['id']; ?>, 'produto')" 
                                               class="absolute top-4 right-4 p-2 bg-black/50 rounded-full text-gray-300 hover:text-red-500 hover:bg-black/70 transition-all duration-300 favorite-btn"
                                               title="Adicionar aos favoritos">
                                           <i class="fas fa-heart"></i>
                                       </button>
                                   </div>
                                    <div class="p-5 flex flex-col flex-grow">
                                        <h3 class="text-lg font-bold text-white mb-2 flex-grow"><?php echo htmlspecialchars($p['nome']); ?></h3>
                                        <p class="text-3xl font-bold text-red-500 mb-4">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></p>
                                        <a href="produtos_pagina.php?id=<?php echo $p['id']; ?>" class="block w-full mt-auto text-center bg-red-600 text-white font-bold py-2.5 rounded-lg hover:bg-red-700 transition-colors duration-300">Ver Produto</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <div class="swiper-slide h-auto"><div class="flex items-center justify-center h-full p-8 text-center text-gray-400">Nenhum produto disponível.</div></div>
                        <?php endif; ?>
                    </div>
                    <div class="swiper-pagination"></div><div class="swiper-button-next"></div><div class="swiper-button-prev"></div>
                </div>
            </section>
        </main>
        <footer class="text-center py-6 text-gray-600 text-sm mt-8">© <?php echo date('Y'); ?> Helmer Academy | Todos os direitos reservados.</footer>
    </div>
</div>

<div class="fixed bottom-5 right-5 z-50 flex flex-col gap-3">
    <a href="chat.php" target="_blank"
       class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-full shadow-lg transition transform hover:scale-110"
       aria-label="Acessar Chat ao Vivo" title="Chat ao Vivo">
        <i class="fas fa-comments"></i>
    </a>
    <a href="https://wa.me/5551996148568" target="_blank" rel="noopener noreferrer" 
       class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-full shadow-lg transition transform hover:scale-110" 
       aria-label="Fale no WhatsApp" title="Fale no WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
</div>

<script>
    const menu = document.getElementById('menu');
    function abrirMenu() { menu.classList.remove('-translate-x-full'); }
    function fecharMenu() { menu.classList.add('-translate-x-full'); }
    document.querySelectorAll('.menu-link').forEach(element => {
        element.addEventListener('click', () => {
            if (element.tagName === 'A' && window.innerWidth < 768) {
                fecharMenu();
            }
        });
    });

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => { if(entry.isIntersecting) entry.target.classList.add('show'); });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
    
    function marcarNotificacoesComoLidas() {
        let alpineData = document.querySelector('div[x-data]').__x.$data;
        if (alpineData.unreadCount > 0) {
            fetch('marcar_notificacoes_lidas.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => { if (data.status === 'success') { alpineData.unreadCount = 0; } })
            .catch(error => console.error('Erro:', error));
        }
    }
    
    document.addEventListener('DOMContentLoaded', function () {
        const createSwiper = (selector) => {
            return new Swiper(selector, {
                loop: false,
                slidesPerView: 1,
                spaceBetween: 20,
                pagination: { el: selector + ' .swiper-pagination', clickable: true, },
                navigation: { nextEl: selector + ' .swiper-button-next', prevEl: selector + ' .swiper-button-prev', },
                breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 }, 1280: { slidesPerView: 4 } }
            });
        };
        
        const cursosSwiper = createSwiper('.cursos-swiper');
        const produtosSwiper = createSwiper('.produtos-swiper');
        const bannerSwiper = new Swiper('.banner-swiper', {
            loop: true,
            autoplay: { delay: 5000, disableOnInteraction: false, },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev', },
        });

        const applyFilters = () => {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const filterValue = document.getElementById('filter').value;
            const swipersToFilter = [cursosSwiper, produtosSwiper];

            swipersToFilter.forEach(swiper => {
                if (!swiper || !swiper.slides) return;
                swiper.slides.forEach(slide => {
                    const cardText = slide.textContent.toLowerCase();
                    const cardType = slide.dataset.tipo || 'all';
                    const matchesSearch = searchTerm === '' || cardText.includes(searchTerm);
                    const matchesFilter = filterValue === 'all' || cardType === filterValue;

                    if (matchesSearch && matchesFilter) {
                        slide.classList.remove('swiper-slide-hidden');
                    } else {
                        slide.classList.add('swiper-slide-hidden');
                    }
                });
                swiper.update(); 
            });
        };

        document.getElementById('search').addEventListener('input', applyFilters);
        document.getElementById('filter').addEventListener('change', applyFilters);
        
        // Busca avançada
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    });
    
    // Função para busca avançada
    function performSearch() {
        const searchTerm = document.getElementById('search').value.trim();
        if (searchTerm) {
            window.location.href = `busca_avancada.php?search=${encodeURIComponent(searchTerm)}`;
        }
    }
    
    // Função para toggle favorito
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
            
            // Atualiza visual do botão
            const button = event.target.closest('button');
            const icon = button.querySelector('i');
            
            if (result.status === 'added') {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.add('favorited');
                button.style.color = '#ef4444';
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('favorited');
                button.style.color = '#9ca3af';
            }
            
            // Mostra notificação
            showNotification(result.message, result.status === 'added' ? 'success' : 'info');
            
        } catch (error) {
            console.error('Erro ao alterar favorito:', error);
            showNotification('Erro ao alterar favorito', 'error');
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
    
    // Registrar Service Worker para PWA
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker registrado com sucesso:', registration.scope);
                })
                .catch(error => {
                    console.log('Falha ao registrar Service Worker:', error);
                });
        });
    }
    
    // Instalar PWA
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Mostrar botão de instalação
        showInstallButton();
    });
    
    function showInstallButton() {
        const installButton = document.createElement('button');
        installButton.innerHTML = '<i class="fas fa-download mr-2"></i>Instalar App';
        installButton.className = 'fixed bottom-20 right-5 bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition transform hover:scale-110 z-50';
        
        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`Instalação ${outcome}`);
                deferredPrompt = null;
                installButton.remove();
            }
        });
        
        document.body.appendChild(installButton);
        
        // Remove o botão após 10 segundos
        setTimeout(() => {
            if (installButton.parentNode) {
                installButton.remove();
            }
        }, 10000);
    }
</script>
</body>
</html>