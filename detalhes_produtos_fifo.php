<?php
header('Content-Type: application/json; charset=utf-8');
require './configuracao/conexao.php'; // Inclua a conexão com o banco de dados

$codProduto = $_GET['cod_produto'] ?? null;

if ($codProduto) {
    try {
        $sql = "
        SELECT 
            A.Cod_produto, 
            A.Data_producao, 
            A.Data_validade, 
            A.Peso_liquido, 
            A.Peso_bruto,
            DATEDIFF(DAY, GETDATE(), A.Data_validade) AS Dias_a_vencer
        FROM TBVOLUME A
        WHERE A.Cod_produto = :codProduto
        AND A.Status = 'E'
        ORDER BY Dias_a_vencer ASC
    ";

        $stmt = $pdoS->prepare($sql);
        $stmt->execute([
            ':codProduto' => $codProduto
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Adicionar formatação de datas e cálculo de dias para vencer
        $formattedResults = array_map(function ($item) {
            $dataAtual = new DateTime();
            $dataValidade = new DateTime($item['Data_validade']);

            // Formatar as datas
            $item['Data_producao'] = (new DateTime($item['Data_producao']))->format('d/m/Y');
            $item['Data_validade'] = $dataValidade->format('d/m/Y');

            // Calcular os dias restantes para vencer
            $diasVencer = $dataAtual->diff($dataValidade)->days;

            // Adicionar o sinal negativo para produtos vencidos
            $item['Dias_a_vencer'] = $dataAtual > $dataValidade ? -$diasVencer : $diasVencer;

            return $item;
        }, $results);

        echo json_encode($formattedResults);

    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Parâmetros insuficientes']);
}
?>
