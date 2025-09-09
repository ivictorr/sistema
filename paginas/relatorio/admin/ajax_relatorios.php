<?php
require 'conexao.php'; // sua conexão PDO

$status = $_GET['status'] ?? 'todos';

$sql = "SELECT id, nome, arquivo, solicitante, status,
               DATE_FORMAT(data_inicio, '%d/%m/%Y') as data_inicio,
               DATE_FORMAT(data_finalizacao, '%d/%m/%Y') as data_finalizacao
        FROM relatorios";

$params = [];
if ($status !== 'todos') {
    $sql .= " WHERE status = :status";
    $params[':status'] = $status;
}

$stmt = $pdoM->prepare($sql);
$stmt->execute($params);
$relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($relatorios);
