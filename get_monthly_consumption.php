<?php
header('Content-Type: application/json');

// Verificar se o mac_address foi fornecido
if (!isset($_GET['mac_address']) || empty($_GET['mac_address'])) {
    echo json_encode([
        'success' => false,
        'message' => 'MAC address não fornecido',
        'monthlyVolumes' => array_fill(0, 12, 0)
    ]);
    exit;
}

$mac_address = $_GET['mac_address'];

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
        'monthlyVolumes' => array_fill(0, 12, 0)
    ]);
    exit;
}

// Ano atual para os dados
$current_year = date('Y');

// Array para armazenar o consumo de cada mês (inicializa com zeros)
$monthly_volumes = array_fill(0, 12, 0);

// Consulta para obter o volume acumulado por mês para o dispositivo específico
$query = "
    SELECT 
        MONTH(timestamp) as month, 
        SUM(volume_acumulado) as total_volume
    FROM 
        registros_dispositivo
    WHERE 
        mac_address = :mac_address
        AND YEAR(timestamp) = :year
    GROUP BY 
        MONTH(timestamp)
    ORDER BY 
        MONTH(timestamp)
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':mac_address' => $mac_address,
        ':year' => $current_year
    ]);

    // Preencher o array com dados reais
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Os meses no banco são de 1 a 12, mas o array é indexado de 0 a 11
        $month_index = (int)$row['month'] - 1;
        $monthly_volumes[$month_index] = (float)$row['total_volume'];
    }

    // Retorna os dados mensais de consumo
    echo json_encode([
        'success' => true,
        'monthlyVolumes' => $monthly_volumes,
        'currentMonth' => date('n') // Retorna o mês atual (1-12)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao obter dados de consumo mensal: ' . $e->getMessage(),
        'monthlyVolumes' => array_fill(0, 12, 0)
    ]);
}
?>
