<?php
session_start();
require 'config.php';

// Apenas usuários logados podem comentar
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, pode redirecionar para o login ou simplesmente morrer
    http_response_code(403);
    exit('Você precisa estar logado para comentar.');
}

// Apenas aceita requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Método não permitido.');
}

// Validação do token CSRF (se implementado)
// if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
//     exit('Falha na validação CSRF.');
// }

// Coleta e validação dos dados do formulário
$user_id = $_SESSION['user_id'];
$conteudo_id = intval($_POST['conteudo_id'] ?? 0);
$tipo_conteudo = in_array($_POST['tipo_conteudo'], ['curso', 'produto']) ? $_POST['tipo_conteudo'] : '';
$comentario = trim($_POST['comentario'] ?? '');

// Verifica se todos os dados são válidos
if ($conteudo_id > 0 && !empty($tipo_conteudo) && !empty($comentario)) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO comentarios (user_id, conteudo_id, tipo_conteudo, comentario) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $conteudo_id, $tipo_conteudo, $comentario]);
        
        // Pega o ID do comentário recém-criado para poder ancorar a página nele
        $last_id = $pdo->lastInsertId();

    } catch (PDOException $e) {
        error_log('Erro ao adicionar comentário: ' . $e->getMessage());
        // Tratar o erro, talvez redirecionando com uma mensagem
    }
}

// Redireciona de volta para a página original, ancorando no novo comentário
$redirect_url = ($tipo_conteudo === 'curso') ? 'curso_pagina.php' : 'produtos_pagina.php';
header("Location: {$redirect_url}?id={$conteudo_id}#comentario-{$last_id}");
exit();
?>