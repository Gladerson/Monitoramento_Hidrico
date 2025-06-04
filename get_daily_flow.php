<?php
header('Content-Type: application/json');

// Verificar se o mac_address foi fornecido
if (!isset($_GET['mac_address']) || empty($_GET['mac_address'])) {
    echo json_encode([
        'success' => false,
        'message' => 'MAC address não fornecido',
        'dailyFlows' => []
    ]);
    exit;
}

$mac_address = $_GET['mac_address'];

// Verificar se o mês foi fornecido, caso contrário, usar o mês atual
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Validar o mês
if ($month < 1 || $month > 12) {
    $month = date('n'); // Usar o mês atual se o valor for inválido
}

// Ano atual para os dados
$current_year = date('Y');

// Conectar ao banco de dados
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=MonitoramentoHidrico;charset=utf8mb4',
        'root',
        'DmC1884',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao conectar ao banco de dados',
        'dailyFlows' => []
    ]);
    exit;
}

// Determinar o número de dias no mês selecionado
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $current_year);

// Array para armazenar a vazão diária (inicializa com nulls)
$daily_flows = array_fill(0, $days_in_month, null);

// Consulta para obter a média da vazão diária para o mês selecionado
$query = "
    SELECT 
        DAY(timestamp) as day, 
        AVG(vazao) as avg_flow
    FROM 
        registros_dispositivo
    WHERE 
        mac_address = :mac_address
        AND YEAR(timestamp) = :year
        AND MONTH(timestamp) = :month
    GROUP BY 
        DAY(timestamp)
    ORDER BY 
        DAY(timestamp)
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':mac_address' => $mac_address,
        ':year' => $current_year,
        ':month' => $month
    ]);

    // Preencher o array com dados reais
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Os dias no banco são de 1 a 31, mas o array é indexado de 0 a 30
        $day_index = (int)$row['day'] - 1;
        
        // Verifica se o índice está dentro dos limites do array
        if ($day_index >= 0 && $day_index < $days_in_month) {
            $daily_flows[$day_index] = (float)$row['avg_flow'];
        }
    }

    // Buscar o valor atual da vazão do dispositivo para dias sem registro
    // especialmente para o dia atual que pode não ter um registro completo ainda
    $today = date('j');
    if ($month == date('n') && $daily_flows[$today - 1] === null) {
        $query = "
            SELECT vazao 
            FROM dispositivos_cadastrados 
            WHERE mac_address = :mac_address
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([':mac_address' => $mac_address]);
        $device_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($device_data && $device_data['vazao'] > 0) {
            $daily_flows[$today - 1] = (float)$device_data['vazao'];
        }
    }

    // Retorna os dados diários de vazão
    echo json_encode([
        'success' => true,
        'dailyFlows' => $daily_flows,
        'month' => $month,
        'daysInMonth' => $days_in_month
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao obter dados de vazão diária: ' . $e->getMessage(),
        'dailyFlows' => []
    ]);
}
?>
