<?php
/**
 * SCRIPT DE ATUALIZAÇÃO AUTOMÁTICA DO BANCO DE DADOS
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este script executa as atualizações do banco de dados automaticamente
 * Execute apenas uma vez após fazer deploy
 */

// Incluir configuração do banco
require 'config.php';

// Verificar se é admin
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

echo "<h1>🔄 Atualizando Banco de Dados - HELMER ACADEMY</h1>";
echo "<div style='font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; border-radius: 10px;'>";

try {
    // 1. Verificar estrutura atual
    echo "<h2>📊 Verificando estrutura atual...</h2>";
    
    $tables = ['users', 'produtos', 'cursos', 'comentarios'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✅ Tabela '$table' encontrada com " . count($columns) . " colunas<br>";
        } catch (PDOException $e) {
            echo "❌ Tabela '$table' não encontrada: " . $e->getMessage() . "<br>";
        }
    }
    
    // 2. Atualizar tabela produtos
    echo "<h2>🛍️ Atualizando tabela produtos...</h2>";
    
    $produtos_updates = [
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo') DEFAULT 'ativo' AFTER preco",
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status",
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao",
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS estoque INT DEFAULT 0 AFTER preco",
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS categoria_id INT AFTER estoque"
    ];
    
    foreach ($produtos_updates as $update) {
        try {
            $pdo->exec($update);
            echo "✅ Comando executado: " . substr($update, 0, 50) . "...<br>";
        } catch (PDOException $e) {
            echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
        }
    }
    
    // 3. Atualizar tabela users
    echo "<h2>👥 Atualizando tabela users...</h2>";
    
    $users_updates = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL AFTER role",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS ultimo_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER avatar",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo' AFTER ultimo_acesso"
    ];
    
    foreach ($users_updates as $update) {
        try {
            $pdo->exec($update);
            echo "✅ Comando executado: " . substr($update, 0, 50) . "...<br>";
        } catch (PDOException $e) {
            echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
        }
    }
    
    // 4. Criar tabela categorias
    echo "<h2>📂 Criando tabela categorias...</h2>";
    
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
        echo "✅ Tabela 'categorias' criada/verificada<br>";
    } catch (PDOException $e) {
        echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
    }
    
    // 5. Criar tabela banners
    echo "<h2>🖼️ Criando tabela banners...</h2>";
    
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
        echo "✅ Tabela 'banners' criada/verificada<br>";
    } catch (PDOException $e) {
        echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
    }
    
    // 6. Criar tabela notificações
    echo "<h2>🔔 Criando tabela notificações...</h2>";
    
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
        echo "✅ Tabela 'notificacoes' criada/verificada<br>";
    } catch (PDOException $e) {
        echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
    }
    
    // 7. Criar tabela favoritos
    echo "<h2>❤️ Criando tabela favoritos...</h2>";
    
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
        echo "✅ Tabela 'favoritos' criada/verificada<br>";
    } catch (PDOException $e) {
        echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
    }
    
    // 8. Adicionar índices para performance
    echo "<h2>⚡ Adicionando índices para performance...</h2>";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_produtos_status ON produtos(status)",
        "CREATE INDEX IF NOT EXISTS idx_produtos_categoria ON produtos(categoria_id)",
        "CREATE INDEX IF NOT EXISTS idx_produtos_data_criacao ON produtos(data_criacao)",
        "CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)",
        "CREATE INDEX IF NOT EXISTS idx_users_ultimo_acesso ON users(ultimo_acesso)",
        "CREATE INDEX IF NOT EXISTS idx_comentarios_conteudo ON comentarios(conteudo_id, tipo_conteudo)",
        "CREATE INDEX IF NOT EXISTS idx_comentarios_data ON comentarios(data_publicacao)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
            echo "✅ Índice criado: " . substr($index, 0, 50) . "...<br>";
        } catch (PDOException $e) {
            echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
        }
    }
    
    // 9. Inserir dados iniciais
    echo "<h2>🌱 Inserindo dados iniciais...</h2>";
    
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
            echo "✅ Categoria inserida: " . $categoria[0] . "<br>";
        } catch (PDOException $e) {
            echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
        }
    }
    
    // 10. Verificação final
    echo "<h2>🔍 Verificação final...</h2>";
    
    $tables_final = ['users', 'produtos', 'cursos', 'comentarios', 'categorias', 'banners', 'notificacoes', 'favoritos'];
    foreach ($tables_final as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "✅ Tabela '$table': $count registros<br>";
        } catch (PDOException $e) {
            echo "❌ Erro ao verificar '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>🎉 Atualização concluída com sucesso!</h2>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Banco de dados atualizado</li>";
    echo "<li>✅ Novas tabelas criadas</li>";
    echo "<li>✅ Índices adicionados</li>";
    echo "<li>✅ Dados iniciais inseridos</li>";
    echo "</ul>";
    echo "<p><strong>⚠️ Importante:</strong> Delete este arquivo (update_database.php) após a execução por segurança!</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro durante a atualização:</h2>";
    echo "<p style='color: #ff4444;'>" . $e->getMessage() . "</p>";
}

echo "</div>";
?>
