<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

require 'config.php'; // conexão PDO

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
  echo "Produto inválido.";
  exit();
}

$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch();

if (!$produto) {
  echo "Produto não encontrado.";
  exit();
}

// Tratamento para evitar campos nulos
$produto['descricao_curta'] = $produto['descricao_curta'] ?? '';
$produto['descricao_longa'] = $produto['descricao_longa'] ?? '';
$produto['link_compra'] = $produto['link_compra'] ?? '#';
$produto['imagem'] = $produto['imagem'] ?: 'fotos/padrao.png'; // fallback

// --- LÓGICA DE COMENTÁRIOS ADICIONADA ---
try {
    // Usamos um JOIN para pegar também o nome de usuário de quem comentou
    $comentarios_stmt = $pdo->prepare(
        "SELECT c.*, u.username 
         FROM comentarios c
         JOIN users u ON c.user_id = u.id
         WHERE c.conteudo_id = ? AND c.tipo_conteudo = ?
         ORDER BY c.data_publicacao DESC"
    );
    // Para curso_pagina.php, use 'curso'. Para produtos_pagina.php, use 'produto'.
    $comentarios_stmt->execute([$id, 'produto']); 
    $comentarios = $comentarios_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $comentarios = [];
    error_log("Erro ao buscar comentários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($produto['nome']); ?> | HELMER ACADEMY</title>
  <meta name="description" content="<?php echo htmlspecialchars($produto['descricao_curta']); ?>">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
    * { font-family: 'Inter', sans-serif; }

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

    .glass-effect {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
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
        transform: translateY(-8px) scale(1.03);
        box-shadow: 0 15px 30px rgba(220, 38, 38, 0.4), 0 0 20px rgba(220, 38, 38, 0.3);
        border-color: rgba(220, 38, 38, 0.6);
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

    .glow-effect {
        filter: drop-shadow(0 0 10px rgba(220, 38, 38, 0.5));
        animation: glowPulse 2s ease-in-out infinite alternate;
    }

    @keyframes glowPulse {
        0% { filter: drop-shadow(0 0 10px rgba(220, 38, 38, 0.5)); }
        100% { filter: drop-shadow(0 0 20px rgba(220, 38, 38, 0.8)); }
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

    .scroll-reveal {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s ease-out;
    }

    .scroll-reveal.show {
        opacity: 1;
        transform: translateY(0);
    }

    /* Mantém as quebras de linha na descrição longa */
    .descricao-longa {
        white-space: pre-line;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #1a1a1a; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #dc2626; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #ef4444; }

    /* Garantir que o scroll funcione */
    html, body {
        overflow-x: hidden;
        overflow-y: auto;
        height: 100%;
    }

    .main-content {
        overflow-y: auto;
        height: 100vh;
        -webkit-overflow-scrolling: touch;
    }
  </style>
</head>
<body class="gradient-bg text-white font-sans min-h-screen relative overflow-hidden">

  <!-- Partículas de fundo -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="particle w-3 h-3 top-1/4 left-1/4" style="animation-delay: 0s;"></div>
    <div class="particle w-4 h-4 top-1/3 right-1/4" style="animation-delay: 1s;"></div>
    <div class="particle w-2 h-2 bottom-1/4 left-1/3" style="animation-delay: 2s;"></div>
    <div class="particle w-3 h-3 bottom-1/3 right-1/3" style="animation-delay: 3s;"></div>
    <div class="particle w-2 h-2 top-1/2 left-1/2" style="animation-delay: 4s;"></div>
    <div class="particle w-4 h-4 top-3/4 right-1/3" style="animation-delay: 5s;"></div>
  </div>

  <div class="flex flex-col md:flex-row min-h-screen relative z-10" x-data="{ sidebarOpen: false }">
    <!-- Menu lateral -->
    <aside class="fixed top-0 left-0 z-50 bg-black/90 backdrop-blur-lg text-white w-full max-w-xs h-full p-6 space-y-6 transform -translate-x-full transition-transform duration-300 md:translate-x-0 md:relative md:block md:w-64 md:min-h-screen glass-effect"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
      <div class="flex justify-center items-center mb-4 relative">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center">
            <i class="fas fa-graduation-cap text-white text-lg"></i>
          </div>
          <span class="text-xl font-extrabold tracking-widest text-center dopamine-text">HELMER ACADEMY</span>
        </div>
        <button @click="sidebarOpen = false" class="md:hidden text-white text-2xl absolute top-0 right-0 mt-4 mr-4 hover:text-red-400 transition-colors">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <nav class="flex flex-col space-y-3">
        <a href="../index.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-red-700/30 transition-all duration-300 group">
          <i class="fas fa-home text-red-400 group-hover:text-white transition-colors"></i>
          <span class="group-hover:text-white transition-colors">Início</span>
        </a>
        <a href="../index.php#cursos" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-red-700/30 transition-all duration-300 group">
          <i class="fas fa-play-circle text-red-400 group-hover:text-white transition-colors"></i>
          <span class="group-hover:text-white transition-colors">Cursos</span>
        </a>
        <a href="../produtos.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-red-700/30 transition-all duration-300 group">
          <i class="fas fa-shopping-bag text-red-400 group-hover:text-white transition-colors"></i>
          <span class="group-hover:text-white transition-colors">Produtos</span>
        </a>
        <a href="../chat.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-red-700/30 transition-all duration-300 group">
          <i class="fas fa-comments text-red-400 group-hover:text-white transition-colors"></i>
          <span class="group-hover:text-white transition-colors">Chat</span>
        </a>
      </nav>
    </aside>

    <!-- Overlay para mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/50 z-40 md:hidden"></div>

    <!-- Conteúdo principal -->
    <div class="flex-1 ml-0 md:ml-64">
      <main class="p-4 md:p-8 space-y-8 main-content">

        <!-- Topo mobile -->
        <div class="md:hidden mb-6 flex items-center justify-between">
          <button @click="sidebarOpen = true" class="p-3 bg-red-600/20 backdrop-blur-sm rounded-xl border border-red-500/30 hover:bg-red-600/30 transition-all duration-300">
            <i class="fas fa-bars text-red-400"></i>
          </button>
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center">
              <i class="fas fa-graduation-cap text-white text-sm"></i>
            </div>
            <span class="text-lg font-bold dopamine-text">HELMER ACADEMY</span>
          </div>
        </div>

        <!-- Banner do produto -->
        <div class="scroll-reveal card-premium rounded-2xl overflow-hidden">
          <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" 
               alt="<?php echo htmlspecialchars($produto['nome']); ?>" 
               loading="lazy" 
               class="w-full h-64 md:h-96 object-cover glow-effect" />
        </div>

        <!-- Nome do produto -->
        <h1 class="scroll-reveal text-3xl md:text-5xl font-bold text-center dopamine-text mb-4">
          <?php echo htmlspecialchars($produto['nome']); ?>
        </h1>

        <!-- Preço -->
        <div class="scroll-reveal text-center mb-8">
          <div class="inline-block bg-gradient-to-r from-red-600 to-red-800 px-8 py-4 rounded-2xl shadow-2xl">
            <span class="text-4xl md:text-5xl font-bold text-white">
              R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
            </span>
          </div>
        </div>

        <!-- Descrição longa -->
        <div class="scroll-reveal card-premium p-6 md:p-8 space-y-4 text-gray-300 text-sm md:text-base descricao-longa">
          <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
            <i class="fas fa-info-circle text-red-400 mr-3"></i>
            Descrição
          </h2>
          <p class="leading-relaxed"><?php echo htmlspecialchars($produto['descricao_longa']); ?></p>
        </div>

        <!-- Botão de compra -->
        <div class="scroll-reveal text-center">
          <a href="<?php echo htmlspecialchars($produto['link_compra']); ?>" 
             target="_blank" 
             rel="noopener noreferrer" 
             class="btn-premium inline-flex items-center space-x-3 text-white font-bold px-8 py-4 rounded-2xl text-lg transition-all duration-300">
            <i class="fas fa-shopping-cart"></i>
            <span>Comprar Agora</span>
            <i class="fas fa-arrow-right"></i>
          </a>
        </div>

        <!-- Benefícios -->
        <?php if(!empty($produto['beneficios'])): ?>
        <section class="scroll-reveal card-premium p-6 md:p-8">
          <h2 class="text-2xl md:text-3xl font-bold text-white mb-6 flex items-center">
            <i class="fas fa-star text-red-400 mr-3"></i>
            Benefícios
          </h2>
          <ul class="space-y-4">
            <?php
              $beneficios = explode("\n", $produto['beneficios']);
              foreach ($beneficios as $b) {
                $b = trim($b);
                if ($b) {
                  echo "<li class='flex items-start space-x-3 text-gray-300'>
                          <i class='fas fa-check-circle text-red-400 mt-1 flex-shrink-0'></i>
                          <span>" . htmlspecialchars($b) . "</span>
                        </li>";
                }
              }
            ?>
          </ul>
        </section>
        <?php endif; ?>

        <!-- Seção de Comentários -->
        <section class="fade-in pt-8 mt-8 border-t border-gray-700/50">
          <h2 class="text-2xl md:text-3xl font-bold mb-6 flex items-center">
            <i class="fas fa-comments text-red-400 mr-3"></i>
            Comentários 
            <span class="text-red-400 ml-2">(<?= count($comentarios) ?>)</span>
          </h2>

          <?php if (isset($_SESSION['user_id'])): ?>
          <div class="card-premium p-6 rounded-2xl mb-8">
            <form action="adicionar_comentario.php" method="POST" class="space-y-4">
              <input type="hidden" name="conteudo_id" value="<?php echo $id; ?>">
              <input type="hidden" name="tipo_conteudo" value="produto"> 
              <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-300">
                  <i class="fas fa-comment mr-2"></i>
                  Deixe seu comentário ou dúvida
                </label>
                <textarea name="comentario" 
                          rows="4" 
                          placeholder="Compartilhe sua experiência com este produto..." 
                          required 
                          class="w-full p-4 rounded-xl bg-gray-800/50 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-300 resize-none"></textarea>
                <button type="submit" 
                        class="btn-premium text-white font-bold py-3 px-6 rounded-xl transition-all duration-300">
                  <i class="fas fa-paper-plane mr-2"></i>
                  Enviar Comentário
                </button>
              </div>
            </form>
          </div>
          <?php else: ?>
          <div class="card-premium p-6 rounded-2xl mb-8 text-center">
            <div class="flex items-center justify-center space-x-3 text-gray-400">
              <i class="fas fa-lock"></i>
              <p>
                <a href="../login.php" class="text-red-400 font-semibold hover:text-red-300 hover:underline transition-colors">
                  Faça login
                </a> 
                para deixar um comentário.
              </p>
            </div>
          </div>
          <?php endif; ?>

          <div class="space-y-6">
            <?php if (count($comentarios) > 0): ?>
              <?php foreach ($comentarios as $comentario): ?>
                <div id="comentario-<?= $comentario['id'] ?>" class="card-premium p-6 rounded-xl">
                  <div class="flex gap-4">
                    <div class="flex-shrink-0">
                      <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center font-bold text-white text-lg">
                        <?= strtoupper(substr($comentario['username'], 0, 1)) ?>
                      </div>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center gap-3 mb-2">
                        <span class="font-bold text-white text-lg"><?= htmlspecialchars($comentario['username']) ?></span>
                        <span class="text-xs text-gray-500 bg-gray-800/50 px-2 py-1 rounded-full">
                          <?= date('d/m/Y \à\s H:i', strtotime($comentario['data_publicacao'])) ?>
                        </span>
                      </div>
                      <p class="text-gray-300 leading-relaxed"><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="card-premium p-8 rounded-2xl text-center">
                <i class="fas fa-comment-slash text-4xl text-gray-500 mb-4"></i>
                <p class="text-gray-500 text-lg">Ainda não há comentários. Seja o primeiro a comentar!</p>
              </div>
            <?php endif; ?>
          </div>
        </section>

        <!-- Footer -->
        <footer class="text-center py-8 text-gray-500 text-sm mt-12">
          <div class="flex items-center justify-center space-x-2 mb-4">
            <div class="w-6 h-6 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center">
              <i class="fas fa-graduation-cap text-white text-xs"></i>
            </div>
            <span class="font-bold">HELMER ACADEMY</span>
          </div>
          <p>© <?php echo date('Y'); ?> Área restrita | Só pra quem tá no jogo</p>
        </footer>
      </main>
    </div>
  </div>

  <!-- Botão WhatsApp -->
  <a href="https://wa.me/5551996148568" 
     target="_blank" 
     rel="noopener noreferrer" 
     aria-label="Fale no WhatsApp" 
     class="fixed bottom-6 right-6 z-50 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white p-4 rounded-full shadow-2xl transition-all duration-300 hover:scale-110 glow-effect">
    <i class="fab fa-whatsapp text-2xl"></i>
  </a>

  <script>
    // Animações de scroll
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

    // Partículas interativas
    document.addEventListener('mousemove', function(e) {
      const particles = document.querySelectorAll('.particle');
      particles.forEach((particle, index) => {
        const speed = (index + 1) * 0.1;
        const x = e.clientX * speed / 100;
        const y = e.clientY * speed / 100;
        particle.style.transform = `translate(${x}px, ${y}px)`;
      });
    });

    // Forçar scroll habilitado
    document.addEventListener('DOMContentLoaded', function() {
      document.body.style.overflow = 'auto';
      document.documentElement.style.overflow = 'auto';
      document.body.style.height = 'auto';
      document.documentElement.style.height = 'auto';
      console.log('Página de produto carregada com sucesso');
    });
  </script>
</body>
</html>
