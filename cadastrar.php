<?php
// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "DmC1884";
$dbname = "MonitoramentoHidrico";

// Criando conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Configurando charset para UTF-8
$conn->set_charset("utf8");

// Verificando conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Capturando e sanitizando dados do formulário
$mac_address = $conn->real_escape_string(trim($_POST['mac_address']));

// Validando formato do MAC address
if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac_address)) {
    die("Formato de MAC address inválido");
}

// Preparando a consulta com prepared statement
$stmt = $conn->prepare("SELECT * FROM dispositivos_disponiveis WHERE mac_address = ?");
$stmt->bind_param("s", $mac_address);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Obtendo dados do dispositivo
    $row = $result->fetch_assoc();
    $local = $row['local'];
    $municipio = $row['municipio'];
    $tipo = $row['tipo'];
    $nome_dispositivo = $row['nome_dispositivo'];

    // Preparando inserção na tabela dispositivos_cadastrados com prepared statement
    $stmt_insert = $conn->prepare("
        INSERT INTO dispositivos_cadastrados 
        (mac_address, local, municipio, tipo, nome_dispositivo, vazao, volume_acumulado, status, ultima_atualizacao)
        VALUES (?, ?, ?, ?, ?, 0.00, 0.00, 'online', NOW())
        ON DUPLICATE KEY UPDATE
        nome_dispositivo = VALUES(nome_dispositivo),
        ultima_atualizacao = NOW()
    ");

    $stmt_insert->bind_param("sssss", 
        $mac_address,
        $local,
        $municipio,
        $tipo,
        $nome_dispositivo
    );

    if ($stmt_insert->execute()) {
        // Inserção bem-sucedida
        echo "<div class='alert alert-success'>Dispositivo cadastrado com sucesso.</div>";

        // Preparando remoção da tabela dispositivos_disponiveis
        $stmt_delete = $conn->prepare("DELETE FROM dispositivos_disponiveis WHERE mac_address = ?");
        $stmt_delete->bind_param("s", $mac_address);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Inserindo na tabela macs_confiaveis se ainda não existir
        $stmt_mac = $conn->prepare("
            INSERT IGNORE INTO macs_confiaveis (mac_address) 
            VALUES (?)
        ");
        $stmt_mac->bind_param("s", $mac_address);
        $stmt_mac->execute();
        $stmt_mac->close();

        // Redirecionar para index.php após 2 segundos
        echo "<script>
                setTimeout(function(){
                    window.location.href = 'index.php';
                }, 2000);
              </script>";
    } else {
        echo "<div class='alert alert-danger'>Erro ao cadastrar dispositivo: " . $stmt_insert->error . "</div>";
    }

    $stmt_insert->close();
} else {
    echo "<div class='alert alert-warning'>Dispositivo não encontrado na lista de disponíveis.</div>";
}

// Fechando statements e conexão
$stmt->close();
$conn->close();
?>