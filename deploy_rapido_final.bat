@echo off
echo ===================================================
echo 🚀 DEPLOY RÁPIDO - HELMER ACADEMY
echo ===================================================

REM Limpar deploy anterior
if exist "deploy" rmdir /s /q "deploy"
mkdir "deploy"

echo 📋 Copiando arquivos...

REM Copiar arquivos PHP
copy "*.php" "deploy\" /q

REM Copiar diretórios
xcopy "css" "deploy\css\" /e /i /q
xcopy "js" "deploy\js\" /e /i /q
xcopy "pages" "deploy\pages\" /e /i /q
xcopy "fotos" "deploy\fotos\" /e /i /q

REM Copiar arquivos de configuração
copy "manifest.json" "deploy\" /q
copy "sw.js" "deploy\" /q

REM Criar diretórios necessários
mkdir "deploy\uploads\banners"
mkdir "deploy\uploads\categorias"
mkdir "deploy\uploads\cursos"
mkdir "deploy\uploads\produtos"
mkdir "deploy\cache"
mkdir "deploy\logs"
mkdir "deploy\icons"

REM Usar config de produção
if exist "config_production.php" (
    copy "config_production.php" "deploy\config.php" /q
    echo ✅ Configuração de produção aplicada
) else (
    echo ⚠️ Usando config.php padrão
)

REM Criar .htaccess
echo # Helmer Academy > "deploy\.htaccess"
echo RewriteEngine On >> "deploy\.htaccess"
echo ^<Files ".env"^> >> "deploy\.htaccess"
echo     Order allow,deny >> "deploy\.htaccess"
echo     Deny from all >> "deploy\.htaccess"
echo ^</Files^> >> "deploy\.htaccess"

REM Criar ZIP
echo 📦 Criando arquivo ZIP...
powershell -Command "Compress-Archive -Path 'deploy\*' -DestinationPath 'helmer-academy-deploy.zip' -Force"

echo ✅ Deploy concluído!
echo 📁 Pasta deploy pronta
echo 📦 Arquivo ZIP criado: helmer-academy-deploy.zip
echo.
echo 🚀 Próximos passos:
echo 1. Faça upload do ZIP para a Hostinger
echo 2. Extraia no diretório public_html
echo 3. Configure o banco de dados
echo.
pause
