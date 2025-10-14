<?php
/**
 * TESTE SIMPLES DE CONEXÃO
 * HELMER ACADEMY - HOSTINGER
 * 
 * Script simples para testar a conexão com o banco
 */

// Verificar se é admin
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - HELMER ACADEMY</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #000000 0%, #1a0000 25%, #2d0000 50%, #1a0000 75%, #000000 100%);
            color: #ffffff; 
            min-height: 100vh;
        }
        .status-ok { color: #10b981; }
        .status-error { color: #ef4444; }
        .status-warning { color: #f59e0b; }
    </style>
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4">
                <i class="fas fa-plug text-red-400 mr-3"></i>
                Teste de Conexão
            </h1>
            <p class="text-gray-400">Verificação simples do sistema</p>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Teste 1: Incluir config.php -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4">1. Teste de Inclusão do config.php</h2>
                <div id="test1-result">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Testando...
                </div>
            </div>

            <!-- Teste 2: Conexão com banco -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4">2. Teste de Conexão com Banco</h2>
                <div id="test2-result">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Testando...
                </div>
            </div>

            <!-- Teste 3: Verificar tabelas -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4">3. Teste de Tabelas</h2>
                <div id="test3-result">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Testando...
                </div>
            </div>

            <!-- Teste 4: Verificar arquivos -->
            <div class="bg-gray-800 p-6 rounded-xl">
                <h2 class="text-xl font-semibold mb-4">4. Teste de Arquivos</h2>
                <div id="test3-result">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Testando...
                </div>
            </div>

            <!-- Botão de teste -->
            <div class="text-center">
                <button onclick="runAllTests()" class="bg-red-600 hover:bg-red-700 text-white px-8 py-4 rounded-xl font-bold transition-colors">
                    <i class="fas fa-play mr-2"></i>
                    Executar Todos os Testes
                </button>
            </div>
        </div>
    </div>

    <script>
        // Executar testes iniciais
        document.addEventListener('DOMContentLoaded', function() {
            runAllTests();
        });

        async function runAllTests() {
            await test1();
            await test2();
            await test3();
            await test4();
        }

        async function test1() {
            const result = document.getElementById('test1-result');
            
            try {
                const response = await fetch('?test=config');
                const data = await response.json();
                
                if (data.success) {
                    result.innerHTML = '<i class="fas fa-check-circle mr-2 status-ok"></i>✅ config.php carregado com sucesso';
                } else {
                    result.innerHTML = '<i class="fas fa-times-circle mr-2 status-error"></i>❌ Erro: ' + data.error;
                }
            } catch (error) {
                result.innerHTML = '<i class="fas fa-times-circle mr-2 status-error"></i>❌ Erro: ' + error.message;
            }
        }

        async function test2() {
            const result = document.getElementById('test2-result');
            
            try {
                const response = await fetch('?test=database');
                const data = await response.json();
                
                if (data.success) {
                    result.innerHTML = '<i class="fas fa-check-circle mr-2 status-ok"></i>✅ Conexão com banco estabelecida';
                } else {
                    result.innerHTML = '<i class="fas fa-times-circle mr-2 status-error"></i>❌ Erro: ' + data.error;
                }
            } catch (error) {
                result.innerHTML = '<i class="fas fa-times-circle mr-2 status-error"></i>❌ Erro: ' + error.message;
            }
        }

        async function test3() {
            const result = document.getElementById('test3-result');
            
            try {
                const response = await fetch('?test=tables');
                const data = await response.json();
                
                if (data.success) {
                    result.innerHTML = '<i class="fas fa-check-circle mr-2 status-ok"></i>✅ Tabelas verificadas: ' + data.count + ' encontradas';
                } else {
                    result.innerHTML = '<i class="fas fa-times-circle mr-2 status-error"></i>❌ Erro: ' + data.error;
                }
            } catch (error) {
                result.innerHTML = '<i class="fas fa-times-circle mr-2 status-error"></i>❌ Erro: ' + error.message;
            }
        }

        async function test4() {
            const result = document.getElementById('test4-result');
            
            try {
                const response = await fetch('?test=files');
                const data = await response.json();
                
                if (data.success) {
                    result.innerHTML = '<i class="fas fa-check-circle mr-2 status-ok"></i>✅ Arquivos verificados: ' + data.count + ' encontrados';
                } else {
                    result.innerHTML = '<i class="fas fa-times-circle mr-2 status-error"></i>❌ Erro: ' + data.error;
                }
            } catch (error) {
                result.innerHTML = '<i class="fas fa-times-circle mr-2 status-error"></i>❌ Erro: ' + error.message;
            }
        }
    </script>
</body>
</html>

<?php
// Processar testes
if (isset($_GET['test'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_GET['test']) {
            case 'config':
                // Teste 1: Incluir config.php
                try {
                    require 'config.php';
                    echo json_encode(['success' => true, 'message' => 'config.php carregado']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                break;
                
            case 'database':
                // Teste 2: Conexão com banco
                try {
                    require 'config.php';
                    $pdo->query("SELECT 1");
                    echo json_encode(['success' => true, 'message' => 'Conexão estabelecida']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                break;
                
            case 'tables':
                // Teste 3: Verificar tabelas
                try {
                    require 'config.php';
                    $tables = ['users', 'produtos', 'cursos', 'comentarios'];
                    $count = 0;
                    
                    foreach ($tables as $table) {
                        try {
                            $pdo->query("SELECT 1 FROM $table LIMIT 1");
                            $count++;
                        } catch (Exception $e) {
                            // Tabela não existe
                        }
                    }
                    
                    echo json_encode(['success' => true, 'count' => $count, 'total' => count($tables)]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                break;
                
            case 'files':
                // Teste 4: Verificar arquivos
                try {
                    $files = ['config.php', 'index.php', 'login.php'];
                    $count = 0;
                    
                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            $count++;
                        }
                    }
                    
                    echo json_encode(['success' => true, 'count' => $count, 'total' => count($files)]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Teste não reconhecido']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
    }
    exit;
}
?>
