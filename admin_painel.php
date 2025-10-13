<?php
// 1. CORREÇÃO CRÍTICA: session_start() deve ser a primeira coisa no script.
session_start();
require 'config.php';

// 2. CORREÇÃO CRÍTICA: Verificando a 'role' do usuário para autorização.
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php"); // Idealmente, redirecionar para login.php que pode mostrar erros
    exit();
}

// Redirecionar para a versão simples que funciona
header("Location: admin_painel_simples.php");
exit();

// 3. MELHORIA: Lógica para exibir mensagens de sucesso ou erro.
$feedbackMessage = '';
$feedbackType = 'success'; // 'success' ou 'error'

if (isset($_GET['msg']) && $_GET['msg'] === 'usuario_cadastrado') {
    $feedbackMessage = 'Novo usuário cadastrado com sucesso!';
}
if (isset($_GET['msg']) && $_GET['msg'] === 'usuario_excluido') {
    $feedbackMessage = 'Usuário excluído com sucesso.';
}
if (isset($_GET['error'])) {
    $feedbackMessage = 'Ocorreu um erro na operação.';
    $feedbackType = 'error';
}

try {
    // 4. MELHORIA: Selecionando colunas específicas em vez de SELECT *
    $stmt = $pdo->query("SELECT id, username, role FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $feedbackMessage = 'Erro ao carregar usuários.';
    $feedbackType = 'error';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Painel Admin | HELMER ACADEMY</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-gray-200 min-h-screen font-sans">
<div class="container mx-auto p-4 sm:p-6 lg:p-8">

    <header class="flex flex-col sm:flex-row justify-between items-center mb-8 pb-4 border-b border-gray-700">
        <h1 class="text-3xl font-extrabold text-white mb-4 sm:mb-0">Painel do Administrador 🔥</h1>
        <div>
            <a href="area-restrita.php" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-lg transition-transform transform hover:scale-105">Adicionar Usuário</a>
            <a href="logout.php" class="ml-4 text-sm text-gray-400 hover:text-white">Sair</a>
        </div>
    </header>

    <?php if ($feedbackMessage): ?>
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-4 rounded-lg mb-6 text-white text-center <?php echo $feedbackType === 'success' ? 'bg-emerald-500/80' : 'bg-red-500/80'; ?>" x-transition>
        <?php echo htmlspecialchars($feedbackMessage); ?>
    </div>
    <?php endif; ?>

    <div class="bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="p-4 text-sm font-semibold tracking-wide text-gray-300">ID</th>
                        <th class="p-4 text-sm font-semibold tracking-wide text-gray-300">Username</th>
                        <th class="p-4 text-sm font-semibold tracking-wide text-gray-300 hidden sm:table-cell">Role</th>
                        <th class="p-4 text-sm font-semibold tracking-wide text-gray-300 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if (count($users) > 0): ?>
                        <?php foreach($users as $user): ?>
                        <tr class="hover:bg-gray-700/50 transition-colors">
                            <td class="p-4 font-mono text-gray-400"><?php echo $user['id']; ?></td>
                            <td class="p-4 font-semibold text-white"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="p-4 hidden sm:table-cell">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-rose-500/30 text-rose-300' : 'bg-sky-500/30 text-sky-300'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                           <td class="p-4">
    <div class="flex items-center justify-center gap-4">
        <a href="editar-usuario.php?id=<?= $user['id'] ?>" class="font-semibold text-sky-400 hover:text-sky-300">Editar</a>
        
        <form action="excluir-usuario.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir?');">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] // O token deve ser gerado na página do painel ?>">
            <button type="submit" class="font-semibold text-rose-500 hover:text-rose-400 bg-transparent border-none p-0 cursor-pointer">
                Excluir
            </button>
        </form>
    </div>
</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-400">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>