<?php
session_start();
require 'config.php';

// Verificação de segurança (padronizar para $_SESSION['role'] seria ideal)
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

// Redirecionar para a versão moderna
header("Location: cursos_moderno.php");
exit();

// 1. CORREÇÃO DE SEGURANÇA: Lógica de exclusão agora espera um método POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    $idExcluir = intval($_POST['excluir_id']);
    
    // (Opcional, mas recomendado) Adicionar validação de token CSRF aqui.

    if ($idExcluir > 0) {
        // Busca imagem para deletar o arquivo
        $stmt = $pdo->prepare('SELECT imagem FROM cursos WHERE id = ?');
        $stmt->execute([$idExcluir]);
        $curso = $stmt->fetch();

        if ($curso && !empty($curso['imagem'])) {
            // MELHORIA: Usando caminho absoluto do servidor para mais robustez.
            $imagemPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($curso['imagem'], '/');
            if (file_exists($imagemPath)) {
                unlink($imagemPath);
            }
        }

        // Exclui o registro do banco de dados
        $stmt = $pdo->prepare('DELETE FROM cursos WHERE id = ?');
        $stmt->execute([$idExcluir]);

        $_SESSION['msg'] = "Curso excluído com sucesso!";
        header('Location: cursos.php');
        exit();
    }
}

// Buscar todos os cursos (MELHORIA: selecionando colunas específicas)
$cursos = $pdo->query('SELECT id, titulo, data_postagem FROM cursos ORDER BY data_postagem DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Administração - Cursos</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-900 text-white font-sans p-6">

<div x-data="{ modalOpen: false, modalTargetId: null, modalTargetTitle: '' }">
    <h1 class="text-3xl mb-6 font-bold">Gerenciar Cursos</h1>
    
    <a href="criar_curso.php" class="inline-block mb-6 bg-rose-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-rose-700 transition">Adicionar Novo Curso</a>

    <?php if (!empty($_SESSION['msg'])): ?>
    <div class="mb-4 p-3 bg-emerald-600 rounded text-center" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
        <span><?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?></span>
    </div>
    <?php endif; ?>

    <div class="overflow-x-auto bg-gray-800 rounded-lg shadow-lg">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-900/50">
                <tr>
                    <th class="p-4">ID</th>
                    <th class="p-4">Título</th>
                    <th class="p-4 hidden sm:table-cell">Data</th>
                    <th class="p-4 text-center">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php foreach ($cursos as $curso): ?>
                    <tr class="hover:bg-gray-700/50">
                        <td class="p-4 font-mono text-gray-400"><?= $curso['id'] ?></td>
                        <td class="p-4 font-semibold"><?= htmlspecialchars($curso['titulo']) ?></td>
                        <td class="p-4 hidden sm:table-cell"><?= date('d/m/Y', strtotime($curso['data_postagem'])) ?></td>
                        <td class="p-4">
                            <div class="flex items-center justify-center gap-4">
                                <a href="editar_curso.php?id=<?= $curso['id'] ?>" class="font-semibold text-sky-400 hover:text-sky-300">Editar</a>
                                <button 
                                    @click="modalOpen = true; modalTargetId = <?= $curso['id'] ?>; modalTargetTitle = '<?= htmlspecialchars(addslashes($curso['titulo'])) ?>'"
                                    class="font-semibold text-rose-500 hover:text-rose-400">
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                 <?php if (empty($cursos)): ?>
                    <tr>
                        <td colspan="4" class="text-center p-4 text-gray-500">Nenhum curso cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div 
        x-show="modalOpen" 
        x-cloak
        @keydown.escape.window="modalOpen = false"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-75 z-50"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div 
            @click.outside="modalOpen = false"
            class="bg-gray-800 p-8 rounded-2xl shadow-2xl max-w-sm w-full text-center border border-gray-700"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            <h3 class="text-xl font-bold mb-2">Confirmar Exclusão</h3>
            <p class="mb-6 text-gray-300">Tem certeza que deseja excluir o curso <strong x-text="modalTargetTitle" class="text-white"></strong>? Esta ação não pode ser desfeita.</p>
            
            <div class="flex justify-around">
                <button @click="modalOpen = false" class="bg-gray-600 px-6 py-2 rounded-lg hover:bg-gray-700 font-semibold transition">Cancelar</button>
                
                <form action="cursos.php" method="POST">
                    <input type="hidden" name="excluir_id" :value="modalTargetId">
                    <button type="submit" class="bg-red-600 px-6 py-2 rounded-lg hover:bg-red-700 font-semibold transition">Sim, Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>