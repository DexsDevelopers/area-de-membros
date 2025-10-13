<?php
session_start();
require 'config.php';

// FALHA CORRIGIDA: Verifica se o usuário é ADMIN, não apenas se está logado.
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Lógica de feedback (para mensagens de sucesso/erro)
$feedbackMessage = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'usuario_adicionado') {
    $feedbackMessage = 'Usuário adicionado com sucesso!';
}

try {
    // Busca todos os usuários, exceto o próprio admin (opcional, mas seguro)
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ? ORDER BY id ASC");
    $stmt->execute([$_SESSION['user_id']]); // Supondo que você guarde o ID na sessão
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $feedbackMessage = 'Erro ao carregar usuários.';
}

// Lógica para adicionar usuário (movido do cadastrar.php para cá)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // (Adicionar validações de 'username' e 'password' aqui seria ideal)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
        $stmt->execute([$username, $hash]);
        
        header("Location: area-restrita.php?msg=usuario_adicionado");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gerenciar Usuários</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-b from-black via-gray-900 to-black text-gray-200 min-h-screen font-sans">
<div class="container mx-auto p-8">

    <header class="flex justify-between items-center mb-8 pb-4 border-b border-gray-700">
        <h1 class="text-3xl font-bold text-white">Gerenciar Usuários</h1>
        <a href="logout.php" class="text-sm text-gray-400 hover:text-white">Sair (<?php echo htmlspecialchars($_SESSION['user']); ?>)</a>
    </header>

    <?php if ($feedbackMessage): ?>
        <div class="bg-emerald-500/80 p-4 rounded-lg mb-6 text-white text-center"><?php echo $feedbackMessage; ?></div>
    <?php endif; ?>

    <section class="bg-gray-800/80 p-6 rounded-2xl shadow-lg mb-10">
        <h2 class="text-2xl font-semibold mb-4 text-white">Adicionar Novo Usuário</h2>
        <form action="area-restrita.php" method="POST" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label for="username" class="block text-sm font-medium text-gray-400 mb-1">Nome de Usuário</label>
                <input type="text" id="username" name="username" required class="w-full p-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500">
            </div>
            <div class="flex-1 w-full">
                <label for="password" class="block text-sm font-medium text-gray-400 mb-1">Senha</label>
                <input type="password" id="password" name="password" required class="w-full p-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-rose-500">
            </div>
            <button type="submit" name="add_user" class="w-full md:w-auto bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-6 rounded-lg transition">Adicionar</button>
        </form>
    </section>

    <section>
        <h2 class="text-2xl font-semibold mb-4 text-white">Usuários Cadastrados</h2>
        <div class="bg-gray-800/80 rounded-2xl shadow-lg overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Usuário</th>
                        <th class="p-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach($users as $user): ?>
                    <tr class="hover:bg-gray-700/50">
                        <td class="p-4 font-mono text-gray-400"><?php echo $user['id']; ?></td>
                        <td class="p-4 font-semibold"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="p-4">
                            <div class="flex items-center justify-center gap-4">
                                <a href="editar.php?id=<?php echo $user['id']; ?>" class="font-semibold text-sky-400 hover:text-sky-300">Editar</a>
                                <form action="excluir.php" method="POST" onsubmit="return confirm('Tem certeza?');">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="font-semibold text-rose-500 hover:text-rose-400 bg-transparent border-none p-0 cursor-pointer">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
</body>
</html>