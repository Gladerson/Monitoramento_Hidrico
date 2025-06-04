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
    die(json_encode(['success' => false, 'message' => 'Falha na conexão: ' . $conn->connect_error]));
}

// Capturando MAC Address do dispositivo
$mac_address = $_POST['mac_address'] ?? '';

// Iniciar transação
$conn->begin_transaction();

try {
    // Deletar registros do dispositivo
    $sql_delete = "DELETE FROM registros_dispositivo WHERE mac_address = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("s", $mac_address);
    $stmt_delete->execute();

    // Atualizar volume acumulado no dispositivo
    $sql_update = "UPDATE dispositivos_cadastrados SET volume_acumulado = 0 WHERE mac_address = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("s", $mac_address);
    $stmt_update->execute();

    // Confirmar transação
    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>