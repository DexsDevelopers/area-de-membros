<?php
/**
 * SCRIPT DE ROLLBACK DO BANCO DE DADOS
 * HELMER ACADEMY - HOSTINGER
 * 
 * Este script reverte as alterações feitas no banco de dados
 * Use apenas se houver problemas após a atualização
 */

// Incluir configuração do banco
require 'config.php';

// Verificar se é admin
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

echo "<h1>🔄 Revertendo Alterações do Banco de Dados - HELMER ACADEMY</h1>";
echo "<div style='font-family: monospace; background: #1a1a1a; color: #ffaa00; padding: 20px; border-radius: 10px;'>";

try {
    echo "<h2>⚠️ ATENÇÃO: Este script irá reverter as alterações do banco!</h2>";
    echo "<p>Certifique-se de que você realmente quer fazer isso.</p>";
    
    // 1. Remover índices
    echo "<h2>🗑️ Removendo índices...</h2>";
    
    $indexes_to_drop = [
        "DROP INDEX IF EXISTS idx_produtos_status ON produtos",
        "DROP INDEX IF EXISTS idx_produtos_categoria ON produtos", 
        "DROP INDEX IF EXISTS idx_produtos_data_criacao ON produtos",
        "DROP INDEX IF EXISTS idx_users_status ON users",
        "DROP INDEX IF EXISTS idx_users_ultimo_acesso ON users",
        "DROP INDEX IF EXISTS idx_comentarios_conteudo ON comentarios",
        "DROP INDEX IF EXISTS idx_comentarios_data ON comentarios"
    ];
    
    foreach ($indexes_to_drop as $index) {
        try {
            $pdo->exec($index);
            echo "✅ Índice removido: " . substr($index, 0, 50) . "...<br>";
        } catch (PDOException $e) {
            echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
        }
    }
    
    // 2. Remover colunas adicionadas da tabela produtos
    echo "<h2>🛍️ Removendo colunas da tabela produtos...</h2>";
    
    $produtos_columns_to_drop = [
        "ALTER TABLE produtos DROP COLUMN IF EXISTS status",
        "ALTER TABLE produtos DROP COLUMN IF EXISTS data_criacao",
        "ALTER TABLE produtos DROP COLUMN IF EXISTS data_atualizacao", 
        "ALTER TABLE produtos DROP COLUMN IF EXISTS estoque",
        "ALTER TABLE produtos DROP COLUMN IF EXISTS categoria_id"
    ];
    
    foreach ($produtos_columns_to_drop as $column) {
        try {
            $pdo->exec($column);
            echo "✅ Coluna removida: " . substr($column, 0, 50) . "...<br>";
        } catch (PDOException $e) {
            echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
        }
    }
    
    // 3. Remover colunas adicionadas da tabela users
    echo "<h2>👥 Removendo colunas da tabela users...</h2>";
    
    $users_columns_to_drop = [
        "ALTER TABLE users DROP COLUMN IF EXISTS avatar",
        "ALTER TABLE users DROP COLUMN IF EXISTS ultimo_acesso",
        "ALTER TABLE users DROP COLUMN IF EXISTS status"
    ];
    
    foreach ($users_columns_to_drop as $column) {
        try {
            $pdo->exec($column);
            echo "✅ Coluna removida: " . substr($column, 0, 50) . "...<br>";
        } catch (PDOException $e) {
            echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
        }
    }
    
    // 4. Remover tabelas criadas
    echo "<h2>🗑️ Removendo tabelas criadas...</h2>";
    
    $tables_to_drop = [
        "DROP TABLE IF EXISTS favoritos",
        "DROP TABLE IF EXISTS notificacoes", 
        "DROP TABLE IF EXISTS banners",
        "DROP TABLE IF EXISTS categorias"
    ];
    
    foreach ($tables_to_drop as $table) {
        try {
            $pdo->exec($table);
            echo "✅ Tabela removida: " . substr($table, 0, 50) . "...<br>";
        } catch (PDOException $e) {
            echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
        }
    }
    
    // 5. Verificação final
    echo "<h2>🔍 Verificação final...</h2>";
    
    $tables_check = ['users', 'produtos', 'cursos', 'comentarios'];
    foreach ($tables_check as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✅ Tabela '$table' restaurada com " . count($columns) . " colunas<br>";
        } catch (PDOException $e) {
            echo "❌ Erro ao verificar '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>🎉 Rollback concluído com sucesso!</h2>";
    echo "<p><strong>Alterações revertidas:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Colunas removidas das tabelas existentes</li>";
    echo "<li>✅ Tabelas criadas removidas</li>";
    echo "<li>✅ Índices removidos</li>";
    echo "<li>✅ Banco restaurado ao estado anterior</li>";
    echo "</ul>";
    echo "<p><strong>⚠️ Importante:</strong> Delete este arquivo (rollback_database.php) após a execução por segurança!</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro durante o rollback:</h2>";
    echo "<p style='color: #ff4444;'>" . $e->getMessage() . "</p>";
}

echo "</div>";
?>
