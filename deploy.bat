@echo off
setlocal

set "REPO_URL=https://github.com/dewecorp/perpustakaan.git"
set "BACKUP_NAME=perpustakaan_backup.zip"

echo ==========================================
echo      AUTO DEPLOY & BACKUP SCRIPT
echo ==========================================

:: 1. GIT CONFIGURATION
if not exist .git (
    echo [INFO] Inisialisasi Git repository...
    git init
    git branch -M main
    git remote add origin %REPO_URL%
) else (
    echo [INFO] Git repository ditemukan.
    git remote set-url origin %REPO_URL%
)

:: 2. INPUT COMMIT MESSAGE
:input_msg
echo.
set /p "commit_msg=Masukkan pesan commit: "
if "%commit_msg%"=="" goto input_msg

echo.
echo ------------------------------------------
echo Pesan Commit: "%commit_msg%"
echo ------------------------------------------
set /p "confirm=Lanjutkan eksekusi? (y/n): "
if /i not "%confirm%"=="y" goto cancel

:: 3. GIT PROCESS
echo.
echo [PROCESS] Git Add...
git add .

echo [PROCESS] Git Commit...
git commit -m "%commit_msg%"

echo [PROCESS] Git Push...
git push -u origin main

:: 4. BACKUP PROCESS
echo.
echo [PROCESS] Membuat Backup ZIP...
if exist "%BACKUP_NAME%" del "%BACKUP_NAME%"

:: Menggunakan tar (tersedia di Windows 10+) untuk membuat zip
:: --exclude digunakan untuk menghindari folder .git dan file zip itu sendiri
tar -a -c -f "%BACKUP_NAME%" --exclude ".git" --exclude "%BACKUP_NAME%" *

if exist "%BACKUP_NAME%" (
    echo [SUCCESS] Backup berhasil dibuat: %BACKUP_NAME%
) else (
    echo [ERROR] Gagal membuat backup.
)

echo.
echo ==========================================
echo      SELESAI!
echo ==========================================
pause
exit /b

:cancel
echo.
echo [INFO] Operasi dibatalkan.
pause
exit /b
