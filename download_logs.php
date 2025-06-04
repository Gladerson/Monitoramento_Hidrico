<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "DmC1884";
$dbname = "MonitoramentoHidrico";

// Criando conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificando conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Capturando MAC Address do dispositivo
$mac_address = $_GET['mac_address'] ?? '';

// Criando objeto Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definindo cabeçalhos
$sheet->setCellValue('A1', 'MAC Address');
$sheet->setCellValue('B1', 'Timestamp');
$sheet->setCellValue('C1', 'Vazão (L/min)');
$sheet->setCellValue('D1', 'Volume Acumulado (L)');

// Estilizando cabeçalho
$sheet->getStyle('A1:D1')->getFont()->setBold(true);
$sheet->getStyle('A1:D1')->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFEEEEEE');

// Buscando todos os registros do dispositivo
$sql_logs = "SELECT * FROM registros_dispositivo WHERE mac_address = ? ORDER BY timestamp";
$stmt_logs = $conn->prepare($sql_logs);
$stmt_logs->bind_param("s", $mac_address);
$stmt_logs->execute();
$result_logs = $stmt_logs->get_result();

// Preenchendo dados
$row = 2;
while ($log_row = $result_logs->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $log_row['mac_address']);
    $sheet->setCellValue('B' . $row, $log_row['timestamp']);
    $sheet->setCellValue('C' . $row, $log_row['vazao']);
    $sheet->setCellValue('D' . $row, $log_row['volume_acumulado']);
    $row++;
}

// Auto ajuste de colunas
foreach (range('A', 'D') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Nome do arquivo
$filename = 'Registros_' . $mac_address . '_' . date('YmdHis') . '.xlsx';

// Cabeçalhos para download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Criar Writer do Excel
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close();
exit();
?>