# ===================================================
# SCRIPT DE OTIMIZAÇÃO DE IMAGENS - HELMER ACADEMY
# ===================================================

param(
    [string]$Quality = "85",
    [string]$MaxWidth = "1920",
    [string]$MaxHeight = "1080"
)

Write-Host "🖼️ Otimizando imagens automaticamente..." -ForegroundColor Green
Write-Host "Qualidade: $Quality%" -ForegroundColor Yellow
Write-Host "Dimensões máximas: ${MaxWidth}x${MaxHeight}" -ForegroundColor Yellow

try {
    # Verificar se o ImageMagick está instalado
    $magickPath = Get-Command magick -ErrorAction SilentlyContinue
    if (-not $magickPath) {
        Write-Host "⚠️ ImageMagick não encontrado. Instalando via Chocolatey..." -ForegroundColor Yellow
        
        # Tentar instalar via Chocolatey
        if (Get-Command choco -ErrorAction SilentlyContinue) {
            choco install imagemagick -y
        } else {
            Write-Host "❌ Chocolatey não encontrado. Instale o ImageMagick manualmente." -ForegroundColor Red
            exit 1
        }
    }

    # Diretórios de uploads para otimizar
    $UploadDirs = @("uploads\banners", "uploads\categorias", "uploads\cursos", "uploads\produtos")
    
    foreach ($dir in $UploadDirs) {
        if (Test-Path $dir) {
            Write-Host "🔍 Processando: $dir" -ForegroundColor Green
            
            # Encontrar imagens para otimizar
            $Images = Get-ChildItem -Path $dir -Include "*.jpg", "*.jpeg", "*.png" -Recurse
            
            foreach ($image in $Images) {
                $OriginalSize = [math]::Round($image.Length / 1KB, 2)
                
                # Criar backup da imagem original
                $BackupPath = $image.FullName + ".backup"
                if (-not (Test-Path $BackupPath)) {
                    Copy-Item $image.FullName $BackupPath
                }
                
                # Otimizar imagem
                $TempPath = $image.FullName + ".tmp"
                
                # Redimensionar e otimizar
                & magick $image.FullName -resize "${MaxWidth}x${MaxHeight}>" -quality $Quality $TempPath
                
                if (Test-Path $TempPath) {
                    $NewSize = [math]::Round((Get-Item $TempPath).Length / 1KB, 2)
                    $Savings = [math]::Round((($OriginalSize - $NewSize) / $OriginalSize) * 100, 1)
                    
                    if ($Savings -gt 0) {
                        Move-Item $TempPath $image.FullName -Force
                        Write-Host "✅ Otimizado: $($image.Name) (Economia: $Savings%)" -ForegroundColor Green
                    } else {
                        Remove-Item $TempPath -Force
                        Write-Host "ℹ️ Sem otimização necessária: $($image.Name)" -ForegroundColor Yellow
                    }
                }
            }
        }
    }

    Write-Host "✅ Otimização de imagens concluída!" -ForegroundColor Green

} catch {
    Write-Host "❌ Erro na otimização: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
