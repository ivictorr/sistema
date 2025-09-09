<?php
require_once 'conexao.php';

// Obtém parâmetros da requisição
$lote = isset($_GET['lote']) ? $_GET['lote'] : '';
$data_abate = isset($_GET['data_abate']) ? $_GET['data_abate'] : '';

// Busca registros do lote selecionado
$stmt = $pdoM->prepare("
    SELECT id_sisbov, animal, data_abate, prazo, data_registro
    FROM logs_monitoramento
    WHERE lote = ? AND data_abate = ?
");
$stmt->execute([$lote, $data_abate]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gera HTML para a resposta AJAX
foreach ($registros as $registro) {
    echo "<tr>
            <td>{$registro['id_sisbov']}</td>
            <td>{$registro['animal']}</td>
            <td>{$registro['data_abate']}</td>
            <td>{$registro['prazo']}</td>
            <td>{$registro['data_registro']}</td>
          </tr>";
}
