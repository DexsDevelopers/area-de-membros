<?php
session_start();
require 'config.php';

// 1. Padroniza a verificação de autorização para 'role'
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Garante que o script só aceite requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    exit('Acesso negado.');
}

// 3. Valida o token CSRF para prevenir ataques
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header("Location: produtos.php?error=csrf_fail");
    exit();
}

$id = intval($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        // 4. FUNCIONALIDADE ADICIONADA: Busca o caminho da imagem antes de deletar
        $stmt = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produto = $stmt->fetch();

        // Se o produto e a imagem existirem, apaga o arquivo da imagem
        if ($produto && !empty($produto['imagem'])) {
            $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($produto['imagem'], '/');
            if (file_exists($caminhoAbsoluto)) {
                unlink($caminhoAbsoluto);
            }
        }

        // Deleta o registro do produto no banco de dados
        $stmtDelete = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmtDelete->execute([$id]);

        // 5. Redireciona com mensagem de sucesso
        header("Location: produtos.php?msg=product_deleted");
        exit();

    } catch (PDOException $e) {
        error_log('Erro ao excluir produto: ' . $e->getMessage());
        header("Location: produtos.php?error=database_error");
        exit();
    }
}

// Redireciona se o ID for inválido
header("Location: produtos.php?error=invalid_id");
exit();
?>