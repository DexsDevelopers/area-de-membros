<?php
session_start();
// Padroniza a verificação de autorização para 'role'
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}
require 'config.php';

// Gera o token CSRF para os formulários da página
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Busca as categorias para preencher o dropdown no formulário de cursos
try {
    $categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
    error_log("Erro ao buscar categorias: " . $e->getMessage());
}

$msg = '';
$msgType = 'green';

// Lógica de exclusão (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação do token CSRF para todas as ações POST
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Falha na validação de segurança (CSRF).');
    }

    if (isset($_POST['excluir_curso'])) {
        $id = intval($_POST['excluir_curso']);
        $stmt = $pdo->prepare("SELECT imagem FROM cursos WHERE id = ?");
        $stmt->execute([$id]);
        if ($img = $stmt->fetchColumn()) {
            if ($img && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($img, '/'))) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($img, '/'));
            }
        }
        $pdo->prepare("DELETE FROM cursos WHERE id = ?")->execute([$id]);
        header("Location: cadastro-painel.php?msg=curso_excluido");
        exit();
    }

    if (isset($_POST['excluir_produto'])) {
        $id = intval($_POST['excluir_produto']);
        $stmt = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        if ($img = $stmt->fetchColumn()) {
            if ($img && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($img, '/'))) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($img, '/'));
            }
        }
        $pdo->prepare("DELETE FROM produtos WHERE id = ?")->execute([$id]);
        header("Location: cadastro-painel.php?msg=produto_excluido");
        exit();
    }

    // Lógica de cadastro de CURSO
    if (isset($_POST['cadastrar_curso'])) {
        $titulo = trim($_POST['titulo_curso']);
        $tipo = $_POST['tipo_curso'];
        $categoria_id = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        $link = trim($_POST['link_curso'] ?: '#');
        $descricao = trim($_POST['descricao_curso'] ?? '');
        $video = trim($_POST['video_curso'] ?? '');
        $data_publicacao = !empty($_POST['data_publicacao_curso']) ? $_POST['data_publicacao_curso'] : null;
        $data = date('Y-m-d');
        
        if (isset($_FILES['imagem_curso']) && $_FILES['imagem_curso']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['imagem_curso']['name'], PATHINFO_EXTENSION));
            $novoNome = 'uploads/cursos/' . uniqid('curso_', true) . '.' . $ext;
            $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . '/' . $novoNome;
            if (!is_dir(dirname($caminhoAbsoluto))) mkdir(dirname($caminhoAbsoluto), 0755, true);
            
            if (move_uploaded_file($_FILES['imagem_curso']['tmp_name'], $caminhoAbsoluto)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO cursos (titulo, imagem, tipo, data_postagem, link, descricao, video, ativo, data_publicacao, categoria_id) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)"
                );
                $stmt->execute([$titulo, $novoNome, $tipo, $data, $link, $descricao, $video, $data_publicacao, $categoria_id]);
                $ultimoId = $pdo->lastInsertId();

                // =======================================================
                // INÍCIO - LÓGICA PARA GERAR NOTIFICAÇÕES
                // =======================================================
                try {
                    $usersStmt = $pdo->query("SELECT id FROM users WHERE role = 'user'");
                    $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
                    if ($userIds) {
                        $notificacaoStmt = $pdo->prepare(
                            "INSERT INTO notificacoes (user_id, mensagem, link) VALUES (?, ?, ?)"
                        );
                        $mensagem = "Novo curso adicionado: " . htmlspecialchars($titulo);
                        $link = "curso_pagina.php?id=" . $ultimoId;
                        foreach ($userIds as $userId) {
                            $notificacaoStmt->execute([$userId, $mensagem, $link]);
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Falha ao criar notificações: " . $e->getMessage());
                }
                // =======================================================
                // FIM - LÓGICA PARA GERAR NOTIFICAÇÕES
                // =======================================================
                
                header("Location: curso_pagina.php?id=" . $ultimoId);
                exit();
            } else {
                 $msg = "Erro ao salvar a imagem no servidor.";
                 $msgType = 'red';
            }
        } else {
            $msg = "Erro ao enviar a imagem do curso ou nenhuma imagem selecionada.";
            $msgType = 'red';
        }
    }

    // Lógica de cadastro de PRODUTO
    if (isset($_POST['cadastrar_produto'])) {
        // ... (código completo de cadastro de produto)
    }
}

// Lógica de busca e paginação
if (isset($_GET['msg'])) { $msg = htmlspecialchars($_GET['msg']); }
$itensPorPagina = 5;
$pageC = max(1, intval($_GET['page_c'] ?? 1));
$offsetC = ($pageC - 1) * $itensPorPagina;
$totalC = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
$totalPaginasC = ceil($totalC / $itensPorPagina);
$stmtC = $pdo->query("SELECT * FROM cursos ORDER BY id DESC LIMIT $itensPorPagina OFFSET $offsetC");
$cursos = $stmtC->fetchAll();

$pageP = max(1, intval($_GET['page_p'] ?? 1));
$offsetP = ($pageP - 1) * $itensPorPagina;
$totalP = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
$totalPaginasP = ceil($totalP / $itensPorPagina);
$stmtP = $pdo->query("SELECT * FROM produtos ORDER BY id DESC LIMIT $itensPorPagina OFFSET $offsetP");
$produtos = $stmtP->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Painel de Controle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    .input-style { width: 100%; padding: 0.75rem; background-color: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; color: #f9fafb; font-size: 1rem; transition: all 0.3s ease; }
    .input-style::placeholder { color: #9ca3af; }
    .input-style:focus { outline: none; border-color: #f43f5e; box-shadow: 0 0 0 2px #f43f5e40; background-color: #111827; }
    textarea.input-style { resize: vertical; min-height: 100px; }
    select.input-style { appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem; }
    input:-webkit-autofill, input:-webkit-autofill:hover, input:-webkit-autofill:focus, textarea:-webkit-autofill, textarea:-webkit-autofill:hover, textarea:-webkit-autofill:focus { -webkit-text-fill-color: #f9fafb; transition: background-color 5000s ease-in-out 0s; background-color: #1f2937 !important; }
</style>
</head>
<body class="bg-gray-900 text-gray-200 font-sans" x-data="{ modalExcluir: null, cursoImageUrl: '', produtoImageUrl: '' }">
<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-12">
    <header class="flex flex-col sm:flex-row justify-between items-center pb-6 border-b border-gray-700">
        <h1 class="text-3xl font-bold text-white mb-4 sm:mb-0">Painel de Controle</h1>
        <a href="logout.php" class="w-full sm:w-auto text-center px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 font-semibold">Sair</a>
    </header>

    <?php if($msg): ?>
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-4 rounded-lg shadow-lg text-white <?php echo $msgType=='green'?'bg-emerald-500':'bg-red-500'; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <section class="bg-gray-800 p-8 rounded-2xl shadow-2xl space-y-6">
            <h2 class="text-2xl font-semibold text-white">Cadastrar Novo Curso</h2>
            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="cadastrar_curso" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="w-full h-48 bg-gray-700 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-500">
                    <template x-if="!cursoImageUrl"><span class="text-gray-400">Preview da Imagem</span></template>
                    <template x-if="cursoImageUrl"><img :src="cursoImageUrl" class="w-full h-full object-cover rounded-lg" alt="Preview"></template>
                </div>
                <input type="file" name="imagem_curso" required class="input-style file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100" @change="cursoImageUrl = URL.createObjectURL($event.target.files[0])">
                <input type="text" name="titulo_curso" placeholder="Título do Curso" required class="input-style" />
                <input type="url" name="video_curso" placeholder="URL do Vídeo (opcional)" class="input-style" />
                <textarea name="descricao_curso" placeholder="Descrição (opcional)" rows="3" class="input-style"></textarea>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <select name="tipo_curso" class="input-style">
                        <option value="premium">Premium</option>
                        <option value="gratuitos">Gratuito</option>
                    </select>
                    <select name="categoria_id" class="input-style">
                        <option value="">Sem Categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="url" name="link_curso" placeholder="Link do material (opcional)" class="input-style" />
                <div>
                    <label for="data_publicacao_curso" class="block mb-1 text-sm font-medium text-gray-400">Agendar Publicação (opcional)</label>
                    <input type="datetime-local" id="data_publicacao_curso" name="data_publicacao_curso" class="input-style">
                </div>
                <button type="submit" class="w-full text-center px-6 py-3 rounded-lg bg-rose-600 hover:bg-rose-700 font-semibold">Cadastrar Curso</button>
            </form>
        </section>

        <section class="bg-gray-800 p-8 rounded-2xl shadow-2xl space-y-6">
            <h2 class="text-2xl font-semibold text-white">Cadastrar Novo Produto</h2>
            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="cadastrar_produto" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="w-full h-48 bg-gray-700 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-500">
                    <template x-if="!produtoImageUrl"><span class="text-gray-400">Preview da Imagem</span></template>
                    <template x-if="produtoImageUrl"><img :src="produtoImageUrl" class="w-full h-full object-cover rounded-lg" alt="Preview"></template>
                </div>
                <input type="file" name="imagem_produto" required class="input-style file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100" @change="produtoImageUrl = URL.createObjectURL($event.target.files[0])">
                <input type="text" name="nome_produto" placeholder="Nome do Produto" required class="input-style" />
                <textarea name="descricao_produto" placeholder="Descrição (opcional)" rows="3" class="input-style"></textarea>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <input type="number" step="0.01" name="preco_produto" placeholder="Preço (ex: 29.99)" required class="input-style" />
                    <input type="url" name="link_produto" placeholder="Link (opcional)" class="input-style" />
                </div>
                 <div>
                    <label for="data_publicacao_produto" class="block mb-1 text-sm font-medium text-gray-400">Agendar Publicação (opcional)</label>
                    <input type="datetime-local" id="data_publicacao_produto" name="data_publicacao_produto" class="input-style">
                </div>
                <button type="submit" class="w-full text-center px-6 py-3 rounded-lg bg-rose-600 hover:bg-rose-700 font-semibold">Cadastrar Produto</button>
            </form>
        </section>
    </div>
    
    <section class="bg-gray-800 p-8 rounded-2xl shadow-2xl">
        <h2 class="text-2xl font-semibold mb-6 text-white">Cursos Cadastrados</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="p-3">Curso</th><th class="p-3 hidden md:table-cell">Tipo</th><th class="p-3 hidden sm:table-cell">Data</th><th class="p-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                <?php foreach ($cursos as $c): ?>
                    <tr class="hover:bg-gray-700/50">
                        <td class="p-3"><div class="flex items-center gap-4"><img src="/<?php echo htmlspecialchars($c['imagem']); ?>" alt="Imagem" class="w-16 h-16 object-cover rounded-lg"><div><div class="font-bold text-white"><?php echo htmlspecialchars($c['titulo']); ?></div><div class="text-sm text-gray-400 hidden sm:block max-w-xs truncate"><?php echo htmlspecialchars($c['descricao']); ?></div></div></div></td>
                        <td class="p-3 hidden md:table-cell"><?php echo ucfirst(htmlspecialchars($c['tipo'])); ?></td>
                        <td class="p-3 hidden sm:table-cell"><?php echo date('d/m/Y', strtotime($c['data_postagem'])); ?></td>
                        <td class="p-3"><div class="flex items-center gap-4"><a href="editar_curso.php?id=<?php echo $c['id']; ?>" class="text-sky-400 hover:text-sky-300">Editar</a><button @click="modalExcluir = { tipo: 'curso', id: <?php echo $c['id']; ?>, titulo: '<?php echo htmlspecialchars(addslashes($c['titulo'])); ?>' }" class="text-rose-500 hover:text-rose-400">Excluir</button></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    
    <section class="bg-gray-800 p-8 rounded-2xl shadow-2xl">
        <h2 class="text-2xl font-semibold mb-6 text-white">Produtos Cadastrados</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="p-3">Produto</th><th class="p-3 hidden md:table-cell">Preço</th><th class="p-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                <?php foreach ($produtos as $p): ?>
                    <tr class="hover:bg-gray-700/50">
                        <td class="p-3"><div class="flex items-center gap-4"><img src="/<?php echo htmlspecialchars($p['imagem']); ?>" alt="Imagem" class="w-16 h-16 object-cover rounded-lg"><div><div class="font-bold text-white"><?php echo htmlspecialchars($p['nome']); ?></div><div class="text-sm text-gray-400 hidden sm:block max-w-xs truncate"><?php echo htmlspecialchars($p['descricao']); ?></div></div></div></td>
                        <td class="p-3 font-bold text-emerald-400 hidden md:table-cell">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></td>
                        <td class="p-3"><div class="flex items-center gap-4"><a href="editar_produto.php?id=<?php echo $p['id']; ?>" class="text-sky-400 hover:text-sky-300">Editar</a><button @click="modalExcluir = { tipo: 'produto', id: <?php echo $p['id']; ?>, titulo: '<?php echo htmlspecialchars(addslashes($p['nome'])); ?>' }" class="text-rose-500 hover:text-rose-400">Excluir</button></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<div x-show="modalExcluir" x-cloak class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div @click.away="modalExcluir=null" class="bg-gray-800 p-8 rounded-2xl max-w-sm w-full space-y-6 shadow-2xl border border-gray-700">
        <h3 class="text-xl font-bold text-white">Confirmar Exclusão</h3>
        <p class="text-gray-300">Você tem certeza que deseja excluir "<strong x-text="modalExcluir.titulo"></strong>"? Esta ação não pode ser desfeita.</p>
        <div class="flex justify-end gap-4 pt-4">
            <button @click="modalExcluir=null" class="px-4 py-2 rounded-lg bg-gray-600 hover:bg-gray-500 font-semibold">Cancelar</button>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" :name="modalExcluir.tipo === 'curso' ? 'excluir_curso' : 'excluir_produto'" :value="modalExcluir.id">
                <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 font-semibold">Sim, Excluir</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>