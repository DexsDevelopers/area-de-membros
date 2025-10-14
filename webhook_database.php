<?php
/**
 * WEBHOOK PARA ATUALIZAÇÃO AUTOMÁTICA DO BANCO
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este webhook é chamado automaticamente pelo GitHub
 * e executa as atualizações do banco de dados
 */

// Configurações de segurança
$webhook_secret = 'helmer_academy_2024_secret_key';
$allowed_ips = ['127.0.0.1', '::1']; // Adicione IPs permitidos se necessário

// Verificar se é uma requisição válida
if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE_256'])) {
    http_response_code(403);
    die('Acesso negado - assinatura não encontrada');
}

// Verificar IP (opcional)
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !in_array($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '', $allowed_ips)) {
    // Comentado para permitir execução de qualquer IP
    // http_response_code(403);
    // die('Acesso negado - IP não autorizado');
}

// Verificar assinatura do webhook
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'];
$expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);

if (!hash_equals($expected_signature, $signature)) {
    http_response_code(403);
    die('Acesso negado - assinatura inválida');
}

// Processar payload do GitHub
$data = json_decode($payload, true);

// Verificar se é um push para a branch main
if ($data['ref'] !== 'refs/heads/main') {
    http_response_code(200);
    die('Push não é para branch main - ignorando');
}

// Verificar se há mudanças nos arquivos de banco de dados
$changed_files = [];
foreach ($data['commits'] as $commit) {
    $changed_files = array_merge($changed_files, $commit['added'], $commit['modified']);
}

$database_files = ['auto_update_database.php', 'update_database.php', 'config.php'];
$has_database_changes = false;

foreach ($database_files as $file) {
    if (in_array($file, $changed_files)) {
        $has_database_changes = true;
        break;
    }
}

if (!$has_database_changes) {
    http_response_code(200);
    die('Nenhuma mudança relacionada ao banco de dados - ignorando');
}

// Incluir configuração do banco
require 'config.php';

// Log da execução
$log_file = 'webhook_database.log';
$log_entry = "[" . date('Y-m-d H:i:s') . "] Webhook executado - Deploy automático iniciado\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

try {
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
    $final_log = "[" . date('Y-m-d H:i:s') . "] Webhook concluído - Sucessos: $success_count, Erros: $error_count\n";
    file_put_contents($log_file, $final_log, FILE_APPEND);
    
    // Resposta de sucesso
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Webhook executado com sucesso',
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
        'message' => 'Erro durante execução do webhook',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
