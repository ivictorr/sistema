<?php
header('Content-Type: application/json');
require('../../../../configuracao/conexao.php');
try {
    $query = $pdoS->query("SELECT Cod_banco_caixa, Nome_agencia FROM tbBancoCaixa WHERE Status = 'A'");

    // Retornar os dados como JSON
    echo json_encode($query->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    // Retornar erro em caso de falha
    echo json_encode(['error' => $e->getMessage()]);
}
?>
