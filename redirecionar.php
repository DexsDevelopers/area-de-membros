<?php
session_start();

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['user'])) {
    // Se não estiver, redireciona para o login
    header("Location: login.php");
    exit();
}

// 2. Se estiver logado, redireciona imediatamente para o site de destino
// O código 301 indica um redirecionamento permanente, o que é bom para SEO.
// Se for um redirecionamento temporário, você pode remover o 'true, 301'.
header("Location: https://helmer-mbs.site/", true, 301);
exit(); // É crucial ter um exit() após um header de redirecionamento.
?>