# ===================================================
# DEPLOY VIA GIT - HELMER ACADEMY
# ===================================================

Write-Host "📤 Iniciando deploy via GitHub..." -ForegroundColor Cyan

try {
    # 1. Garantir que é um repositório Git
    if (-not (Test-Path ".git")) {
        Write-Host "⚠️  Este diretório não é um repositório Git." -ForegroundColor Yellow
        Write-Host "Execute: git init e git remote add origin url-do-repo" -ForegroundColor Yellow
        throw "Repositório Git não encontrado."
    }

    # 2. Status antes do commit
    git status

    # 3. Adicionar todos os arquivos modificados
    git add .

    # 4. Commit com timestamp
    $CommitMsg = "🚀 Deploy automático Helmer Academy - $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')"
    git commit -m $CommitMsg

    # 5. Enviar para GitHub
    git push origin main

    Write-Host "✅ Deploy enviado para o GitHub com sucesso!" -ForegroundColor Green
    Write-Host "Deploy GitHub concluído com sucesso" -ForegroundColor Green

    Write-Host ""
    Write-Host "🌐 Aguardando Hostinger fazer o pull automático..." -ForegroundColor Cyan
    Write-Host "➡️  Repositório: https://github.com/DexsDevelopers/area-de-membros" -ForegroundColor White
    Write-Host "🚀 Deploy completo via Git!" -ForegroundColor Green

} catch {
    Write-Host "❌ Erro durante o deploy Git: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Erro no deploy Git: $($_.Exception.Message)" -ForegroundColor Red
}