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

// Buscando detalhes do dispositivo
$sql = "SELECT * FROM dispositivos_cadastrados WHERE mac_address = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $mac_address);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Dispositivo não encontrado']);
}

$conn->close();
?>