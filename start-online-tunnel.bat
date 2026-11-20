@echo off
title Cek Resi Aruna - Cloudflare Online Tunnel
echo ========================================================
echo   Menjalankan Cloudflare Tunnel untuk Cek Resi Aruna...
echo ========================================================
echo.
echo Pastikan web server lokal (http://127.0.0.1:8000) aktif!
echo.
"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:8000
pause
