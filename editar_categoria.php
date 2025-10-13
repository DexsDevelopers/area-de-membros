<?php
session_start();
require 'config.php';

// Apenas admins podem acessar
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Pega e valida o ID da categoria da URL
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de categoria inválido.");
}

// Busca a categoria para preencher o formulário
try {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    $categoria = $stmt->fetch();
    if (!$categoria) {
        die("Categoria não encontrada.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar a categoria.");
}

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome_categoria'] ?? '');
    $imagem_atual = $categoria['imagem_url'];
    $erro = '';

    if (empty($nome)) {
        $erro = "O nome da categoria não pode ficar vazio.";
    } else {
        $imagemParaSalvar = $imagem_atual;

        // Lógica de upload de nova imagem (se uma for enviada)
        if (isset($_FILES['imagem_categoria']) && $_FILES['imagem_categoria']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['imagem_categoria']['name'], PATHINFO_EXTENSION));
            $novoNome = 'uploads/categorias/' . uniqid('cat_', true) . '.' . $ext;
            $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . '/' . $novoNome;

            if (move_uploaded_file($_FILES['imagem_categoria']['tmp_name'], $caminhoAbsoluto)) {
                // Apaga a imagem antiga, se ela existir
                $caminhoAntigo = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($imagem_atual, '/');
                if (!empty($imagem_atual) && file_exists($caminhoAntigo)) {
                    unlink($caminhoAntigo);
                }
                $imagemParaSalvar = $novoNome; // Define a nova imagem para ser salva
            } else {
                 $erro = "Erro ao mover a nova imagem.";
            }
        }

        if (empty($erro)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nome)));
            try {
                $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, slug = ?, imagem_url = ? WHERE id = ?");
                $stmt->execute([$nome, $slug, $imagemParaSalvar, $id]);
                
                header("Location: gerenciar_categorias.php?msg=updated");
                exit();
            } catch (PDOException $e) {
                $erro = "Erro ao atualizar a categoria.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Editar Categoria | HELMER ACADEMY</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-gray-200 min-h-screen flex items-center justify-center p-4">

<div class="bg-gray-800 p-8 rounded-2xl w-full max-w-lg shadow-2xl">
    <h1 class="text-2xl font-bold text-white mb-6">Editar Categoria</h1>

    <?php if (!empty($erro)): ?>
        <div class="mb-4 p-3 bg-red-500/80 rounded-lg text-center"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div>
            <label for="nome_categoria" class="block text-sm font-medium text-gray-400 mb-1">Nome da Categoria</label>
            <input type="text" id="nome_categoria" name="nome_categoria" required 
                   value="<?php echo htmlspecialchars($categoria['nome']); ?>"
                   class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>
        
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-400">Imagem Atual</label>
            <div class="flex items-center gap-6">
                <img src="/<?php echo htmlspecialchars($categoria['imagem_url'] ?: 'fotos/padrao.png'); ?>" alt="Imagem atual" class="rounded-full w-20 h-20 object-cover border-2 border-gray-600">
                <div>
                    <label for="imagem_categoria" class="block mb-1 text-sm font-medium text-gray-400">Substituir imagem (opcional)</label>
                    <input type="file" id="imagem_categoria" name="imagem_categoria" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                </div>
            </div>
        </div>
        
        <div class="pt-4 flex items-center gap-4">
            <a href="gerenciar_categorias.php" class="w-1/2 text-center px-6 py-3 rounded-lg bg-gray-600 hover:bg-gray-700 font-semibold transition">Cancelar</a>
            <button type="submit" class="w-1/2 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition">Salvar Alterações</button>
        </div>
    </form>
</div>

</body>
</html>