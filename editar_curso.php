<?php
session_start();
require 'config.php';

// CORREÇÃO 2: Verificação de role de admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de curso inválido.");
}

// Busca dados atuais do curso
try {
    $stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ?");
    $stmt->execute([$id]);
    $curso = $stmt->fetch();

    if (!$curso) {
        die("Curso não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar o curso.");
}

// CORREÇÃO 1 (CSRF): Gera o token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$feedbackMessage = '';
$feedbackType = 'success';

// Processa a edição quando o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_curso'])) {
    
    // CORREÇÃO 1 (CSRF): Valida o token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Falha na validação CSRF.');
    }

    $titulo = trim($_POST['titulo_curso'] ?? '');
    $tipo = $_POST['tipo_curso'] ?? 'premium';
    $link = trim($_POST['link_curso'] ?? '#');
    $imagemParaSalvar = $curso['imagem']; // Começa com a imagem antiga

    if ($titulo === '') {
        $feedbackMessage = "O título do curso não pode ficar vazio.";
        $feedbackType = 'error';
    } else {
        // Lógica de upload de nova imagem (se houver)
        if (isset($_FILES['imagem_curso']) && $_FILES['imagem_curso']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['imagem_curso']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $novoNome = 'uploads/cursos/' . uniqid('curso_', true) . '.' . $ext;
                
                // CORREÇÃO 3 (Caminho Absoluto): Usa DOCUMENT_ROOT
                $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . '/' . $novoNome;

                if (move_uploaded_file($_FILES['imagem_curso']['tmp_name'], $caminhoAbsoluto)) {
                    // Apaga a imagem antiga, se existir
                    $caminhoAntigo = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($curso['imagem'], '/');
                    if (!empty($curso['imagem']) && file_exists($caminhoAntigo)) {
                        unlink($caminhoAntigo);
                    }
                    $imagemParaSalvar = $novoNome; // Define a nova imagem para ser salva
                } else {
                    $feedbackMessage = "Erro ao mover a nova imagem.";
                    $feedbackType = 'error';
                }
            } else {
                $feedbackMessage = "Extensão de imagem não permitida.";
                $feedbackType = 'error';
            }
        }

        // Se não houve erros até agora, executa o UPDATE
        if (empty($feedbackMessage)) {
            try {
                // MELHORIA: Uma única query UPDATE, mais limpa e segura
                $sql = "UPDATE cursos SET titulo = ?, tipo = ?, link = ?, imagem = ? WHERE id = ?";
                $params = [$titulo, $tipo, $link, $imagemParaSalvar, $id];
                
                $pdo->prepare($sql)->execute($params);
                
                // MELHORIA (PRG): Redireciona com mensagem de sucesso
                header("Location: editar_curso.php?id=$id&msg=success");
                exit();

            } catch (PDOException $e) {
                $feedbackMessage = "Erro ao atualizar o curso no banco de dados.";
                $feedbackType = 'error';
            }
        }
    }
}

// Lógica para exibir mensagem de sucesso após o redirecionamento
if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $feedbackMessage = "Curso atualizado com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Editar Curso - <?php echo htmlspecialchars($curso['titulo']); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">

<div class="max-w-xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Editar Curso: <span class="text-rose-500"><?php echo htmlspecialchars($curso['titulo']); ?></span></h1>

    <?php if ($feedbackMessage): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo $feedbackType === 'success' ? 'bg-emerald-500/80' : 'bg-red-500/80'; ?>">
        <?php echo htmlspecialchars($feedbackMessage); ?>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-6 bg-gray-800 p-8 rounded-2xl">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

        <div>
            <label for="titulo" class="block mb-1 text-sm font-medium text-gray-400">Título do Curso</label>
            <input type="text" id="titulo" name="titulo_curso" required class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500" value="<?php echo htmlspecialchars($curso['titulo']); ?>">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="tipo" class="block mb-1 text-sm font-medium text-gray-400">Tipo</label>
                <select id="tipo" name="tipo_curso" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500" style="appearance: none; background-image: url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e&quot;); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em;">
                    <option value="premium" <?php if ($curso['tipo'] == 'premium') echo 'selected'; ?>>Premium</option>
                    <option value="gratuitos" <?php if ($curso['tipo'] == 'gratuitos') echo 'selected'; ?>>Gratuito</option>
                </select>
            </div>
            <div>
                <label for="link" class="block mb-1 text-sm font-medium text-gray-400">Link do Material</label>
                <input type="url" id="link" name="link_curso" placeholder="https://..." class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500" value="<?php echo htmlspecialchars($curso['link']); ?>">
            </div>
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-400">Imagem do Curso</label>
            <div class="flex items-center gap-6">
                <img src="/<?php echo htmlspecialchars($curso['imagem']); ?>" alt="Imagem atual" class="rounded-lg w-32 h-32 object-cover border-2 border-gray-600">
                <div>
                    <label for="imagem_curso" class="block mb-1 text-sm font-medium text-gray-400">Substituir imagem (opcional)</label>
                    <input type="file" id="imagem_curso" name="imagem_curso" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                </div>
            </div>
        </div>

        <div class="pt-4">
            <button type="submit" name="editar_curso" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 px-6 rounded-lg transition-transform transform hover:scale-105">
                Salvar Alterações
            </button>
        </div>
    </form>

    <div class="mt-6 text-center">
        <a href="cursos.php" class="text-sm text-gray-400 hover:underline hover:text-rose-500 transition">Voltar para a lista de cursos</a>
    </div>
</div>

</body>
</html>