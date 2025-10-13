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
    echo 'Acesso negado.';
    exit();
}

// 3. Valida o token CSRF para prevenir ataques
// O token CSRF deve ser gerado na página que contém o formulário de exclusão.
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header("Location: cursos.php?error=csrf_fail");
    exit();
}

$id = intval($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        // 4. FUNCIONALIDADE ADICIONADA: Busca o caminho da imagem antes de deletar
        $stmt = $pdo->prepare("SELECT imagem FROM cursos WHERE id = ?");
        $stmt->execute([$id]);
        $curso = $stmt->fetch();

        // Se o curso e a imagem existirem, apaga o arquivo da imagem
        if ($curso && !empty($curso['imagem'])) {
            $caminhoAbsoluto = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($curso['imagem'], '/');
            if (file_exists($caminhoAbsoluto)) {
                unlink($caminhoAbsoluto);
            }
        }

        // Deleta o registro do curso no banco de dados
        $stmtDelete = $pdo->prepare("DELETE FROM cursos WHERE id = ?");
        $stmtDelete->execute([$id]);

        // Redireciona com mensagem de sucesso
        header("Location: cursos.php?msg=curso_deleted");
        exit();

    } catch (PDOException $e) {
        // Em caso de erro, redireciona com uma mensagem genérica
        error_log('Erro ao excluir curso: ' . $e->getMessage()); // Loga o erro real
        header("Location: cursos.php?error=database_error");
        exit();
    }
}

// Redireciona se o ID for inválido
header("Location: cursos.php?error=invalid_id");
exit();
?>