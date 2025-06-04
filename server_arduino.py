import socket
import json
import pymysql
from decimal import Decimal
import time
import schedule
from datetime import datetime
from threading import Thread, Event

# Configurações do servidor
HOST = '172.16.100.240'
PORT = 9876

# Configurações do banco de dados
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'DmC1884',
    'database': 'MonitoramentoHidrico'
}

# Evento para controle de término
stop_event = Event()

def validate_data(data_json):
    """Valida os dados recebidos do Arduino"""
    required_fields = ['mac_address', 'local', 'municipio', 'tipo', 'nome', 'fluxo', 'volume']
    if not all(key in data_json for key in required_fields):
        raise ValueError("Dados incompletos recebidos")
    
    # Validação de tamanhos máximos
    if any([
        len(data_json['local']) > 36,
        len(data_json['municipio']) > 100,
        len(data_json['tipo']) > 100,
        len(data_json['nome']) > 100
    ]):
        raise ValueError("Dados excedem tamanho máximo permitido")
    
    # Converte e valida valores numéricos
    try:
        vazao = Decimal(str(data_json['fluxo']))
        volume = Decimal(str(data_json['volume']))
        if volume < 0:
            raise ValueError("Volume não pode ser negativo")
        return vazao, volume
    except (ValueError, TypeError, decimal.InvalidOperation) as e:
        raise ValueError(f"Erro na conversão de valores numéricos: {e}")

def handle_client(conn, addr):
    """Gerencia a conexão com cada dispositivo"""
    print(f'Conectado por {addr}')
    with conn:
        try:
            request_data = b""
            while True:
                chunk = conn.recv(1024)
                if not chunk:
                    break
                request_data += chunk
                if b'\r\n\r\n' in request_data:
                    break

            request_text = request_data.decode('utf-8')
            headers, body = request_text.split('\r\n\r\n', 1)
            data_json = json.loads(body.strip())
            
            # Valida os dados recebidos
            vazao, volume = validate_data(data_json)
            mac_address = data_json['mac_address']
            local = data_json['local']
            municipio = data_json['municipio']
            tipo = data_json['tipo']
            nome_dispositivo = data_json['nome']

            with pymysql.connect(**db_config) as connection:
                with connection.cursor() as cursor:
                    # Verifica se o MAC é confiável
                    cursor.execute(
                        "SELECT * FROM macs_confiaveis WHERE mac_address = %s",
                        (mac_address,)
                    )
                    if not cursor.fetchone():
                        print(f'MAC address nao confiavel: {mac_address}')
                        error_msg = "MAC address nao confiavel"
                        conn.sendall(f"HTTP/1.1 403 Forbidden\r\nContent-Type: text/plain\r\n\r\n{error_msg}".encode('utf-8'))
                        return

                    # Busca o dispositivo cadastrado
                    cursor.execute(
                        """
                        SELECT volume_acumulado, vazao 
                        FROM dispositivos_cadastrados 
                        WHERE mac_address = %s
                        """,
                        (mac_address,)
                    )
                    device_data = cursor.fetchone()
                    
                    if device_data:
                        previous_volume = Decimal(str(device_data[0]))
                        previous_vazao = Decimal(str(device_data[1]))
                        
                        # Calcula novo volume acumulado
                        new_volume = previous_volume + volume
                        
                        # Define a vazão a ser registrada
                        # Se a nova vazão for 0, mantém a anterior
                        vazao_to_register = previous_vazao if vazao == 0 else vazao
                        
                        # Atualiza dispositivos_cadastrados
                        cursor.execute(
                            """
                            UPDATE dispositivos_cadastrados
                            SET vazao = %s, volume_acumulado = %s, 
                                ultima_atualizacao = NOW(), status = 'online', 
                                tipo = %s
                            WHERE mac_address = %s
                            """,
                            (vazao_to_register, new_volume, tipo, mac_address)
                        )
                        
                        # Insere o registro diário
                        cursor.execute(
                            """
                            INSERT INTO registros_dispositivo 
                            (mac_address, vazao, volume_acumulado, timestamp)
                            VALUES (%s, %s, %s, DATE(NOW()))
                            ON DUPLICATE KEY UPDATE
                            vazao = %s,
                            volume_acumulado = volume_acumulado + %s
                            """,
                            (mac_address, vazao_to_register, volume, vazao_to_register, volume)
                        )
                    else:
                        # Dispositivo não cadastrado - verifica se está disponível
                        cursor.execute(
                            "SELECT * FROM dispositivos_disponiveis WHERE mac_address = %s",
                            (mac_address,)
                        )
                        if cursor.fetchone():
                            cursor.execute(
                                """
                                UPDATE dispositivos_disponiveis
                                SET ultima_atualizacao = NOW(), tipo = %s
                                WHERE mac_address = %s
                                """,
                                (tipo, mac_address)
                            )
                        else:
                            cursor.execute(
                                """
                                INSERT INTO dispositivos_disponiveis 
                                (mac_address, local, municipio, tipo, nome_dispositivo, ultima_atualizacao)
                                VALUES (%s, %s, %s, %s, %s, NOW())
                                """,
                                (mac_address, local, municipio, tipo, nome_dispositivo)
                            )
                    
                    connection.commit()
                    print(f'Dados processados: {data_json}')
                    conn.sendall("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{}".encode('utf-8'))

        except Exception as e:
            print(f'Erro ao processar dados: {e}')
            error_msg = "Erro interno do servidor"
            conn.sendall(f"HTTP/1.1 500 Internal Server Error\r\nContent-Type: text/plain\r\n\r\n{error_msg}".encode('utf-8'))

def check_device_status():
    """Verifica e atualiza o status dos dispositivos"""
    print("Verificando status dos dispositivos...")
    try:
        with pymysql.connect(**db_config) as connection:
            with connection.cursor() as cursor:
                cursor.execute(
                    """
                    DELETE FROM dispositivos_disponiveis
                    WHERE ultima_atualizacao < (NOW() - INTERVAL 30 SECOND)
                    """
                )
                cursor.execute(
                    """
                    UPDATE dispositivos_cadastrados
                    SET status = 'offline'
                    WHERE ultima_atualizacao < (NOW() - INTERVAL 30 SECOND)
                    """
                )
                connection.commit()
    except Exception as e:
        print(f"Erro ao verificar status dos dispositivos: {e}")
    return schedule.CancelJob if stop_event.is_set() else None

def is_leap_year(year):
    """Verifica se o ano é bissexto"""
    return year % 4 == 0 and (year % 100 != 0 or year % 400 == 0)

def log_device_data():
    """Limpa registros antigos dos dispositivos"""
    print("Limpando registros antigos dos dispositivos...")
    try:
        current_year = datetime.now().year
        days_in_year = 366 if is_leap_year(current_year) else 365
        
        with pymysql.connect(**db_config) as connection:
            with connection.cursor() as cursor:
                cursor.execute(
                    """
                    DELETE FROM registros_dispositivo
                    WHERE timestamp < DATE_SUB(CURDATE(), INTERVAL %s DAY)
                    """,
                    (days_in_year,)
                )
                connection.commit()
    except Exception as e:
        print(f"Erro ao limpar registros antigos: {e}")
    return schedule.CancelJob if stop_event.is_set() else None

def run_scheduler():
    """Executa as tarefas agendadas"""
    schedule.every(30).seconds.do(check_device_status)
    schedule.every().day.at("23:59").do(log_device_data)
    
    while not stop_event.is_set():
        schedule.run_pending()
        time.sleep(1)

def main():
    """Função principal do servidor"""
    scheduler_thread = None
    try:
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            s.bind((HOST, PORT))
            s.listen()
            print(f'Servidor ouvindo em {HOST}:{PORT}')
            
            scheduler_thread = Thread(target=run_scheduler, daemon=True)
            scheduler_thread.start()
            
            while True:
                conn, addr = s.accept()
                client_thread = Thread(target=handle_client, args=(conn, addr), daemon=True)
                client_thread.start()
    
    except KeyboardInterrupt:
        print("\nEncerrando o servidor...")
    except Exception as e:
        print(f"Erro no servidor: {e}")
    finally:
        stop_event.set()
        if scheduler_thread:
            scheduler_thread.join(timeout=5)

if __name__ == "__main__":
    main()
