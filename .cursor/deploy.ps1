# ===================================================
# SCRIPT DE DEPLOY AUTOMÁTICO - HELMER ACADEMY
# PowerShell para automação de deploy
# ===================================================

param(
    [string]$Environment = "production",
    [string]$Target = "hostinger"
)

# Configurações
$ProjectName = "helmer-academy"
$DeployDir = "deploy"
$BackupDir = "backups"
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$StartTime = Get-Date

# Cores para output
$Red = "Red"
$Green = "Green"
$Yellow = "Yellow"
$Blue = "Blue"
$White = "White"
$Cyan = "Cyan"

# Log de deploy
$LogFile = "deploy-log-$Timestamp.txt"
$LogContent = @()

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $LogEntry = "[$(Get-Date -Format 'HH:mm:ss')] [$Level] $Message"
    $LogContent += $LogEntry
    Write-Host $LogEntry -ForegroundColor $(if($Level -eq "ERROR") { $Red } elseif($Level -eq "SUCCESS") { $Green } elseif($Level -eq "WARNING") { $Yellow } else { $White })
}

function Save-Log {
    $LogContent | Out-File -FilePath $LogFile -Encoding UTF8
    Write-Log "Log salvo em: $LogFile" "INFO"
}

Write-Host "===================================================" -ForegroundColor $Blue
Write-Host "🚀 DEPLOY AUTOMÁTICO - HELMER ACADEMY" -ForegroundColor $Blue
Write-Host "===================================================" -ForegroundColor $Blue
Write-Log "Iniciando deploy automático" "INFO"
Write-Log "Ambiente: $Environment" "INFO"
Write-Log "Target: $Target" "INFO"
Write-Log "Timestamp: $Timestamp" "INFO"
Write-Log "Hora de início: $($StartTime.ToString('dd/MM/yyyy HH:mm:ss'))" "INFO"

try {
    # 1. Verificar se estamos no diretório correto
    Write-Log "Verificando diretório do projeto" "INFO"
    if (-not (Test-Path "index.php")) {
        Write-Log "ERRO: Execute este script no diretório raiz do projeto" "ERROR"
        throw "Execute este script no diretório raiz do projeto"
    }
    Write-Log "Diretório do projeto verificado com sucesso" "SUCCESS"

    # 2. Criar backup
    $BackupStartTime = Get-Date
    Write-Log "Iniciando processo de backup" "INFO"
    if (Test-Path $BackupDir) {
        Remove-Item $BackupDir -Recurse -Force
        Write-Log "Diretório de backup anterior removido" "INFO"
    }
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
    Write-Log "Diretório de backup criado" "SUCCESS"
    
    # Backup de arquivos importantes
    $BackupFiles = 0
    if (Test-Path "uploads") {
        Copy-Item "uploads" "$BackupDir\uploads" -Recurse -Force
        $BackupFiles++
        Write-Log "Pasta uploads copiada para backup" "SUCCESS"
    }
    if (Test-Path "cache") {
        Copy-Item "cache" "$BackupDir\cache" -Recurse -Force
        $BackupFiles++
        Write-Log "Pasta cache copiada para backup" "SUCCESS"
    }
    if (Test-Path "config.php") {
        Copy-Item "config.php" "$BackupDir\config.php" -Force
        $BackupFiles++
        Write-Log "Arquivo config.php copiado para backup" "SUCCESS"
    }
    
    $BackupTime = (Get-Date) - $BackupStartTime
    Write-Log "Backup concluído em $($BackupTime.TotalSeconds.ToString('F2')) segundos - $BackupFiles arquivos" "SUCCESS"

    # 3. Preparar diretório de deploy
    Write-Host "🔧 Preparando deploy..." -ForegroundColor $Green
    if (Test-Path $DeployDir) {
        Remove-Item $DeployDir -Recurse -Force
    }
    New-Item -ItemType Directory -Path $DeployDir -Force | Out-Null

    # 4. Copiar arquivos PHP
    Write-Host "📋 Copiando arquivos PHP..." -ForegroundColor $Green
    Get-ChildItem -Path "*.php" | Copy-Item -Destination $DeployDir -Force

    # 5. Copiar diretórios necessários
    $Directories = @("css", "js", "pages", "fotos")
    foreach ($dir in $Directories) {
        if (Test-Path $dir) {
            Copy-Item $dir "$DeployDir\$dir" -Recurse -Force
            Write-Host "✅ Copiado: $dir" -ForegroundColor $Green
        }
    }

    # 6. Copiar arquivos de configuração
    $ConfigFiles = @("manifest.json", "sw.js", ".htaccess")
    foreach ($file in $ConfigFiles) {
        if (Test-Path $file) {
            Copy-Item $file "$DeployDir\$file" -Force
            Write-Host "✅ Copiado: $file" -ForegroundColor $Green
        }
    }

    # 7. Criar diretórios necessários
    Write-Host "📁 Criando diretórios..." -ForegroundColor $Green
    $RequiredDirs = @(
        "$DeployDir\uploads\banners",
        "$DeployDir\uploads\categorias", 
        "$DeployDir\uploads\cursos",
        "$DeployDir\uploads\produtos",
        "$DeployDir\cache",
        "$DeployDir\logs",
        "$DeployDir\icons"
    )
    
    foreach ($dir in $RequiredDirs) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }

    # 8. Configurar para produção
    if (Test-Path "config_production.php") {
        Copy-Item "config_production.php" "$DeployDir\config.php" -Force
        Write-Host "✅ Configuração de produção aplicada" -ForegroundColor $Green
    }

    # 9. Criar arquivo de versão
    $VersionContent = @"
Helmer Academy - Deploy Automático
Versão: 1.0.0
Data: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')
Build: $Timestamp
Ambiente: $Environment
Target: $Target
"@
    $VersionContent | Out-File -FilePath "$DeployDir\version.txt" -Encoding UTF8

    # 10. Criar .htaccess otimizado
    $HtaccessContent = @"
# Helmer Academy - Configurações Apache
RewriteEngine On

# Segurança
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

# Cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

# Compressão
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript
</IfModule>
"@
    $HtaccessContent | Out-File -FilePath "$DeployDir\.htaccess" -Encoding UTF8

    # 11. Criar script de instalação
    $InstallContent = @"
<?php
/**
 * Script de Instalação - Helmer Academy
 * Deploy Automático - $Timestamp
 */
echo "<h1>🚀 Helmer Academy - Instalação Automática</h1>";
echo "<p>Verificando configurações...</p>";

// Verificar permissões
$dirs = ['uploads', 'cache', 'logs'];
foreach ($dirs as $dir) {
    if (!is_writable($dir)) {
        echo "<p style='color: red;'>❌ Diretório $dir não tem permissão de escrita</p>";
    } else {
        echo "<p style='color: green;'>✅ Diretório $dir OK</p>";
    }
}

// Verificar extensões PHP
$required_extensions = ['pdo', 'pdo_mysql', 'gd', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ Extensão $ext OK</p>";
    } else {
        echo "<p style='color: red;'>❌ Extensão $ext não encontrada</p>";
    }
}

echo "<p><strong>Deploy automático concluído!</strong></p>";
echo "<p><a href='index.php'>Acessar aplicação</a></p>";
?>
"@
    $InstallContent | Out-File -FilePath "$DeployDir\install.php" -Encoding UTF8

    # 12. Criar health check
    $HealthContent = @"
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'build' => '$Timestamp',
    'environment' => '$Environment',
    'checks' => []
];

// Verificar banco de dados
try {
    require_once 'config.php';
    $health['checks']['database'] = 'ok';
} catch (Exception $e) {
    $health['status'] = 'error';
    $health['checks']['database'] = 'error: ' . $e->getMessage();
}

// Verificar diretórios
$dirs = ['uploads', 'cache', 'logs'];
foreach ($dirs as $dir) {
    if (is_writable($dir)) {
        $health['checks']["dir_$dir"] = 'ok';
    } else {
        $health['status'] = 'error';
        $health['checks']["dir_$dir"] = 'error: not writable';
    }
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
"@
    $HealthContent | Out-File -FilePath "$DeployDir\health_check.php" -Encoding UTF8

    # 13. Criar ZIP para upload
    Write-Host "📦 Criando arquivo ZIP..." -ForegroundColor $Green
    $ZipPath = "helmer-academy-deploy-$Timestamp.zip"
    Compress-Archive -Path "$DeployDir\*" -DestinationPath $ZipPath -Force

    # 14. Estatísticas finais
    $EndTime = Get-Date
    $TotalTime = $EndTime - $StartTime
    $FileCount = (Get-ChildItem $DeployDir -Recurse -File).Count
    $ZipSize = [math]::Round((Get-Item $ZipPath).Length / 1MB, 2)

    Write-Host "===================================================" -ForegroundColor $Blue
    Write-Host "✅ DEPLOY AUTOMÁTICO CONCLUÍDO COM SUCESSO!" -ForegroundColor $Green
    Write-Host "===================================================" -ForegroundColor $Blue
    
    # Log de estatísticas
    Write-Log "DEPLOY FINALIZADO COM SUCESSO" "SUCCESS"
    Write-Log "Tempo total de execução: $($TotalTime.TotalSeconds.ToString('F2')) segundos" "SUCCESS"
    Write-Log "Arquivos processados: $FileCount" "SUCCESS"
    Write-Log "Tamanho do ZIP: $ZipSize MB" "SUCCESS"
    Write-Log "Timestamp: $Timestamp" "SUCCESS"
    Write-Log "Target: $Target" "SUCCESS"
    Write-Log "Ambiente: $Environment" "SUCCESS"
    Write-Log "Hora de início: $($StartTime.ToString('dd/MM/yyyy HH:mm:ss'))" "SUCCESS"
    Write-Log "Hora de conclusão: $($EndTime.ToString('dd/MM/yyyy HH:mm:ss'))" "SUCCESS"
    
    # Salvar log
    Save-Log
    
    Write-Host "📊 ESTATÍSTICAS DO DEPLOY:" -ForegroundColor $Cyan
    Write-Host "⏱️  Tempo total: $($TotalTime.TotalSeconds.ToString('F2')) segundos" -ForegroundColor $Yellow
    Write-Host "📁 Arquivos no deploy: $FileCount" -ForegroundColor $Yellow
    Write-Host "📦 Tamanho do ZIP: $ZipSize MB" -ForegroundColor $Yellow
    Write-Host "⏰ Timestamp: $Timestamp" -ForegroundColor $Yellow
    Write-Host "🎯 Target: $Target" -ForegroundColor $Yellow
    Write-Host "🌍 Ambiente: $Environment" -ForegroundColor $Yellow
    Write-Host ""
    Write-Host "📋 PRÓXIMOS PASSOS:" -ForegroundColor $Blue
    Write-Host "1. Faça upload do arquivo: $ZipPath" -ForegroundColor $White
    Write-Host "2. Extraia no diretório public_html do servidor" -ForegroundColor $White
    Write-Host "3. Configure o banco de dados" -ForegroundColor $White
    Write-Host "4. Teste a aplicação" -ForegroundColor $White
    Write-Host ""
    Write-Host "📄 Log detalhado salvo em: $LogFile" -ForegroundColor $Cyan
    Write-Host "🚀 Deploy automático finalizado com sucesso!" -ForegroundColor $Green

} catch {
    $EndTime = Get-Date
    $TotalTime = $EndTime - $StartTime
    
    Write-Log "ERRO NO DEPLOY AUTOMÁTICO" "ERROR"
    Write-Log "Erro: $($_.Exception.Message)" "ERROR"
    Write-Log "Tempo até o erro: $($TotalTime.TotalSeconds.ToString('F2')) segundos" "ERROR"
    Write-Log "Hora do erro: $($EndTime.ToString('dd/MM/yyyy HH:mm:ss'))" "ERROR"
    
    # Salvar log mesmo com erro
    Save-Log
    
    Write-Host "❌ ERRO NO DEPLOY AUTOMÁTICO:" -ForegroundColor $Red
    Write-Host "Erro: $($_.Exception.Message)" -ForegroundColor $Red
    Write-Host "Tempo até o erro: $($TotalTime.TotalSeconds.ToString('F2')) segundos" -ForegroundColor $Red
    Write-Host "Log de erro salvo em: $LogFile" -ForegroundColor $Red
    exit 1
}