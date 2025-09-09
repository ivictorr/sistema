<?php
header('Content-Type: application/json; charset=utf-8');
require './configuracao/conexao.php'; // Inclua a conex�o com o banco de dados

$codProduto = $_GET['cod_produto'] ?? null;
$codEmbalagem = $_GET['cod_embalagem'] ?? null;

if ($codProduto && $codEmbalagem) {
    try {
        $sql = "
            SELECT 
        A.Cod_produto,
        A.Cod_filial,
        A.Serie_volume,
		CONCAT(A.Cod_filial, A.Serie_volume, 
               RIGHT(REPLICATE('0', 6) + CAST(A.Num_volume AS VARCHAR(6)), 6)) AS Cod_completo,
        A.Peso_liquido,
        A.Peso_bruto,
        A.Data_embalagem,
        A.Num_pallet,
		A.Status as Status,
		TBC.Nome_cadastro
            FROM tbVolume A
			INNER JOIN TBCADASTROGERAL TBC ON A.Cod_funcionario = TBC.Cod_cadastro
            WHERE A.Cod_produto = :codProduto
              AND A.Data_embalagem = :codEmbalagem
              AND A.Num_pallet = 0
			  AND A.Status <> 'C'";

        $stmt = $pdoS->prepare($sql);
        $stmt->execute([
            ':codProduto' => $codProduto,
            ':codEmbalagem' => $codEmbalagem,

        ]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($result);

    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Parâmetros insuficientes']);
}
?>