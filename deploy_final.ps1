# Deploy Final - Helmer Academy
Write-Host "🚀 Iniciando Deploy..." -ForegroundColor Green

# Limpar deploy anterior
if (Test-Path "deploy") {
    Remove-Item "deploy" -Recurse -Force
}

# Criar pasta deploy
New-Item -ItemType Directory -Path "deploy" -Force | Out-Null

# Copiar arquivos PHP
Write-Host "📋 Copiando arquivos PHP..." -ForegroundColor Yellow
Get-ChildItem -Path "*.php" | Copy-Item -Destination "deploy" -Force

# Copiar diretórios
$dirs = @("css", "js", "pages", "fotos")
foreach ($dir in $dirs) {
    if (Test-Path $dir) {
        Copy-Item $dir "deploy\$dir" -Recurse -Force
        Write-Host "✅ Copiado: $dir" -ForegroundColor Green
    }
}

# Copiar arquivos de configuração
$files = @("manifest.json", "sw.js")
foreach ($file in $files) {
    if (Test-Path $file) {
        Copy-Item $file "deploy\$file" -Force
        Write-Host "✅ Copiado: $file" -ForegroundColor Green
    }
}

# Criar diretórios necessários
$newDirs = @(
    "deploy\uploads\banners",
    "deploy\uploads\categorias",
    "deploy\uploads\cursos", 
    "deploy\uploads\produtos",
    "deploy\cache",
    "deploy\logs",
    "deploy\icons"
)

foreach ($dir in $newDirs) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
}

# Criar arquivo .htaccess simples
$htaccessContent = "RewriteEngine On`n<Files `.env`>`n    Order allow,deny`n    Deny from all`n</Files>"
$htaccessContent | Out-File -FilePath "deploy\.htaccess" -Encoding UTF8

# Criar ZIP
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$zipName = "helmer-academy-$timestamp.zip"
Write-Host "📦 Criando ZIP..." -ForegroundColor Yellow
Compress-Archive -Path "deploy\*" -DestinationPath $zipName -Force

# Estatísticas
$fileCount = (Get-ChildItem "deploy" -Recurse -File).Count
$zipSize = [math]::Round((Get-Item $zipName).Length / 1MB, 2)

Write-Host "===================================================" -ForegroundColor Blue
Write-Host "✅ DEPLOY CONCLUÍDO!" -ForegroundColor Green
Write-Host "===================================================" -ForegroundColor Blue
Write-Host "📁 Arquivos: $fileCount" -ForegroundColor Yellow
Write-Host "📦 ZIP: $zipName ($zipSize MB)" -ForegroundColor Yellow
Write-Host "🚀 Pronto para upload!" -ForegroundColor Green
