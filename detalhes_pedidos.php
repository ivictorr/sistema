<?php
header('Content-Type: application/json; charset=utf-8');
require './configuracao/conexao.php';

try {
    $productId = trim($_GET['cod_produto'] ?? '');
	$localEstoque = $_SESSION['local_estoque'] ?? '';
    $filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; // fallback para 100

    if (empty($productId)) {
        echo json_encode(['error' => 'Parâmetro cod_produto não fornecido ou vazio.']);
        exit;
    }

    $sql = "
SELECT 
    TBI.COD_PRODUTO,
    PVE.Cod_tipo_mv,
    SUM(TBI.QTDE_PRI) AS PESO_RESERV,
    PVE.Num_docto,
    PVE.Cod_usuario,
    FORMAT(PVE.Data_movto, 'dd/MM/yyyy') AS Data_movto,
    FORMAT(PVE.Data_v1, 'dd/MM/yyyy') AS Data_v1,
    TBI.Valor_unitario AS Preco,
    TBE.Nome_cadastro AS Cliente
FROM tbSaidas PVE
LEFT JOIN tbSaidas ROS
    ON ROS.Chave_fato_orig_un = PVE.Chave_fato
    AND ROS.COD_DOCTO IN ('ROS','RTS')
    AND ROS.Cod_filial = PVE.Cod_filial
INNER JOIN tbSaidasItem TBI
    ON TBI.Chave_fato = PVE.Chave_fato
INNER JOIN tbCadastroGeral TBE 
    ON PVE.Cod_cli_for = TBE.Cod_cadastro
WHERE 
    PVE.COD_DOCTO IN ('PVE','PAE')
    AND PVE.Cod_filial = :filial
    AND PVE.Status <> 'C'
    AND TBI.Cod_local = :localEstoque
    AND TBI.COD_PRODUTO = :product_id
    AND PVE.Cod_tipo_mv IN ('T500', 'T700', 'E500','M500','M501','M502','M503','T800')
    AND PVE.Data_v2 BETWEEN DATEADD(DAY, -15, GETDATE()) AND DATEADD(YEAR, 2, GETDATE())
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidasItem TBI_PTC
        INNER JOIN tbSaidas PTC
            ON PTC.Chave_fato = TBI_PTC.Chave_fato
            AND PTC.COD_DOCTO = 'PTC'
            AND PTC.Cod_filial = PVE.Cod_filial
        WHERE TBI_PTC.Chave_fato_orig = ROS.Chave_fato
    )
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidasItem TBI_PTO
        INNER JOIN tbSaidas PTO
            ON PTO.Chave_fato = TBI_PTO.Chave_fato
            AND PTO.COD_DOCTO = 'PTO'
            AND PTO.Cod_filial = PVE.Cod_filial
        WHERE TBI_PTO.Chave_fato_orig = ROS.Chave_fato
    )
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidas NE_ROS
        WHERE NE_ROS.Chave_fato_orig_un = ROS.Chave_fato
          AND NE_ROS.COD_DOCTO IN ('NE', 'NEE')
          AND NE_ROS.Cod_filial = PVE.Cod_filial
    )
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidas PAV
        WHERE PAV.Chave_fato_orig_un = PVE.Chave_fato
          AND PAV.COD_DOCTO = 'PAV'
          AND PAV.Cod_filial = PVE.Cod_filial
    )
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidas NE_PVE
        WHERE NE_PVE.Chave_fato_orig_un = PVE.Chave_fato
          AND NE_PVE.COD_DOCTO IN ('NE', 'NEE')
          AND NE_PVE.Cod_filial = PVE.Cod_filial
    )
GROUP BY
    TBI.COD_PRODUTO,
    PVE.Cod_tipo_mv,
    PVE.Num_docto,
    PVE.Cod_usuario,
    FORMAT(PVE.Data_movto, 'dd/MM/yyyy'),
    FORMAT(PVE.Data_v1, 'dd/MM/yyyy'),
    TBI.Valor_unitario,
    TBE.Nome_cadastro
    ";

    $stmt = $pdoS->prepare($sql);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
    $stmt->bindParam(':filial', $filial, PDO::PARAM_STR);
	$stmt->bindParam(':localEstoque', $localEstoque, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result ?: []);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
?>
