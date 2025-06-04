#!/bin/bash

# Encerrar o servidor Arduino
PID_SERVER=$(ps aux | grep '[s]erver_arduino.py' | awk '{print $2}')
if [ -z "$PID_SERVER" ]; then
  echo "❌ server_arduino.py não está em execução."
else
  kill "$PID_SERVER"
  echo "✅ server_arduino.py (PID: $PID_SERVER) encerrado com sucesso."
fi

# Encerrar o stream da câmera
PID_STREAM=$(ps aux | grep '[s]tream.py' | awk '{print $2}')
if [ -z "$PID_STREAM" ]; then
  echo "❌ stream.py não está em execução."
else
  kill "$PID_STREAM"
  echo "✅ stream.py (PID: $PID_STREAM) encerrado com sucesso."
fi
