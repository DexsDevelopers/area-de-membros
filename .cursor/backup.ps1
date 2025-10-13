# ===================================================
# SCRIPT DE BACKUP AUTOMÁTICO - HELMER ACADEMY
# ===================================================

param(
    [string]$BackupType = "full"
)

$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupDir = "backups\backup_$Timestamp"
$ProjectName = "helmer-academy"

Write-Host "📦 Criando backup automático..." -ForegroundColor Green
Write-Host "Tipo: $BackupType" -ForegroundColor Yellow
Write-Host "Timestamp: $Timestamp" -ForegroundColor Yellow

try {
    # Criar diretório de backup
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null

    # Backup de arquivos PHP
    Write-Host "📋 Fazendo backup dos arquivos PHP..." -ForegroundColor Green
    Get-ChildItem -Path "*.php" | Copy-Item -Destination $BackupDir -Force

    # Backup de diretórios importantes
    $ImportantDirs = @("uploads", "cache", "css", "js", "pages", "fotos")
    foreach ($dir in $ImportantDirs) {
        if (Test-Path $dir) {
            Copy-Item $dir "$BackupDir\$dir" -Recurse -Force
            Write-Host "✅ Backup: $dir" -ForegroundColor Green
        }
    }

    # Backup de arquivos de configuração
    $ConfigFiles = @("config.php", "manifest.json", "sw.js", ".htaccess")
    foreach ($file in $ConfigFiles) {
        if (Test-Path $file) {
            Copy-Item $file $BackupDir -Force
            Write-Host "✅ Backup: $file" -ForegroundColor Green
        }
    }

    # Criar arquivo de informações do backup
    $BackupInfo = @"
# Backup Automático - Helmer Academy
Data: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')
Tipo: $BackupType
Timestamp: $Timestamp
Arquivos: $((Get-ChildItem $BackupDir -Recurse -File).Count)
Tamanho: $([math]::Round((Get-ChildItem $BackupDir -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB, 2)) MB
"@
    $BackupInfo | Out-File -FilePath "$BackupDir\backup_info.txt" -Encoding UTF8

    Write-Host "✅ Backup criado com sucesso em: $BackupDir" -ForegroundColor Green

} catch {
    Write-Host "❌ Erro ao criar backup: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
