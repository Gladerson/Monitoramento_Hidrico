#!/bin/bash

# Caminho do diretório do projeto
cd /var/www/html/MonitoramentoHidrico || exit

# Ativar o ambiente virtual
source /var/www/html/venv/bin/activate

# Iniciar o servidor do Arduino em background
nohup python3 server_arduino.py > server_arduino.log 2>&1 &

# Iniciar o servidor de streaming da câmera em background
nohup python3 stream.py > stream.log 2>&1 &
