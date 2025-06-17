@echo off
cd /d C:\inetpub\wwwroot\generatorApi
start /min php artisan serve --host=127.0.0.1 --port=8129
