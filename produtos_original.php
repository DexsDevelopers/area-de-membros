<?php
session_start();
require 'config.php';

// Padroniza a verificação de autorização
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

// Redirecionar para a versão moderna
header("Location: produtos_moderno.php");
exit();

// Lógica para feedback
$feedbackMessage = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'product_added') $feedbackMessage = 'Produto cadastrado com sucesso!';
    if ($_GET['msg'] === 'product_deleted') $feedbackMessage = 'Produto excluído com sucesso!';
}

// Lógica de cadastro (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_produto'])) {
    // Valida o token CSRF aqui, se implementado

    // Coleta segura dos dados
    $nome = trim($_POST['nome'] ?? '');
    $preco = str_replace(',', '.', trim($_POST['preco'] ?? '0'));
    $descricao = trim($_POST['descricao'] ?? '');

    $erro = '';
    $imagemPath = '';

    if (empty($nome) || !is_numeric($preco)) {
        $erro = "Nome e preço (válido) são obrigatórios.";
    }

    // Processamento seguro do upload de imagem com otimização
    if (empty($erro) && isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        require_once 'image_optimizer.php';
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileName = $_FILES['imagem']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExtension, $allowedExtensions)) {
            // Gera um nome único e seguro para o arquivo
            $novoNome = 'uploads/produtos/' . uniqid('prod_', true) . '.' . $fileExtension;
            $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . '/' . $novoNome;

            if (!is_dir(dirname($caminhoAbsoluto))) {
                mkdir(dirname($caminhoAbsoluto), 0755, true);
            }

            // Processa upload com otimização automática
            $optimizer = new ImageOptimizer(800, 600, 85);
            if ($optimizer->processUpload($_FILES['imagem'], dirname($caminhoAbsoluto), basename($novoNome))) {
                $imagemPath = $novoNome;
                
                // Gera versões responsivas
                $responsive_images = $optimizer->generateResponsiveImages(
                    $caminhoAbsoluto, 
                    pathinfo($novoNome, PATHINFO_FILENAME),
                    [400, 800, 1200]
                );
                
                // Salva informações das imagens responsivas no banco (opcional)
                // Você pode criar uma tabela para armazenar essas informações
            } else {
                $erro = "Falha ao processar a imagem.";
            }
        } else {
            $erro = "Tipo de imagem não permitido. Use jpg, png, gif ou webp.";
        }
    } elseif (empty($erro)) {
        $erro = "É necessário enviar uma imagem para o produto.";
    }
    
    // Se não houver erros, insere no banco
    if (empty($erro)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO produtos (nome, preco, descricao, imagem, ativo) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$nome, $preco, $descricao, $imagemPath]);
            header('Location: produtos.php?msg=product_added');
            exit();
        } catch (PDOException $e) {
            $erro = "Erro ao salvar no banco de dados.";
            error_log($e->getMessage());
        }
    }
    
    // Se houve erro, define a mensagem de feedback
    if ($erro) {
        $feedbackMessage = $erro;
    }
}

// Busca os produtos para exibir na página
$produtos = $pdo->query('SELECT id, nome, preco, imagem FROM produtos ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Produtos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6 font-sans">
<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">Gerenciar Produtos</h1>

    <?php if ($feedbackMessage): ?>
        <div class="p-4 mb-6 rounded-lg <?php echo isset($_GET['msg']) ? 'bg-emerald-500/80' : 'bg-red-500/80'; ?>">
            <?php echo htmlspecialchars($feedbackMessage); ?>
        </div>
    <?php endif; ?>

    <div class="bg-gray-800 p-6 rounded-2xl mb-10">
        <h2 class="text-2xl font-semibold mb-4">Adicionar Novo Produto</h2>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="cadastrar_produto" value="1">
            <input name="nome" placeholder="Nome do Produto" required class="p-3 rounded-lg bg-gray-700 w-full border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500">
            <input name="preco" placeholder="Preço (ex: 49,90)" required class="p-3 rounded-lg bg-gray-700 w-full border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500">
            <textarea name="descricao" placeholder="Descrição" rows="3" class="p-3 rounded-lg bg-gray-700 w-full border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
            <input type="file" name="imagem" required class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
            <button type="submit" class="bg-rose-600 hover:bg-rose-700 px-6 py-3 rounded-lg font-bold transition">Cadastrar Produto</button>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach($produtos as $p): ?>
            <div class="bg-gray-800 rounded-2xl shadow-lg overflow-hidden flex flex-col">
                <img src="/<?php echo htmlspecialchars($p['imagem']); ?>" alt="<?php echo htmlspecialchars($p['nome']); ?>" class="w-full h-48 object-cover">
                <div class="p-4 flex flex-col flex-grow">
                    <h3 class="font-bold text-lg flex-grow"><?php echo htmlspecialchars($p['nome']); ?></h3>
                    <p class="text-xl font-semibold text-teal-400 mt-2">R$ <?php echo htmlspecialchars(number_format($p['preco'], 2, ',', '.')); ?></p>
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-700">
                        <a href="editar_produto.php?id=<?php echo $p['id']; ?>" class="font-semibold text-sky-400 hover:text-sky-300 text-sm">Editar</a>
                        <form action="excluir_produto.php" method="POST" onsubmit="return confirm('Tem certeza?');">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="font-semibold text-rose-500 hover:text-rose-400 bg-transparent border-none p-0 cursor-pointer text-sm">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>