<?php
session_start();

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set("Etc/GMT+3");

// Conexão com MySQL principal (sistemavalencio)
$db_host = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "sistemavalencio";

global $pdoM;
try {
    $pdoM = new PDO("mysql:dbname={$db_name};host={$db_host}", $db_username, $db_password);
    $pdoM->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdoM->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
    echo "Erro na conexão com MySQL principal: " . $e->getMessage();
    exit;
}

// Conexão com MySQL secundário (newcalc)
$db_name_sec = "newcalc";

global $pdoC;
try {
    $pdoC = new PDO("mysql:dbname={$db_name_sec};host={$db_host}", $db_username, $db_password);
    $pdoC->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erro na conexão com MySQL secundário: " . $e->getMessage();
    exit;
}

// Função para buscar dados do usuário
function getUsuarioPorId($id) {
    global $pdoM;
    $stmt = $pdoM->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Função para conectar ao SQL Server de acordo com a filial
function conectarSQLServerPorFilial($filialSelecionada) {
    global $pdoS;

    if ($filialSelecionada == '100') {
        $db_host = "192.168.0.249";
        $db_name = "SATKVALENCIO";
    } elseif ($filialSelecionada == '200') {
        $db_host = "192.168.20.251";
        $db_name = "SATKVALENCIO_JATAI";
    } elseif ($filialSelecionada == '400') {
        $db_host = "10.1.150.251";
        $db_name = "SATKALTAMIRA";
    } else {
        echo "Filial inválida!";
        exit;
    }

    $db_user = "dbcliente";
    $db_pass = "Vic@2025";
    $db_driver = "sqlsrv";

    $pdoSConfig  = "$db_driver:Server=$db_host;";
    $pdoSConfig .= "Database=$db_name;";

    try {
        $pdoS = new PDO($pdoSConfig, $db_user, $db_pass);
        $pdoS->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo "Erro de conexão com o SQL Server: " . $e->getMessage();
        error_log("Erro de conexão com o SQL Server: " . $e->getMessage());
        exit;
    }
}

// Função para verificar se o usuário é administrador
function isAdmin($user_id) {
    global $pdoM;
    $query = $pdoM->prepare("SELECT 1 FROM usuarios WHERE id = :user_id AND Administrador = 'SIM'");
    $query->execute(['user_id' => $user_id]);
    return $query->fetchColumn() !== false;
}
// Função para verificar se o usuário é PCP
function isPcp($user_id) {
    global $pdoM;
    $query = $pdoM->prepare("SELECT 1 FROM usuarios WHERE id = :user_id AND PCP = 'SIM'");
    $query->execute(['user_id' => $user_id]);
    return $query->fetchColumn() !== false;
}
// Verifica e atualiza sessão do usuário
function verificarSessao($userId) {
    global $pdoM;
    $sessionId = session_id();

    $stmt = $pdoM->prepare("SELECT is_logged_in, last_session_id FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['is_logged_in'] && $user['last_session_id'] !== $sessionId) {
            $stmt = $pdoM->prepare("UPDATE usuarios SET is_logged_in = 0, last_session_id = NULL WHERE id = :id");
            $stmt->execute([':id' => $userId]);

            session_unset();
            session_destroy();
            header("Location: paginas/login.php");
            exit();
        } else {
            $stmt = $pdoM->prepare("UPDATE usuarios SET is_logged_in = 1, last_session_id = :session_id WHERE id = :id");
            $stmt->execute([
                ':session_id' => $sessionId,
                ':id' => $userId
            ]);
        }
    }
}

// Autenticação e controle de filial do usuário
if (isset($_SESSION['user_id'])) {
    $usuario = getUsuarioPorId($_SESSION['user_id']);
    if ($usuario && isset($usuario['filial_selecionada'])) {

        // Verifica se foi feita uma troca de filial
        if (isset($_GET['trocar_filial']) && in_array($_GET['trocar_filial'], ['100', '200', '400'])) {
            $novaFilial = $_GET['trocar_filial'];

            $stmt = $pdoM->prepare("UPDATE usuarios SET filial_selecionada = :filial WHERE id = :id");
            $stmt->execute([
                ':filial' => $novaFilial,
                ':id' => $_SESSION['user_id']
            ]);

            // Atualiza variáveis globais
            conectarSQLServerPorFilial($novaFilial);
            $GLOBALS['FILIAL_USUARIO'] = $novaFilial;

            header("Location: ./"); // Redireciona para a página principal
            exit();
        }

        // Filial padrão se não houver troca
        $filialSelecionada = $usuario['filial_selecionada'];
        conectarSQLServerPorFilial($filialSelecionada);
        $GLOBALS['FILIAL_USUARIO'] = $filialSelecionada;
    }
}
?>
