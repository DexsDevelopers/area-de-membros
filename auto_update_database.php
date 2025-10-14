<?php
/**
 * ATUALIZAÇÃO AUTOMÁTICA DO BANCO DE DADOS
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este script executa automaticamente todas as melhorias do banco
 * Executa apenas uma vez e se auto-destrói após conclusão
 */

// Incluir configuração do banco
require 'config.php';

// Verificar se é admin
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

// Verificar se já foi executado
$executed_file = 'database_updated.flag';
if (file_exists($executed_file)) {
    die('✅ Banco de dados já foi atualizado anteriormente. Script não executado por segurança.');
}

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Atualização Automática - HELMER ACADEMY</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            background: linear-gradient(135deg, #000000 0%, #1a0000 25%, #2d0000 50%, #1a0000 75%, #000000 100%);
            color: #00ff00; 
            margin: 0; 
            padding: 20px; 
            min-height: 100vh;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: rgba(0,0,0,0.8); 
            padding: 30px; 
            border-radius: 15px; 
            border: 1px solid #dc2626;
            box-shadow: 0 0 30px rgba(220,38,38,0.3);
        }
        h1 { color: #dc2626; text-align: center; margin-bottom: 30px; }
        h2 { color: #ef4444; margin-top: 25px; margin-bottom: 15px; }
        .success { color: #00ff00; }
        .warning { color: #ffaa00; }
        .error { color: #ff4444; }
        .info { color: #00aaff; }
        .progress { 
            background: #1a1a1a; 
            border-radius: 10px; 
            padding: 15px; 
            margin: 10px 0; 
            border-left: 4px solid #dc2626;
        }
        .step { margin: 10px 0; padding: 10px; background: rgba(220,38,38,0.1); border-radius: 5px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🚀 ATUALIZAÇÃO AUTOMÁTICA DO BANCO DE DADOS</h1>";
echo "<div class='progress'>";
echo "<p class='info'>🔄 Iniciando processo automático de atualização...</p>";

try {
    $start_time = microtime(true);
    $updates_count = 0;
    $errors_count = 0;
    
    // 1. VERIFICAÇÃO INICIAL
    echo "<h2>📊 Verificação Inicial do Sistema</h2>";
    
    // Verificar conexão
    $pdo->query("SELECT 1");
    echo "<div class='step success'>✅ Conexão com banco de dados estabelecida</div>";
    
    // Verificar tabelas existentes
    $existing_tables = [];
    $tables_to_check = ['users', 'produtos', 'cursos', 'comentarios'];
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $existing_tables[] = $table;
            echo "<div class='step success'>✅ Tabela '$table' encontrada</div>";
        } catch (PDOException $e) {
            echo "<div class='step error'>❌ Tabela '$table' não encontrada: " . $e->getMessage() . "</div>";
            $errors_count++;
        }
    }
    
    // 2. ATUALIZAÇÃO DA TABELA PRODUTOS
    echo "<h2>🛍️ Atualizando Tabela Produtos</h2>";
    
    $produtos_updates = [
        "status" => "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo') DEFAULT 'ativo' AFTER preco",
        "data_criacao" => "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status",
        "data_atualizacao" => "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao",
        "estoque" => "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS estoque INT DEFAULT 0 AFTER preco",
        "categoria_id" => "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS categoria_id INT AFTER estoque"
    ];
    
    foreach ($produtos_updates as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "<div class='step success'>✅ Coluna '$column' adicionada à tabela produtos</div>";
            $updates_count++;
        } catch (PDOException $e) {
            echo "<div class='step warning'>⚠️ Coluna '$column' já existe ou erro: " . $e->getMessage() . "</div>";
        }
    }
    
    // 3. ATUALIZAÇÃO DA TABELA USERS
    echo "<h2>👥 Atualizando Tabela Users</h2>";
    
    $users_updates = [
        "avatar" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL AFTER role",
        "ultimo_acesso" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS ultimo_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER avatar",
        "status" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo' AFTER ultimo_acesso"
    ];
    
    foreach ($users_updates as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "<div class='step success'>✅ Coluna '$column' adicionada à tabela users</div>";
            $updates_count++;
        } catch (PDOException $e) {
            echo "<div class='step warning'>⚠️ Coluna '$column' já existe ou erro: " . $e->getMessage() . "</div>";
        }
    }
    
    // 4. CRIAÇÃO DE NOVAS TABELAS
    echo "<h2>🆕 Criando Novas Tabelas</h2>";
    
    // Tabela categorias
    $categorias_sql = "
    CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        descricao TEXT,
        imagem VARCHAR(255),
        status ENUM('ativo', 'inativo') DEFAULT 'ativo',
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    try {
        $pdo->exec($categorias_sql);
        echo "<div class='step success'>✅ Tabela 'categorias' criada/verificada</div>";
        $updates_count++;
    } catch (PDOException $e) {
        echo "<div class='step error'>❌ Erro ao criar tabela categorias: " . $e->getMessage() . "</div>";
        $errors_count++;
    }
    
    // Tabela banners
    $banners_sql = "
    CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(200) NOT NULL,
        descricao TEXT,
        imagem VARCHAR(255) NOT NULL,
        link VARCHAR(500),
        posicao ENUM('principal', 'secundario', 'lateral') DEFAULT 'principal',
        status ENUM('ativo', 'inativo') DEFAULT 'ativo',
        ordem INT DEFAULT 0,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    try {
        $pdo->exec($banners_sql);
        echo "<div class='step success'>✅ Tabela 'banners' criada/verificada</div>";
        $updates_count++;
    } catch (PDOException $e) {
        echo "<div class='step error'>❌ Erro ao criar tabela banners: " . $e->getMessage() . "</div>";
        $errors_count++;
    }
    
    // Tabela notificações
    $notificacoes_sql = "
    CREATE TABLE IF NOT EXISTS notificacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        titulo VARCHAR(200) NOT NULL,
        mensagem TEXT NOT NULL,
        tipo ENUM('info', 'sucesso', 'aviso', 'erro') DEFAULT 'info',
        lida BOOLEAN DEFAULT FALSE,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    try {
        $pdo->exec($notificacoes_sql);
        echo "<div class='step success'>✅ Tabela 'notificacoes' criada/verificada</div>";
        $updates_count++;
    } catch (PDOException $e) {
        echo "<div class='step error'>❌ Erro ao criar tabela notificacoes: " . $e->getMessage() . "</div>";
        $errors_count++;
    }
    
    // Tabela favoritos
    $favoritos_sql = "
    CREATE TABLE IF NOT EXISTS favoritos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        produto_id INT,
        curso_id INT,
        tipo ENUM('produto', 'curso') NOT NULL,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
        UNIQUE KEY unique_favorito (user_id, produto_id, curso_id, tipo)
    )";
    
    try {
        $pdo->exec($favoritos_sql);
        echo "<div class='step success'>✅ Tabela 'favoritos' criada/verificada</div>";
        $updates_count++;
    } catch (PDOException $e) {
        echo "<div class='step error'>❌ Erro ao criar tabela favoritos: " . $e->getMessage() . "</div>";
        $errors_count++;
    }
    
    // 5. ADIÇÃO DE ÍNDICES PARA PERFORMANCE
    echo "<h2>⚡ Adicionando Índices para Performance</h2>";
    
    $indexes = [
        "idx_produtos_status" => "CREATE INDEX IF NOT EXISTS idx_produtos_status ON produtos(status)",
        "idx_produtos_categoria" => "CREATE INDEX IF NOT EXISTS idx_produtos_categoria ON produtos(categoria_id)",
        "idx_produtos_data_criacao" => "CREATE INDEX IF NOT EXISTS idx_produtos_data_criacao ON produtos(data_criacao)",
        "idx_users_status" => "CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)",
        "idx_users_ultimo_acesso" => "CREATE INDEX IF NOT EXISTS idx_users_ultimo_acesso ON users(ultimo_acesso)",
        "idx_comentarios_conteudo" => "CREATE INDEX IF NOT EXISTS idx_comentarios_conteudo ON comentarios(conteudo_id, tipo_conteudo)",
        "idx_comentarios_data" => "CREATE INDEX IF NOT EXISTS idx_comentarios_data ON comentarios(data_publicacao)"
    ];
    
    foreach ($indexes as $index_name => $sql) {
        try {
            $pdo->exec($sql);
            echo "<div class='step success'>✅ Índice '$index_name' criado</div>";
            $updates_count++;
        } catch (PDOException $e) {
            echo "<div class='step warning'>⚠️ Índice '$index_name' já existe ou erro: " . $e->getMessage() . "</div>";
        }
    }
    
    // 6. INSERÇÃO DE DADOS INICIAIS
    echo "<h2>🌱 Inserindo Dados Iniciais</h2>";
    
    $categorias_iniciais = [
        ['Cursos', 'Cursos online da Helmer Academy', 'ativo'],
        ['Produtos Digitais', 'Produtos digitais exclusivos', 'ativo'],
        ['Ferramentas', 'Ferramentas e softwares', 'ativo'],
        ['Templates', 'Templates e modelos', 'ativo']
    ];
    
    foreach ($categorias_iniciais as $categoria) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO categorias (nome, descricao, status) VALUES (?, ?, ?)");
            $stmt->execute($categoria);
            echo "<div class='step success'>✅ Categoria inserida: " . $categoria[0] . "</div>";
            $updates_count++;
        } catch (PDOException $e) {
            echo "<div class='step warning'>⚠️ Categoria já existe ou erro: " . $e->getMessage() . "</div>";
        }
    }
    
    // 7. VERIFICAÇÃO FINAL
    echo "<h2>🔍 Verificação Final do Sistema</h2>";
    
    $tables_final = ['users', 'produtos', 'cursos', 'comentarios', 'categorias', 'banners', 'notificacoes', 'favoritos'];
    $total_records = 0;
    
    foreach ($tables_final as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $total_records += $count;
            echo "<div class='step success'>✅ Tabela '$table': $count registros</div>";
        } catch (PDOException $e) {
            echo "<div class='step error'>❌ Erro ao verificar '$table': " . $e->getMessage() . "</div>";
            $errors_count++;
        }
    }
    
    // 8. ESTATÍSTICAS FINAIS
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);
    
    echo "<h2>📊 Estatísticas da Atualização</h2>";
    echo "<div class='step info'>⏱️ Tempo de execução: {$execution_time} segundos</div>";
    echo "<div class='step success'>✅ Operações realizadas: $updates_count</div>";
    echo "<div class='step warning'>⚠️ Avisos/Erros: $errors_count</div>";
    echo "<div class='step info'>📊 Total de registros no banco: $total_records</div>";
    
    // 9. MARCAR COMO EXECUTADO E AUTO-DESTRUIR
    file_put_contents($executed_file, date('Y-m-d H:i:s'));
    
    echo "<h2>🎉 Atualização Concluída com Sucesso!</h2>";
    echo "<div class='step success'>✅ Banco de dados atualizado automaticamente</div>";
    echo "<div class='step success'>✅ Novas tabelas criadas</div>";
    echo "<div class='step success'>✅ Índices adicionados</div>";
    echo "<div class='step success'>✅ Dados iniciais inseridos</div>";
    echo "<div class='step success'>✅ Script marcado como executado</div>";
    
    echo "<h2>🔄 Auto-Destruição do Script</h2>";
    echo "<div class='step warning'>⚠️ Este script será removido automaticamente em 10 segundos...</div>";
    
    // Auto-destruir o script após 10 segundos
    echo "<script>
        setTimeout(function() {
            // Tentar remover o arquivo
            fetch('?action=delete_script', {method: 'POST'})
            .then(() => {
                document.body.innerHTML = '<div style=\"text-align: center; padding: 50px; color: #00ff00;\"><h1>✅ Script Removido com Sucesso!</h1><p>Atualização concluída. Você pode fechar esta página.</p></div>';
            })
            .catch(() => {
                document.body.innerHTML = '<div style=\"text-align: center; padding: 50px; color: #ffaa00;\"><h1>⚠️ Atualização Concluída!</h1><p>Por favor, delete manualmente o arquivo auto_update_database.php</p></div>';
            });
        }, 10000);
    </script>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro Durante a Atualização</h2>";
    echo "<div class='step error'>Erro: " . $e->getMessage() . "</div>";
    echo "<div class='step warning'>⚠️ Verifique as configurações do banco de dados</div>";
}

// Processar auto-destruição se solicitado
if (isset($_GET['action']) && $_GET['action'] === 'delete_script') {
    unlink(__FILE__);
    exit('Script removido com sucesso');
}

echo "</div></div></body></html>";
?>
