-- ===================================================
-- SCRIPT DE ATUALIZAÇÃO DO BANCO DE DADOS
-- HELMER ACADEMY - HOSTINGER
-- ===================================================

-- 1. VERIFICAR ESTRUTURA ATUAL DAS TABELAS
-- ===================================================

-- Verificar estrutura da tabela 'users'
DESCRIBE users;

-- Verificar estrutura da tabela 'produtos'
DESCRIBE produtos;

-- Verificar estrutura da tabela 'cursos'
DESCRIBE cursos;

-- Verificar estrutura da tabela 'comentarios'
DESCRIBE comentarios;

-- 2. MELHORIAS SUGERIDAS PARA A TABELA 'produtos'
-- ===================================================

-- Adicionar coluna de status se não existir
ALTER TABLE produtos 
ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo') DEFAULT 'ativo' AFTER preco;

-- Adicionar coluna de data de criação se não existir
ALTER TABLE produtos 
ADD COLUMN IF NOT EXISTS data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status;

-- Adicionar coluna de data de atualização se não existir
ALTER TABLE produtos 
ADD COLUMN IF NOT EXISTS data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao;

-- Adicionar coluna de estoque se não existir
ALTER TABLE produtos 
ADD COLUMN IF NOT EXISTS estoque INT DEFAULT 0 AFTER preco;

-- Adicionar coluna de categoria se não existir
ALTER TABLE produtos 
ADD COLUMN IF NOT EXISTS categoria_id INT AFTER estoque;

-- 3. MELHORIAS SUGERIDAS PARA A TABELA 'users'
-- ===================================================

-- Adicionar coluna de avatar se não existir
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL AFTER role;

-- Adicionar coluna de data de último acesso se não existir
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS ultimo_acesso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER avatar;

-- Adicionar coluna de status do usuário se não existir
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo' AFTER ultimo_acesso;

-- 4. CRIAR TABELA DE CATEGORIAS SE NÃO EXISTIR
-- ===================================================

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    imagem VARCHAR(255),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. CRIAR TABELA DE BANNERS SE NÃO EXISTIR
-- ===================================================

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
);

-- 6. CRIAR TABELA DE NOTIFICAÇÕES SE NÃO EXISTIR
-- ===================================================

CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('info', 'sucesso', 'aviso', 'erro') DEFAULT 'info',
    lida BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. CRIAR TABELA DE FAVORITOS SE NÃO EXISTIR
-- ===================================================

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
);

-- 8. ADICIONAR ÍNDICES PARA PERFORMANCE
-- ===================================================

-- Índices para tabela produtos
CREATE INDEX IF NOT EXISTS idx_produtos_status ON produtos(status);
CREATE INDEX IF NOT EXISTS idx_produtos_categoria ON produtos(categoria_id);
CREATE INDEX IF NOT EXISTS idx_produtos_data_criacao ON produtos(data_criacao);

-- Índices para tabela users
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_ultimo_acesso ON users(ultimo_acesso);

-- Índices para tabela comentarios
CREATE INDEX IF NOT EXISTS idx_comentarios_conteudo ON comentarios(conteudo_id, tipo_conteudo);
CREATE INDEX IF NOT EXISTS idx_comentarios_data ON comentarios(data_publicacao);

-- 9. INSERIR DADOS INICIAIS SE NECESSÁRIO
-- ===================================================

-- Inserir categorias padrão
INSERT IGNORE INTO categorias (nome, descricao, status) VALUES
('Cursos', 'Cursos online da Helmer Academy', 'ativo'),
('Produtos Digitais', 'Produtos digitais exclusivos', 'ativo'),
('Ferramentas', 'Ferramentas e softwares', 'ativo'),
('Templates', 'Templates e modelos', 'ativo');

-- 10. VERIFICAR RESULTADOS
-- ===================================================

-- Verificar se as alterações foram aplicadas
SELECT 'Verificação das tabelas atualizadas:' as status;

-- Verificar estrutura final da tabela produtos
DESCRIBE produtos;

-- Verificar estrutura final da tabela users
DESCRIBE users;

-- Verificar se as novas tabelas foram criadas
SHOW TABLES;

-- ===================================================
-- FIM DO SCRIPT DE ATUALIZAÇÃO
-- ===================================================
