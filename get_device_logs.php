<?php
header('Content-Type: application/json');

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "DmC1884";
$dbname = "MonitoramentoHidrico";

// Criando conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificando conexão
if ($conn->connect_error) {
    die(json_encode(['error' => 'Falha na conexão: ' . $conn->connect_error]));
}

// Capturando MAC Address do dispositivo
$mac_address = $_GET['mac_address'] ?? '';

// Buscando registros a cada 1 semana
$sql_logs = "SELECT * FROM registros_dispositivo WHERE mac_address = ? ORDER BY timestamp DESC LIMIT 50";
$stmt_logs = $conn->prepare($sql_logs);
$stmt_logs->bind_param("s", $mac_address);
$stmt_logs->execute();
$result_logs = $stmt_logs->get_result();

$logs = [];
if ($result_logs->num_rows > 0) {
    while ($log_row = $result_logs->fetch_assoc()) {
        $logs[] = $log_row;
    }
}

echo json_encode($logs);

$conn->close();
?>