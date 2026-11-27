@echo off
setlocal

set "ROOT=%~dp0"
set "TOOLS=%ROOT%.local-runtime"
set "PHP_HOME=%TOOLS%\php"
set "APP_DIR=%ROOT%"
if not exist "%APP_DIR%\artisan" set "APP_DIR=%ROOT%human-resource-app"
set "COMPOSER_PHAR=%TOOLS%\composer.phar"

where powershell.exe >nul 2>&1
if errorlevel 1 (
    echo PowerShell is required. Use Windows 10 or later.
    pause
    exit /b 1
)

if not exist "%PHP_HOME%\php.exe" (
    echo Downloading PHP 8.3...
    if not exist "%TOOLS%" mkdir "%TOOLS%"
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; $zip=Join-Path $env:TEMP 'php-8.3-nts-x64.zip'; Invoke-WebRequest -UseBasicParsing -Uri 'https://windows.php.net/downloads/releases/latest/php-8.3-nts-Win32-vs16-x64-latest.zip' -OutFile $zip; New-Item -ItemType Directory -Force -Path $env:PHP_HOME | Out-Null; Expand-Archive -LiteralPath $zip -DestinationPath $env:PHP_HOME -Force; Remove-Item $zip -Force"
    if errorlevel 1 goto failed
)

if not exist "%PHP_HOME%\php.ini" (
    >"%PHP_HOME%\php.ini" echo extension_dir="%PHP_HOME%\ext"
    >>"%PHP_HOME%\php.ini" echo extension=curl
    >>"%PHP_HOME%\php.ini" echo extension=dom
    >>"%PHP_HOME%\php.ini" echo extension=fileinfo
    >>"%PHP_HOME%\php.ini" echo extension=gd
    >>"%PHP_HOME%\php.ini" echo extension=mbstring
    >>"%PHP_HOME%\php.ini" echo extension=openssl
    >>"%PHP_HOME%\php.ini" echo extension=pdo_sqlite
    >>"%PHP_HOME%\php.ini" echo extension=sqlite3
    >>"%PHP_HOME%\php.ini" echo extension=xml
    >>"%PHP_HOME%\php.ini" echo extension=xmlreader
    >>"%PHP_HOME%\php.ini" echo extension=xmlwriter
    >>"%PHP_HOME%\php.ini" echo extension=zip
    >>"%PHP_HOME%\php.ini" echo date.timezone=Asia/Taipei
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; $p=Join-Path $env:PHP_HOME 'php.ini'; $c=@(Get-Content -LiteralPath $p); $required=@('curl','dom','fileinfo','gd','mbstring','openssl','pdo_sqlite','sqlite3','xml','xmlreader','xmlwriter','zip'); foreach ($extension in $required) { $pattern='^\s*extension\s*=\s*(php_)?' + [regex]::Escape($extension) + '(\.dll)?\s*$'; if (-not ($c | Where-Object { $_ -match $pattern })) { $c += ('extension=' + $extension) } }; Set-Content -LiteralPath $p -Value $c"
if errorlevel 1 goto failed

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; $p=Join-Path $env:PHP_HOME 'php.ini'; $tmp=Join-Path $env:PHP_HOME 'tmp'; New-Item -ItemType Directory -Force -Path $tmp | Out-Null; $tmp=$tmp -replace '\\','/'; $c=Get-Content -LiteralPath $p; $c=$c | Where-Object { $_ -notmatch '^(upload_max_filesize|post_max_size|upload_tmp_dir)\s*=' }; $c += 'upload_max_filesize=10M'; $c += 'post_max_size=12M'; $c += ('upload_tmp_dir=' + [char]34 + $tmp + [char]34); Set-Content -LiteralPath $p -Value $c"
if errorlevel 1 goto failed

"%PHP_HOME%\php.exe" -v >nul 2>&1
if errorlevel 1 (
    echo Installing the Microsoft Visual C++ 2015-2022 x64 Redistributable...
    echo Windows may ask for administrator permission.
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; $installer=Join-Path $env:TEMP 'vc_redist.x64.exe'; Invoke-WebRequest -UseBasicParsing -Uri 'https://aka.ms/vs/17/release/vc_redist.x64.exe' -OutFile $installer; $p=Start-Process -FilePath $installer -ArgumentList '/install','/quiet','/norestart' -Verb RunAs -Wait -PassThru; Remove-Item $installer -Force; if ($p.ExitCode -notin @(0,1638,3010)) { exit $p.ExitCode }"
    if errorlevel 1 goto failed
    "%PHP_HOME%\php.exe" -v >nul 2>&1
    if errorlevel 1 goto failed
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$modules=& (Join-Path $env:PHP_HOME 'php.exe') -m; $required=@('curl','dom','fileinfo','gd','mbstring','openssl','pdo_sqlite','sqlite3','xml','xmlreader','xmlwriter','zip'); $missing=$required | Where-Object { $modules -notcontains $_ }; if ($missing) { Write-Error ('Missing PHP extensions: ' + ($missing -join ', ')); exit 1 }"
if errorlevel 1 goto failed

if not exist "%COMPOSER_PHAR%" (
    echo Downloading Composer...
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; Invoke-WebRequest -UseBasicParsing -Uri 'https://getcomposer.org/composer-stable.phar' -OutFile $env:COMPOSER_PHAR"
    if errorlevel 1 goto failed
)

if not exist "%APP_DIR%\artisan" (
    echo Creating Laravel 11 project...
    "%PHP_HOME%\php.exe" "%COMPOSER_PHAR%" create-project laravel/laravel "%APP_DIR%" "^11.0" --no-interaction
    if errorlevel 1 goto failed
)

"%PHP_HOME%\php.exe" "%COMPOSER_PHAR%" --working-dir="%APP_DIR%" config audit.block-insecure true
if errorlevel 1 goto failed

if exist "%APP_DIR%\vendor\maatwebsite\excel\src\Excel.php" if exist "%APP_DIR%\vendor\phpoffice\phpword\src\PhpWord.php" goto packages_installed
(
    echo Installing Excel and Word packages...
    "%PHP_HOME%\php.exe" "%COMPOSER_PHAR%" --working-dir="%APP_DIR%" require "maatwebsite/excel:^3.1" phpoffice/phpword --no-interaction
    if errorlevel 1 goto failed
)

:packages_installed
if not exist "%APP_DIR%\.env" copy "%APP_DIR%\.env.example" "%APP_DIR%\.env" >nul
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; $p=Join-Path $env:APP_DIR '.env'; $c=Get-Content -LiteralPath $p -Raw; $c=[regex]::Replace($c,'(?m)^APP_LOCALE=.*$','APP_LOCALE=zh_TW'); $c=[regex]::Replace($c,'(?m)^APP_FALLBACK_LOCALE=.*$','APP_FALLBACK_LOCALE=zh_TW'); $c=[regex]::Replace($c,'(?m)^DB_CONNECTION=.*$','DB_CONNECTION=sqlite'); $c=[regex]::Replace($c,'(?m)^DB_DATABASE=.*$','DB_DATABASE=database/database.sqlite'); Set-Content -LiteralPath $p -Value $c -NoNewline; New-Item -ItemType File -Force -Path (Join-Path $env:APP_DIR 'database\database.sqlite') | Out-Null"
if errorlevel 1 goto failed

pushd "%APP_DIR%"
findstr /r /c:"^APP_KEY=base64:" ".env" >nul
if errorlevel 1 (
    "%PHP_HOME%\php.exe" artisan key:generate --force
    if errorlevel 1 (
        popd
        goto failed
    )
)
"%PHP_HOME%\php.exe" artisan migrate --force
if errorlevel 1 (
    popd
    goto failed
)
popd

echo Starting the local Laravel server...
start "Human Resource Database" /D "%APP_DIR%" "%PHP_HOME%\php.exe" artisan serve --host=127.0.0.1 --port=8000
timeout /t 3 /nobreak >nul
start "" "http://127.0.0.1:8000"
echo.
echo Environment setup is complete. Keep the server window open while using the app.
pause
exit /b 0

:failed
echo.
echo Setup failed. Review the error above, then run this file again.
pause
exit /b 1
