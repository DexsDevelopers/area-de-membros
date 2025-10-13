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
<title>HELMER ACADEMY - Plataforma Premium de Cursos</title>
<meta name="description" content="Transforme sua carreira com nossos cursos premium. Acesso exclusivo a treinamentos de alta qualidade com certificação profissional.">
<meta name="keywords" content="cursos online, educação premium, treinamentos profissionais, certificação, carreira">
<meta name="author" content="Helmer Academy">

<!-- PWA Meta Tags -->
<meta name="theme-color" content="#dc2626">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Helmer Academy">
<meta name="msapplication-TileColor" content="#dc2626">

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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .gradient-bg {
        background: linear-gradient(135deg, #000000 0%, #1a0000 25%, #2d0000 50%, #1a0000 75%, #000000 100%);
        background-size: 400% 400%;
        animation: gradientShift 8s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .hero-gradient {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 25%, #f97316 50%, #dc2626 75%, #ef4444 100%);
        background-size: 200% 200%;
        animation: heroPulse 3s ease infinite;
    }
    
    @keyframes heroPulse {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .card-premium {
        background: linear-gradient(145deg, rgba(220,38,38,0.1) 0%, rgba(0,0,0,0.3) 50%, rgba(220,38,38,0.05) 100%);
        backdrop-filter: blur(25px);
        border: 1px solid rgba(220,38,38,0.3);
        box-shadow: 0 8px 32px rgba(220,38,38,0.2), 0 0 0 1px rgba(255,255,255,0.1);
        position: relative;
        overflow: hidden;
    }
    
    .card-premium::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s;
    }
    
    .card-premium:hover::before {
        left: 100%;
    }
    
    .card-hover {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-hover:hover {
        transform: translateY(-12px) scale(1.05);
        box-shadow: 0 25px 50px rgba(220, 38, 38, 0.4), 0 0 30px rgba(220, 38, 38, 0.3);
        border-color: rgba(220, 38, 38, 0.6);
    }
    
    .dopamine-glow {
        animation: dopamineGlow 2s ease-in-out infinite alternate;
    }
    
    @keyframes dopamineGlow {
        0% { 
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.3), 0 0 40px rgba(220, 38, 38, 0.1);
        }
        100% { 
            box-shadow: 0 0 40px rgba(220, 38, 38, 0.6), 0 0 80px rgba(220, 38, 38, 0.3);
        }
    }
    
    .neon-border {
        border: 2px solid transparent;
        background: linear-gradient(45deg, #000, #000) padding-box,
                    linear-gradient(45deg, #dc2626, #ef4444, #dc2626) border-box;
        animation: neonPulse 3s ease-in-out infinite;
    }
    
    @keyframes neonPulse {
        0%, 100% { 
            border-image: linear-gradient(45deg, #dc2626, #ef4444, #dc2626) 1;
        }
        50% { 
            border-image: linear-gradient(45deg, #ef4444, #dc2626, #ef4444) 1;
        }
    }
    
    .fade-in {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .fade-in.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .swiper-button-next, .swiper-button-prev {
        color: #fff;
        background: linear-gradient(135deg, #dc2626, #ef4444);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
    }
    
    .swiper-button-next:hover, .swiper-button-prev:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6);
    }
    
    .swiper-button-next::after, .swiper-button-prev::after {
        font-size: 20px;
        font-weight: bold;
    }
    
    .swiper-pagination-bullet {
        background: rgba(255,255,255,0.3);
        opacity: 1;
    }
    
    .swiper-pagination-bullet-active {
        background: #dc2626;
        transform: scale(1.2);
    }
    
    .trust-badge {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1));
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    
    .stats-card {
        background: linear-gradient(145deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .testimonial-card {
        background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .floating-animation {
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    .pulse-glow {
        animation: pulse-glow 2s ease-in-out infinite alternate;
    }
    
    @keyframes pulse-glow {
        from { box-shadow: 0 0 20px rgba(220, 38, 38, 0.4); }
        to { box-shadow: 0 0 40px rgba(220, 38, 38, 0.8); }
    }
    
    .text-gradient {
        background: linear-gradient(135deg, #dc2626, #ef4444, #f97316);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .btn-premium {
        background: linear-gradient(135deg, #dc2626, #ef4444, #f97316);
        background-size: 200% 200%;
        box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        transition: all 0.3s ease;
        animation: buttonPulse 2s ease-in-out infinite;
        position: relative;
        overflow: hidden;
    }
    
    .btn-premium::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }
    
    .btn-premium:hover::before {
        left: 100%;
    }
    
    .btn-premium:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 30px rgba(220, 38, 38, 0.8);
        animation: buttonGlow 0.5s ease-in-out;
    }
    
    @keyframes buttonPulse {
        0%, 100% { 
            background-position: 0% 50%;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }
        50% { 
            background-position: 100% 50%;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6);
        }
    }
    
    @keyframes buttonGlow {
        0% { box-shadow: 0 12px 30px rgba(220, 38, 38, 0.8); }
        50% { box-shadow: 0 15px 40px rgba(220, 38, 38, 1); }
        100% { box-shadow: 0 12px 30px rgba(220, 38, 38, 0.8); }
    }
    
    .mobile-optimized {
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }
    
    .loading-skeleton {
        background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    .particle {
        position: absolute;
        background: radial-gradient(circle, rgba(220,38,38,0.8) 0%, rgba(220,38,38,0.2) 50%, transparent 100%);
        border-radius: 50%;
        pointer-events: none;
        animation: particleFloat 6s ease-in-out infinite;
    }
    
    @keyframes particleFloat {
        0%, 100% { 
            transform: translateY(0px) scale(1);
            opacity: 0.7;
        }
        50% { 
            transform: translateY(-20px) scale(1.2);
            opacity: 1;
        }
    }
    
    .dopamine-text {
        background: linear-gradient(45deg, #dc2626, #ef4444, #f97316, #dc2626);
        background-size: 400% 400%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: textShimmer 3s ease-in-out infinite;
    }
    
    @keyframes textShimmer {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .glow-effect {
        filter: drop-shadow(0 0 10px rgba(220, 38, 38, 0.5));
        animation: glowPulse 2s ease-in-out infinite alternate;
    }
    
    @keyframes glowPulse {
        0% { filter: drop-shadow(0 0 10px rgba(220, 38, 38, 0.5)); }
        100% { filter: drop-shadow(0 0 20px rgba(220, 38, 38, 0.8)); }
    }
</style>
</head>
<body class="gradient-bg text-white min-h-screen relative overflow-hidden">

<!-- Partículas de fundo -->
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="particle w-3 h-3 top-1/4 left-1/4" style="animation-delay: 0s;"></div>
    <div class="particle w-4 h-4 top-1/3 right-1/4" style="animation-delay: 1s;"></div>
    <div class="particle w-2 h-2 bottom-1/4 left-1/3" style="animation-delay: 2s;"></div>
    <div class="particle w-3 h-3 bottom-1/3 right-1/3" style="animation-delay: 3s;"></div>
    <div class="particle w-2 h-2 top-1/2 left-1/2" style="animation-delay: 4s;"></div>
    <div class="particle w-4 h-4 top-3/4 right-1/3" style="animation-delay: 5s;"></div>
</div>

<div class="flex flex-col lg:flex-row min-h-screen relative z-10">

    <!-- Sidebar Moderna -->
    <aside id="menu" x-data="{ openCategories: false }" class="fixed top-0 left-0 z-50 gradient-bg backdrop-blur-xl border-r border-white/10 text-white w-full max-w-xs h-full p-6 space-y-6 transform -translate-x-full lg:translate-x-0 lg:relative lg:block lg:w-80 flex-shrink-0">
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 hero-gradient rounded-xl flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-white text-xl"></i>
                </div>
                <span class="text-xl font-bold text-white tracking-wide">HELMER ACADEMY</span>
            </div>
            <button aria-label="Fechar menu" class="lg:hidden text-2xl hover:text-red-400 transition-colors" onclick="fecharMenu()">&times;</button>
        </div>
        
        <div class="bg-white/5 rounded-xl p-4 mb-6">
            <div class="text-sm text-gray-300 mb-1">Bem-vindo de volta,</div>
            <div class="text-lg font-semibold text-white"><?php echo htmlspecialchars($_SESSION['user']); ?></div>
        </div>
        
        <nav class="flex flex-col space-y-2">
            <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-white/10 transition-all duration-300 group">
                <i class="fas fa-home w-5 text-gray-400 group-hover:text-red-400"></i>
                <span class="font-medium">Início</span>
            </a>
            
            <div>
                <button @click="openCategories = !openCategories" class="w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-white/10 transition-all duration-300 group">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-book w-5 text-gray-400 group-hover:text-red-400"></i>
                        <span class="font-medium">Cursos</span>
                    </div>
                    <i class="fas fa-chevron-down transition-transform duration-300" :class="{'rotate-180': openCategories}"></i>
                </button>
                <div x-show="openCategories" x-transition class="pl-8 mt-2 space-y-1">
                    <a href="index.php" class="block text-sm px-4 py-2 rounded-lg text-gray-400 hover:bg-white/5 hover:text-white transition-colors">Todos os Cursos</a>
                    <?php foreach ($categorias_menu as $cat): ?>
                        <a href="index.php?categoria_id=<?php echo $cat['id']; ?>" class="block text-sm px-4 py-2 rounded-lg text-gray-400 hover:bg-white/5 hover:text-white transition-colors">
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <a href="#produtos" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-white/10 transition-all duration-300 group">
                <i class="fas fa-shopping-bag w-5 text-gray-400 group-hover:text-red-400"></i>
                <span class="font-medium">Produtos</span>
            </a>
            
            <a href="chat.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-white/10 transition-all duration-300 group">
                <i class="fas fa-comments w-5 text-gray-400 group-hover:text-red-400"></i>
                <span class="font-medium">Chat Ao Vivo</span>
            </a>
        </nav>
        
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="block w-full btn-premium text-white px-4 py-3 rounded-xl text-center font-semibold transition-all duration-300 hover:scale-105">
                <i class="fas fa-sign-out-alt mr-2"></i>Sair
            </a>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <div class="flex-1 min-h-screen overflow-y-auto">
        
        <!-- Header Premium -->
        <header class="p-6 lg:p-8 flex justify-between items-center sticky top-0 gradient-bg backdrop-blur-xl z-40 border-b border-white/10">
            <div class="flex items-center gap-4">
                <button aria-label="Abrir menu" onclick="abrirMenu()" class="p-3 btn-premium rounded-xl lg:hidden">
                    <i class="fas fa-bars text-white"></i>
                </button>
                <h1 class="text-2xl font-bold text-gradient hidden lg:block">HELMER ACADEMY</h1>
            </div>
            
            <!-- Notificações -->
            <div x-data="{ open: false, unreadCount: <?php echo $totalNaoLidas; ?> }" class="relative">
                <button @click="open = !open; if(unreadCount > 0) marcarNotificacoesComoLidas()" class="relative p-3 bg-white/10 rounded-xl hover:bg-white/20 transition-all duration-300">
                    <i class="fas fa-bell text-lg"></i>
                    <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center animate-pulse"></span>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-4 w-80 card-premium rounded-2xl shadow-2xl z-50" style="display: none;">
                    <div class="p-6 font-bold border-b border-white/10 text-white text-lg">Notificações</div>
                    <div class="max-h-96 overflow-y-auto">
                        <?php if (empty($notificacoes)): ?>
                            <p class="text-gray-400 text-center p-6">Nenhuma notificação.</p>
                        <?php else: ?>
                            <?php foreach ($notificacoes as $notif): ?>
                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="block px-6 py-4 hover:bg-white/5 transition <?php echo !$notif['lida'] ? 'bg-blue-500/10 border-l-4 border-blue-500' : ''; ?>">
                                <p class="text-sm text-gray-200"><?php echo htmlspecialchars($notif['mensagem']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('d/m/Y H:i', strtotime($notif['data_criacao'])); ?></p>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-6 lg:p-8 space-y-16">
            
            <!-- Hero Section -->
            <section class="fade-in">
                <div class="text-center mb-12">
                    <h1 class="text-4xl lg:text-6xl font-bold text-white mb-6">
                        Transforme sua 
                        <span class="dopamine-text glow-effect">Carreira</span>
                    </h1>
                    <p class="text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
                        Acesse cursos premium de alta qualidade e acelere seu desenvolvimento profissional com nossa plataforma exclusiva.
                    </p>
                    
                    <!-- Stats de Confiança -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 max-w-4xl mx-auto mb-12">
                        <div class="stats-card rounded-xl p-6 text-center">
                            <div class="text-3xl font-bold text-red-400 mb-2">500+</div>
                            <div class="text-sm text-gray-300">Alunos Ativos</div>
                        </div>
                        <div class="stats-card rounded-xl p-6 text-center">
                            <div class="text-3xl font-bold text-red-400 mb-2">50+</div>
                            <div class="text-sm text-gray-300">Cursos Premium</div>
                        </div>
                        <div class="stats-card rounded-xl p-6 text-center">
                            <div class="text-3xl font-bold text-red-400 mb-2">98%</div>
                            <div class="text-sm text-gray-300">Satisfação</div>
                        </div>
                        <div class="stats-card rounded-xl p-6 text-center">
                            <div class="text-3xl font-bold text-red-400 mb-2">24/7</div>
                            <div class="text-sm text-gray-300">Suporte</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Banners Carousel -->
            <section class="fade-in">
                <?php if (!empty($banners)): ?>
                <div class="swiper banner-swiper relative w-full max-w-5xl mx-auto rounded-3xl shadow-2xl overflow-hidden neon-border">
                    <div class="swiper-wrapper">
                        <?php foreach($banners as $banner): ?>
                        <div class="swiper-slide">
                            <a href="<?php echo htmlspecialchars($banner['link_destino']); ?>" aria-label="Saiba mais" class="block w-full aspect-[16/9] bg-cover bg-center bg-no-repeat relative group" style="background-image: url('/<?php echo htmlspecialchars($banner['imagem_url']); ?>');">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>
                                <div class="absolute bottom-8 left-8 right-8">
                                    <h3 class="text-3xl font-bold text-white mb-3 text-gradient">Oferta Especial</h3>
                                    <p class="text-gray-200 text-lg">Aproveite nossa promoção exclusiva!</p>
                                    <div class="mt-4 inline-flex items-center bg-red-600/20 backdrop-blur-sm px-4 py-2 rounded-full border border-red-500/30">
                                        <i class="fas fa-fire text-red-400 mr-2"></i>
                                        <span class="text-red-300 font-semibold">Oferta Limitada</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-button-next banner-next"></div>
                    <div class="swiper-button-prev banner-prev"></div>
                    <div class="swiper-pagination banner-pagination"></div>
                </div>
                <?php endif; ?>
            </section>

            <!-- Categorias -->
            <section class="fade-in">
                <h2 class="text-3xl font-bold text-white mb-8 text-center">Explore por Categoria</h2>
                <div class="flex gap-6 overflow-x-auto pb-6 -mx-6 px-6">
                    <?php foreach ($categorias_menu as $categoria): ?>
                        <a href="index.php?categoria_id=<?php echo $categoria['id']; ?>" class="flex flex-col items-center gap-4 flex-shrink-0 group min-w-[120px]">
                            <div class="w-24 h-24 rounded-2xl ring-2 ring-white/20 group-hover:ring-red-500 transition-all duration-300 p-2 bg-white/5 group-hover:bg-white/10">
                                <img src="/<?php echo htmlspecialchars($categoria['imagem_url'] ?: 'fotos/padrao.png'); ?>" alt="<?php echo htmlspecialchars($categoria['nome']); ?>" class="w-full h-full object-cover rounded-xl transform transition-transform duration-300 group-hover:scale-110">
                            </div>
                            <span class="text-sm font-semibold text-gray-300 group-hover:text-white transition-colors text-center"><?php echo htmlspecialchars($categoria['nome']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Busca e Filtros -->
            <div class="fade-in">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1 relative">
                        <input type="text" id="search" placeholder="Buscar cursos e produtos..." class="w-full p-4 pr-12 rounded-xl bg-white/10 text-white border border-white/20 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 mobile-optimized" autocomplete="off">
                        <button onclick="performSearch()" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                            <i class="fas fa-search text-lg"></i>
                        </button>
                    </div>
                    <select id="filter" class="p-4 rounded-xl bg-white/10 text-white border border-white/20 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 mobile-optimized" style="background-image: url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e&quot;); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem;">
                        <option value="all">Todos os Tipos</option>
                        <option value="gratuitos">Gratuitos</option>
                        <option value="premium">Premium</option>
                    </select>
                    <a href="busca_avancada.php" class="px-6 py-4 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-300 flex items-center justify-center mobile-optimized">
                        <i class="fas fa-filter mr-2"></i>Filtros
                    </a>
                    <a href="favoritos.php" class="px-6 py-4 btn-premium text-white rounded-xl transition-all duration-300 flex items-center justify-center mobile-optimized">
                        <i class="fas fa-heart mr-2"></i>Favoritos
                    </a>
                </div>
            </div>

            <!-- Cursos Premium -->
            <section id="cursos" class="fade-in">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-4xl font-bold text-white">🚀 Cursos Premium</h2>
                    <div class="trust-badge px-4 py-2 rounded-full">
                        <span class="text-sm font-semibold text-green-400">
                            <i class="fas fa-shield-alt mr-2"></i>Certificação Garantida
                        </span>
                    </div>
                </div>
                
                <div class="swiper cursos-swiper relative">
                    <div class="swiper-wrapper pb-12">
                        <?php if(count($cursos) > 0): ?>
                            <?php foreach($cursos as $curso): ?>
                            <div class="swiper-slide h-auto mobile-optimized" data-tipo="<?php echo htmlspecialchars($curso['tipo'] ?? 'premium'); ?>">
                                <div class="card-premium rounded-2xl overflow-hidden group h-full flex flex-col card-hover">
                                    <div class="relative">
                                        <a href="curso_pagina.php?id=<?php echo $curso['id']; ?>" class="block">
                                            <img src="/<?php echo htmlspecialchars($curso['imagem']); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" class="w-full h-48 object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                        </a>
                                        <div class="absolute bottom-4 left-4">
                                            <span class="text-xs font-bold uppercase tracking-wider text-white bg-red-500 px-3 py-1.5 rounded-full"><?php echo htmlspecialchars($curso['tipo']); ?></span>
                                        </div>
                                        <button onclick="toggleFavorite(<?php echo $curso['id']; ?>, 'curso')" 
                                                class="absolute top-4 right-4 p-3 bg-black/50 rounded-full text-gray-300 hover:text-red-500 hover:bg-black/70 transition-all duration-300 mobile-optimized"
                                                title="Adicionar aos favoritos">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                    <div class="p-6 flex flex-col flex-grow">
                                        <h3 class="text-xl font-bold text-white mb-3 flex-grow line-clamp-2"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                                        <p class="text-sm text-gray-400 line-clamp-3 mb-4"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center text-yellow-400">
                                                <i class="fas fa-star text-sm"></i>
                                                <span class="text-sm font-semibold ml-1">4.9</span>
                                                <span class="text-xs text-gray-400 ml-1">(127)</span>
                                            </div>
                                            <div class="text-sm text-green-400 font-semibold">
                                                <i class="fas fa-check-circle mr-1"></i>Disponível
                                            </div>
                                        </div>
                                        <a href="curso_pagina.php?id=<?php echo $curso['id']; ?>" class="block w-full mt-auto text-center btn-premium text-white font-semibold py-3 rounded-xl transition-all duration-300 hover:scale-105">Acessar Curso</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="swiper-slide h-auto">
                                <div class="card-premium rounded-2xl p-12 text-center">
                                    <i class="fas fa-book-open text-6xl text-gray-400 mb-4"></i>
                                    <h3 class="text-xl font-semibold text-white mb-2">Nenhum curso encontrado</h3>
                                    <p class="text-gray-400">Que tal explorar nossas categorias?</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </section>

            <!-- Produtos Exclusivos -->
            <section id="produtos" class="fade-in">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-4xl font-bold text-white">💰 Produtos Exclusivos</h2>
                    <div class="trust-badge px-4 py-2 rounded-full">
                        <span class="text-sm font-semibold text-green-400">
                            <i class="fas fa-lock mr-2"></i>Acesso Exclusivo
                        </span>
                    </div>
                </div>
                
                <div class="swiper produtos-swiper relative">
                    <div class="swiper-wrapper pb-12">
                        <?php if(count($produtos) > 0): ?>
                            <?php foreach($produtos as $p): ?>
                            <div class="swiper-slide h-auto mobile-optimized" data-tipo="premium">
                                <div class="card-premium rounded-2xl overflow-hidden group h-full flex flex-col card-hover">
                                   <div class="relative">
                                       <a href="produtos_pagina.php?id=<?php echo $p['id']; ?>" class="block">
                                           <img src="/<?php echo htmlspecialchars($p['imagem']); ?>" alt="<?php echo htmlspecialchars($p['nome']); ?>" class="w-full h-48 object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                                           <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                       </a>
                                       <button onclick="toggleFavorite(<?php echo $p['id']; ?>, 'produto')" 
                                               class="absolute top-4 right-4 p-3 bg-black/50 rounded-full text-gray-300 hover:text-red-500 hover:bg-black/70 transition-all duration-300 mobile-optimized"
                                               title="Adicionar aos favoritos">
                                           <i class="fas fa-heart"></i>
                                       </button>
                                   </div>
                                    <div class="p-6 flex flex-col flex-grow">
                                        <h3 class="text-xl font-bold text-white mb-3 flex-grow line-clamp-2"><?php echo htmlspecialchars($p['nome']); ?></h3>
                                        <p class="text-sm text-gray-400 line-clamp-2 mb-4"><?php echo htmlspecialchars($p['descricao']); ?></p>
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="text-3xl font-bold text-red-400">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></div>
                                            <div class="text-sm text-green-400 font-semibold">
                                                <i class="fas fa-check-circle mr-1"></i>Disponível
                                            </div>
                                        </div>
                                        <a href="produtos_pagina.php?id=<?php echo $p['id']; ?>" class="block w-full mt-auto text-center btn-premium text-white font-bold py-3 rounded-xl transition-all duration-300 hover:scale-105">Ver Produto</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="swiper-slide h-auto">
                                <div class="card-premium rounded-2xl p-12 text-center">
                                    <i class="fas fa-shopping-bag text-6xl text-gray-400 mb-4"></i>
                                    <h3 class="text-xl font-semibold text-white mb-2">Nenhum produto disponível</h3>
                                    <p class="text-gray-400">Novos produtos em breve!</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </section>

            <!-- Testimonials -->
            <section class="fade-in">
                <h2 class="text-4xl font-bold text-white mb-12 text-center">O que nossos alunos dizem</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="testimonial-card rounded-2xl p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center text-white font-bold">M</div>
                            <div class="ml-4">
                                <h4 class="font-semibold text-white">Maria Silva</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-300 italic">"Os cursos são excepcionais! Consegui uma promoção após completar o curso de marketing digital."</p>
                    </div>
                    
                    <div class="testimonial-card rounded-2xl p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">J</div>
                            <div class="ml-4">
                                <h4 class="font-semibold text-white">João Santos</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-300 italic">"Plataforma incrível! O suporte é 24/7 e os conteúdos são sempre atualizados."</p>
                    </div>
                    
                    <div class="testimonial-card rounded-2xl p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">A</div>
                            <div class="ml-4">
                                <h4 class="font-semibold text-white">Ana Costa</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-300 italic">"Recomendo para todos! A qualidade dos cursos superou minhas expectativas."</p>
                    </div>
                </div>
            </section>
        </main>
        
        <footer class="text-center py-12 text-gray-400 text-sm mt-16 border-t border-white/10">
            <div class="max-w-4xl mx-auto">
                <div class="flex items-center justify-center mb-6">
                    <div class="w-12 h-12 hero-gradient rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-graduation-cap text-white text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-white">HELMER ACADEMY</span>
                </div>
                <p class="mb-4">© <?php echo date('Y'); ?> Helmer Academy. Todos os direitos reservados.</p>
                <p class="text-xs">Transformando carreiras através da educação de qualidade</p>
            </div>
        </footer>
    </div>
</div>

<!-- Botões Flutuantes -->
<div class="fixed bottom-6 right-6 z-50 flex flex-col gap-4">
    <a href="chat.php" target="_blank"
       class="bg-red-500 hover:bg-red-600 text-white p-4 rounded-full shadow-2xl transition-all duration-300 transform hover:scale-110 pulse-glow mobile-optimized"
       aria-label="Acessar Chat ao Vivo" title="Chat ao Vivo">
        <i class="fas fa-comments text-lg"></i>
    </a>
    <a href="https://wa.me/5551996148568" target="_blank" rel="noopener noreferrer" 
       class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-full shadow-2xl transition-all duration-300 transform hover:scale-110 mobile-optimized" 
       aria-label="Fale no WhatsApp" title="Fale no WhatsApp">
        <i class="fab fa-whatsapp text-lg"></i>
    </a>
</div>

<script>
    const menu = document.getElementById('menu');
    function abrirMenu() { menu.classList.remove('-translate-x-full'); }
    function fecharMenu() { menu.classList.add('-translate-x-full'); }
    
    document.querySelectorAll('a[href^="#"]').forEach(element => {
        element.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                fecharMenu();
            }
        });
    });

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => { 
            if(entry.isIntersecting) {
                entry.target.classList.add('show');
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
    
    function marcarNotificacoesComoLidas() {
        let alpineData = document.querySelector('div[x-data]').__x.$data;
        if (alpineData.unreadCount > 0) {
            fetch('marcar_notificacoes_lidas.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => { 
                if (data.status === 'success') { 
                    alpineData.unreadCount = 0; 
                } 
            })
            .catch(error => console.error('Erro:', error));
        }
    }
    
    document.addEventListener('DOMContentLoaded', function () {
        const createSwiper = (selector) => {
            return new Swiper(selector, {
                loop: false,
                slidesPerView: 1,
                spaceBetween: 24,
                pagination: { 
                    el: selector + ' .swiper-pagination', 
                    clickable: true,
                    dynamicBullets: true
                },
                navigation: { 
                    nextEl: selector + ' .swiper-button-next', 
                    prevEl: selector + ' .swiper-button-prev' 
                },
                breakpoints: { 
                    640: { slidesPerView: 2, spaceBetween: 20 }, 
                    1024: { slidesPerView: 3, spaceBetween: 24 }, 
                    1280: { slidesPerView: 4, spaceBetween: 24 } 
                }
            });
        };
        
        const cursosSwiper = createSwiper('.cursos-swiper');
        const produtosSwiper = createSwiper('.produtos-swiper');
        const bannerSwiper = new Swiper('.banner-swiper', {
            loop: true,
            autoplay: { 
                delay: 5000, 
                disableOnInteraction: false 
            },
            navigation: { 
                nextEl: '.banner-next', 
                prevEl: '.banner-prev' 
            },
            pagination: {
                el: '.banner-pagination',
                clickable: true,
                dynamicBullets: true
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            }
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
        
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    });
    
    function performSearch() {
        const searchTerm = document.getElementById('search').value.trim();
        if (searchTerm) {
            window.location.href = `busca_avancada.php?search=${encodeURIComponent(searchTerm)}`;
        }
    }
    
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
            
            showNotification(result.message, result.status === 'added' ? 'success' : 'info');
            
        } catch (error) {
            console.error('Erro ao alterar favorito:', error);
            showNotification('Erro ao alterar favorito', 'error');
        }
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-6 right-6 p-4 rounded-xl shadow-2xl z-50 ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 'bg-blue-600'
        } text-white font-semibold`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
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
    
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallButton();
    });
    
    function showInstallButton() {
        const installButton = document.createElement('button');
        installButton.innerHTML = '<i class="fas fa-download mr-2"></i>Instalar App';
        installButton.className = 'fixed bottom-24 right-6 bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-full shadow-2xl transition-all duration-300 transform hover:scale-110 z-50 mobile-optimized';
        
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
        
        setTimeout(() => {
            if (installButton.parentNode) {
                installButton.remove();
            }
        }, 10000);
    }
</script>
</body>
</html>
