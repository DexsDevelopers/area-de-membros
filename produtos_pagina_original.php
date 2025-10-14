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
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .neon-box {
      box-shadow: 0 0 10px #f00, 0 0 20px #f00;
      transition: box-shadow 0.3s ease-in-out;
    }
    .neon-box:hover {
      box-shadow: 0 0 20px #f00, 0 0 40px #f00;
    }
    .titulo-section {
      color: #fff;
      text-shadow: 0 0 5px red;
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
    /* Botão hover */
    .btn-comprar:hover {
      background-color: #b91c1c; /* vermelho mais escuro */
    }
  </style>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white font-sans overflow-x-hidden">

  <div class="flex flex-col md:flex-row min-h-screen">
    <!-- Menu lateral -->
    <aside id="menu" class="fixed top-0 left-0 z-50 bg-black/90 backdrop-blur-md text-white w-full max-w-xs h-full p-6 space-y-6 transform -translate-x-full transition-transform duration-300 md:translate-x-0 md:relative md:block md:w-56 md:min-h-screen">
      <div class="flex justify-center items-center mb-4 relative">
        <span class="text-xl font-extrabold tracking-widest text-center">HELMER ACADEMY</span>
        <button aria-label="Fechar menu" class="md:hidden text-white text-2xl absolute top-0 right-0 mt-4 mr-4" onclick="fecharMenu()">&times;</button>
      </div>
      <nav class="flex flex-col items-center space-y-3">
        <a href="../index.php" class="hover:bg-red-700 rounded px-4 py-2 w-full text-center">Início</a>
        <a href="../index.php#cursos" class="hover:bg-red-700 rounded px-4 py-2 w-full text-center">Cursos</a>
        <a href="../pages/produtos.php" class="hover:bg-red-700 rounded px-4 py-2 w-full text-center">Produtos</a>
      </nav>
    </aside>

    <!-- Conteúdo principal -->
    <div class="flex-1 ml-0 md:ml-56">
      <main class="p-4 space-y-8 bg-white/10 backdrop-blur-md rounded-xl">

        <!-- Topo mobile -->
        <div class="md:hidden mb-4 flex items-center gap-4">
          <button aria-label="Abrir menu" onclick="abrirMenu()" class="p-2 bg-red-700 rounded">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <span class="text-center text-xl font-bold">HELMER ACADEMY</span>
        </div>

        <!-- Banner do produto -->
        <div class="scroll-reveal">
          <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" loading="lazy" class="w-full rounded-xl shadow-lg neon-box" />
        </div>

        <!-- Nome do produto -->
        <h1 class="scroll-reveal titulo-section text-2xl md:text-3xl"><?php echo htmlspecialchars($produto['nome']); ?></h1>

        <!-- Preço -->
        <div class="scroll-reveal text-center">
          <span class="text-pink-400 text-3xl font-bold">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
        </div>

        <!-- Descrição longa -->
        <div class="scroll-reveal space-y-4 text-gray-300 text-sm md:text-base descricao-longa">
          <p><?php echo htmlspecialchars($produto['descricao_longa']); ?></p>
        </div>

        <!-- Botão de compra -->
        <div class="scroll-reveal text-center">
          <a href="<?php echo htmlspecialchars($produto['link_compra']); ?>" target="_blank" rel="noopener noreferrer" class="btn-comprar inline-block bg-red-700 text-white font-semibold px-6 py-3 rounded-full shadow-lg neon-box transition duration-300">Comprar Agora</a>
        </div>

        <!-- Benefícios -->
        <?php if(!empty($produto['beneficios'])): ?>
        <section class="scroll-reveal">
          <h2 class="titulo-section text-xl md:text-2xl mb-4">Benefícios</h2>
          <ul class="space-y-2 list-disc list-inside text-gray-300">
            <?php
              $beneficios = explode("\n", $produto['beneficios']);
              foreach ($beneficios as $b) {
                $b = trim($b);
                if ($b) echo "<li>" . htmlspecialchars($b) . "</li>";
              }
            ?>
          </ul>
        </section>
        <?php endif; ?>

      </main>
      
      <section id="comentarios" class="fade-in pt-8 mt-8 border-t border-gray-700">
    <h2 class="text-2xl md:text-3xl font-bold mb-6">Comentários (<span class="text-rose-400"><?= count($comentarios) ?></span>)</h2>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="bg-gray-800 p-6 rounded-2xl mb-8">
        <form action="adicionar_comentario.php" method="POST" class="space-y-4">
            <input type="hidden" name="conteudo_id" value="<?php echo $id; ?>">
            <input type="hidden" name="tipo_conteudo" value="produto"> 
            <textarea name="comentario" rows="4" placeholder="Deixe seu comentário ou dúvida..." required class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-6 rounded-lg transition">Enviar Comentário</button>
        </form>
    </div>
    <?php else: ?>
    <div class="bg-gray-800 p-6 rounded-2xl mb-8 text-center">
        <p><a href="login.php" class="text-rose-400 font-semibold hover:underline">Faça login</a> para deixar um comentário.</p>
    </div>
    <?php endif; ?>

    <div class="space-y-6">
        <?php if (count($comentarios) > 0): ?>
            <?php foreach ($comentarios as $comentario): ?>
                <div id="comentario-<?= $comentario['id'] ?>" class="flex gap-4 bg-gray-800/50 p-4 rounded-xl">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center font-bold text-rose-400">
                            <?= strtoupper(substr($comentario['username'], 0, 1)) ?>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-white"><?= htmlspecialchars($comentario['username']) ?></span>
                            <span class="text-xs text-gray-500"><?= date('d/m/Y \à\s H:i', strtotime($comentario['data_publicacao'])) ?></span>
                        </div>
                        <p class="text-gray-300 mt-2"><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-gray-500">Ainda não há comentários. Seja o primeiro a comentar!</p>
        <?php endif; ?>
    </div>
</section>

      <footer class="text-center py-6 text-gray-500 text-xs mt-8">© 2025 Área restrita | Só pra quem tá no jogo</footer>
    </div>
  </div>

  <!-- Botão WhatsApp -->
  <a href="https://wa.me/5551996148568" target="_blank" rel="noopener noreferrer" aria-label="Fale no WhatsApp" class="fixed bottom-4 right-4 z-50 bg-green-500 hover:bg-green-600 text-white p-3 rounded-full shadow-lg transition duration-300 animate-bounce">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="white" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M20.52 3.48A11.89 11.89 0 0 0 12.004 0c-6.63 0-12 5.37-12 12a11.93 11.93 0 0 0 1.69 6.09L0 24l5.92-1.55A11.89 11.89 0 0 0 12.004 24c6.63 0 12-5.37 12-12 0-3.21-1.25-6.22-3.48-8.52zM12 22c-1.65 0-3.26-.38-4.69-1.09l-.34-.18-3.52.92.94-3.43-.22-.35A9.956 9.956 0 0 1 2 12c0-5.51 4.49-10 10-10s10 4.49 10 10-4.49 10-10 10zm5.04-7.49c-.27-.14-1.6-.79-1.85-.88-.25-.09-.44-.14-.62.14s-.71.88-.87 1.06c-.16.18-.32.2-.59.07-.27-.14-1.15-.42-2.18-1.35-.8-.72-1.35-1.61-1.51-1.88-.16-.27-.02-.42.12-.56.12-.12.27-.32.41-.48.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.48-.07-.14-.62-1.5-.84-2.05-.22-.53-.43-.46-.62-.47-.16-.01-.34-.02-.52-.02s-.48.07-.73.35c-.25.27-.96.94-.96 2.29s.99 2.66 1.13 2.84c.14.18 1.95 2.97 4.73 4.17.66.28 1.17.45 1.57.58.66.21 1.27.18 1.74.11.53-.08 1.6-.65 1.83-1.27.23-.62.23-1.15.16-1.27-.07-.12-.25-.18-.52-.32z"/>
    </svg>
  </a>

  <script>
    function abrirMenu() { document.getElementById('menu').classList.remove('-translate-x-full'); }
    function fecharMenu() { document.getElementById('menu').classList.add('-translate-x-full'); }

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('show');
      });
    });
    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
  </script>
</body>
</html>
