<?php
/**
 * DEPLOY AUTOMÁTICO DO BANCO DE DADOS
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este script executa automaticamente quando detecta mudanças
 * e atualiza o banco de dados da Hostinger
 */

// Verificar se é uma requisição válida
if (!isset($_SERVER['HTTP_X_GITHUB_EVENT']) && !isset($_GET['auto_deploy'])) && !isset($_POST['auto_deploy'])) {
    http_response_code(403);
    die('Acesso negado');
}

// Incluir configuração do banco
require 'config.php';

// Log da execução
$log_file = 'database_deploy.log';
$log_entry = "[" . date('Y-m-d H:i:s') . "] Iniciando deploy automático do banco de dados\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

try {
    // Verificar se já foi executado recentemente (evitar execuções duplicadas)
    $last_execution_file = 'last_database_deploy.txt';
    $last_execution = file_exists($last_execution_file) ? file_get_contents($last_execution_file) : 0;
    $current_time = time();
    
    // Se executou há menos de 5 minutos, não executar novamente
    if (($current_time - $last_execution) < 300) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Deploy cancelado - execução muito recente\n", FILE_APPEND);
        die('Deploy cancelado - execução muito recente');
    }
    
    // Marcar execução atual
    file_put_contents($last_execution_file, $current_time);
    
    // Executar atualizações do banco
    $updates = [
        // Atualizar tabela produtos
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo') DEFAULT 'ativo' AFTER preco",
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status",
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao",
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS estoque INT DEFAULT 0 AFTER preco",
        "ALTER TABLE produtos ADD COLUMN IF NOT EXISTS categoria_id INT AFTER estoque",
        
        // Atualizar tabela users
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL AFTER role",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS ultimo_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER avatar",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo' AFTER ultimo_acesso",
        
        // Criar tabela categorias
        "CREATE TABLE IF NOT EXISTS categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            imagem VARCHAR(255),
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Criar tabela banners
        "CREATE TABLE IF NOT EXISTS banners (
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
        )",
        
        // Criar tabela notificações
        "CREATE TABLE IF NOT EXISTS notificacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            titulo VARCHAR(200) NOT NULL,
            mensagem TEXT NOT NULL,
            tipo ENUM('info', 'sucesso', 'aviso', 'erro') DEFAULT 'info',
            lida BOOLEAN DEFAULT FALSE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // Criar tabela favoritos
        "CREATE TABLE IF NOT EXISTS favoritos (
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
        )",
        
        // Adicionar índices
        "CREATE INDEX IF NOT EXISTS idx_produtos_status ON produtos(status)",
        "CREATE INDEX IF NOT EXISTS idx_produtos_categoria ON produtos(categoria_id)",
        "CREATE INDEX IF NOT EXISTS idx_produtos_data_criacao ON produtos(data_criacao)",
        "CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)",
        "CREATE INDEX IF NOT EXISTS idx_users_ultimo_acesso ON users(ultimo_acesso)",
        "CREATE INDEX IF NOT EXISTS idx_comentarios_conteudo ON comentarios(conteudo_id, tipo_conteudo)",
        "CREATE INDEX IF NOT EXISTS idx_comentarios_data ON comentarios(data_publicacao)"
    ];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($updates as $update) {
        try {
            $pdo->exec($update);
            $success_count++;
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ✅ Sucesso: " . substr($update, 0, 50) . "...\n", FILE_APPEND);
        } catch (PDOException $e) {
            $error_count++;
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ❌ Erro: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Inserir dados iniciais
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
            $success_count++;
        } catch (PDOException $e) {
            $error_count++;
        }
    }
    
    // Log final
    $final_log = "[" . date('Y-m-d H:i:s') . "] Deploy concluído - Sucessos: $success_count, Erros: $error_count\n";
    file_put_contents($log_file, $final_log, FILE_APPEND);
    
    // Resposta de sucesso
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Deploy automático do banco de dados concluído',
        'success_count' => $success_count,
        'error_count' => $error_count,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Log de erro
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ❌ Erro fatal: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Resposta de erro
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro durante o deploy automático',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
