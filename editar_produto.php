<?php
session_start();
require 'config.php';

// MELHORIA: Verificação de role padronizada
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de produto inválido.");
}

// CORREÇÃO CSRF: Gera o token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Lógica de feedback para erros
$erro = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_price') {
        $erro = 'O formato do preço é inválido.';
    }
}

// Busca os dados do produto para exibir no formulário
try {
    // MELHORIA: Otimizando a query
    $stmt = $pdo->prepare("SELECT id, nome, preco, descricao, imagem FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch();

    if (!$produto) {
        die("Produto não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar o produto.");
}


// Processa o formulário via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CORREÇÃO CSRF: Valida o token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Falha na validação CSRF!');
    }

    $nome = trim($_POST['nome'] ?? '');
    $precoInput = trim($_POST['preco'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $imagemParaSalvar = $produto['imagem']; // Assume a imagem antiga por padrão

    if ($nome === '' || $precoInput === '') {
        $erro = "Nome e preço são obrigatórios.";
    } elseif (!is_numeric(str_replace(',', '.', $precoInput))) {
        // Redireciona com erro para limpar o POST
        header('Location: editar_produto.php?id=' . $id . '&error=invalid_price');
        exit();
    } else {
        $preco = str_replace(',', '.', $precoInput);

        // FUNCIONALIDADE FALTANDO: Lógica para upload de nova imagem
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $novoNome = 'uploads/produtos/' . uniqid('produto_', true) . '.' . $ext;
                $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . '/' . $novoNome;

                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminhoAbsoluto)) {
                    // Apaga a imagem antiga se ela existir
                    $caminhoAntigo = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($produto['imagem'], '/');
                    if (!empty($produto['imagem']) && file_exists($caminhoAntigo)) {
                        unlink($caminhoAntigo);
                    }
                    $imagemParaSalvar = $novoNome; // Atualiza para o caminho da nova imagem
                }
            }
        }

        // Atualiza o banco com uma única query
        $stmt = $pdo->prepare('UPDATE produtos SET nome = ?, preco = ?, descricao = ?, imagem = ? WHERE id = ?');
        $stmt->execute([$nome, $preco, $descricao, $imagemParaSalvar, $id]);
        
        // MELHORIA: Redireciona com mensagem de sucesso
        header('Location: produtos.php?msg=product_updated');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Produto | HELMER</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-white min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-xl">
    <form method="post" enctype="multipart/form-data" class="bg-gray-800 p-8 rounded-2xl w-full space-y-6 shadow-lg">
        <h1 class="text-2xl font-bold text-rose-500 mb-4 text-center">Editar Produto</h1>

        <?php if ($erro): ?>
            <div class="bg-red-500/20 text-red-300 border border-red-500 p-3 rounded-lg text-center"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <label class="block">
                <span class="text-gray-300 mb-1 block">Nome</span>
                <input type="text" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500" required>
            </label>

            <label class="block">
                <span class="text-gray-300 mb-1 block">Preço</span>
                <input type="text" name="preco" value="<?php echo htmlspecialchars(str_replace('.', ',', $produto['preco'])); ?>" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500" required>
            </label>
        </div>

        <label class="block">
            <span class="text-gray-300 mb-1 block">Descrição</span>
            <textarea name="descricao" rows="4" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
        </label>
        
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-300">Imagem do Produto</label>
            <div class="flex items-center gap-6">
                <img src="/<?php echo htmlspecialchars($produto['imagem'] ?: 'fotos/padrao.png'); ?>" alt="Imagem atual" class="rounded-lg w-24 h-24 object-cover border-2 border-gray-600">
                <div>
                    <label for="imagem" class="block mb-1 text-sm font-medium text-gray-400">Substituir imagem (opcional)</label>
                    <input type="file" id="imagem" name="imagem" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 py-3 rounded-lg font-bold text-lg transition-transform transform hover:scale-105">Salvar Alterações</button>
    </form>
    <a href="produtos.php" class="block text-center text-gray-400 hover:text-white mt-4 text-sm">Voltar para Produtos</a>
</div>

</body>
</html>