from flask import Flask, Response, request
import cv2
import time
import threading
import os
from flask_cors import CORS
import numpy as np
import mysql.connector

app = Flask(__name__)
CORS(app)

STREAM_CONFIG = {
    "resize_width": 960,
    "resize_height": 540,
    "jpeg_quality": 75,  # Reduced from 85 to improve encoding speed
    "frame_rate": 24,
    "buffer_size": 0,
    "max_reconnects": 5,
    "stream_timeout": 10
}

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'DmC1884',
    'database': 'MonitoramentoHidrico'
}

device_frames = {}
device_locks = {}

def capture_device_stream(mac_address):
    # Add FFmpeg flags to reduce buffering
    rtmp_url = f"rtmp://172.16.100.240:1935/live/{mac_address}?tcp_nodelay=1&buffer=0"
    print(f"📷 Iniciando captura para dispositivo: {mac_address}")
    print(f"🔗 URL RTMP: {rtmp_url}")

    encode_param = [int(cv2.IMWRITE_JPEG_QUALITY), STREAM_CONFIG["jpeg_quality"]]
    if mac_address not in device_locks:
        device_locks[mac_address] = threading.Lock()

    cap = cv2.VideoCapture()
    cap.open(rtmp_url, cv2.CAP_FFMPEG)
    cap.set(cv2.CAP_PROP_BUFFERSIZE, STREAM_CONFIG["buffer_size"])
    cap.set(cv2.CAP_PROP_FPS, STREAM_CONFIG["frame_rate"])  # Explicitly set frame rate

    reconnect_count = 0
    target_frame_time = 1 / STREAM_CONFIG["frame_rate"]

    while True:
        try:
            start_time = time.time()
            cap.grab()  # Ignora frame acumulado
            success, frame = cap.retrieve()  # Recupera o mais atual possível

            if not success:
                reconnect_count += 1
                print(f"⚠️ Frame falhou para {mac_address}. Tentativa {reconnect_count}/{STREAM_CONFIG['max_reconnects']}")

                if reconnect_count >= STREAM_CONFIG["max_reconnects"]:
                    no_signal_img = create_placeholder_image("Sem sinal da câmera", mac_address)
                    with device_locks[mac_address]:
                        _, buffer = cv2.imencode('.jpg', no_signal_img, encode_param)
                        device_frames[mac_address] = buffer.tobytes()
                    time.sleep(10)
                    reconnect_count = 0

                time.sleep(2)
                cap.release()
                cap.open(rtmp_url, cv2.CAP_FFMPEG)
                cap.set(cv2.CAP_PROP_BUFFERSIZE, STREAM_CONFIG["buffer_size"])
                cap.set(cv2.CAP_PROP_FPS, STREAM_CONFIG["frame_rate"])
                continue

            reconnect_count = 0
            frame = cv2.resize(frame, (STREAM_CONFIG["resize_width"], STREAM_CONFIG["resize_height"]))

            with device_locks[mac_address]:
                ret, buffer = cv2.imencode('.jpg', frame, encode_param)
                if ret:
                    device_frames[mac_address] = buffer.tobytes()

            # Adjust sleep to maintain consistent frame rate
            elapsed_time = time.time() - start_time
            sleep_time = max(0, target_frame_time - elapsed_time)
            time.sleep(sleep_time)

        except Exception as e:
            print(f"❌ Erro stream {mac_address}: {str(e)}")
            time.sleep(2)
            try:
                cap.release()
                cap.open(rtmp_url, cv2.CAP_FFMPEG)
                cap.set(cv2.CAP_PROP_BUFFERSIZE, STREAM_CONFIG["buffer_size"])
                cap.set(cv2.CAP_PROP_FPS, STREAM_CONFIG["frame_rate"])
            except:
                pass

def create_placeholder_image(text, mac_address):
    width = STREAM_CONFIG["resize_width"]
    height = STREAM_CONFIG["resize_height"]
    img = np.zeros((height, width, 3), np.uint8)
    img[:] = (40, 40, 40)
    font = cv2.FONT_HERSHEY_SIMPLEX
    cv2.putText(img, text, (width // 2 - 150, height // 2 - 20), font, 0.8, (255, 255, 255), 2)
    cv2.putText(img, f"MAC: {mac_address}", (width // 2 - 120, height // 2 + 20), font, 0.7, (200, 200, 200), 1)
    return img

def generate_frames(mac_address):
    target_frame_time = 1 / STREAM_CONFIG["frame_rate"]
    while True:
        try:
            start_time = time.time()
            with device_locks.get(mac_address, threading.Lock()):
                frame_bytes = device_frames.get(mac_address)
            if frame_bytes:
                yield (b'--frame\r\n'
                       b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')
            else:
                fallback = create_placeholder_image("Aguardando vídeo...", mac_address)
                _, buffer = cv2.imencode('.jpg', fallback, [int(cv2.IMWRITE_JPEG_QUALITY), STREAM_CONFIG["jpeg_quality"]])
                yield (b'--frame\r\n'
                       b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
            
            # Adjust sleep to maintain consistent frame rate
            elapsed_time = time.time() - start_time
            sleep_time = max(0, target_frame_time - elapsed_time)
            time.sleep(sleep_time)
        except Exception as e:
            print(f"❌ Erro ao gerar frames: {str(e)}")
            time.sleep(1)

@app.route('/video_feed/<mac_address>')
def video_feed(mac_address):
    mac_address = mac_address.replace('-', ':').lower()
    print(f"🔌 Solicitação de stream para: {mac_address}")
    return Response(generate_frames(mac_address),
                    mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/status')
def status():
    return {
        "status": "online",
        "active_devices": list(device_frames.keys()),
        "active_streams": len(device_frames)
    }

def get_known_devices():
    macs = []
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("SELECT mac_address FROM dispositivos_cadastrados")
        macs = [row[0].lower() for row in cursor.fetchall()]
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"❌ Erro ao conectar ao banco de dados: {e}")
    return macs

def iniciar_streams_automaticamente():
    for mac in get_known_devices():
        thread = threading.Thread(target=capture_device_stream, args=(mac,), daemon=True)
        thread.start()

if __name__ == '__main__':
    print("🚀 Iniciando servidor de stream RTMP...")
    iniciar_streams_automaticamente()
    app.run(host='172.16.100.240', port=5000, threaded=True)
