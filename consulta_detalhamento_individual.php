<?php
header('Content-Type: application/json');

require('configuracao/conexao.php');

$filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; // fallback para 100

$input = json_decode(file_get_contents("php://input"), true);
$num_carga = $input['num_carga'] ?? null;

if (!$num_carga) {
    echo json_encode([]); // Retorna array vazio
    exit;
}

$query = "SELECT 
    SI.COD_PRODUTO, 
    MAX(P.Desc_produto_est) AS Produto,
    SUM(SI.QTDE_PRI) AS QTDE_TOTAL,

    (
        SELECT SUM(V.Peso_liquido)
        FROM TBVOLUME V
        WHERE V.COD_PRODUTO = SI.COD_PRODUTO
        AND V.STATUS = 'E'
    ) AS ESTOQUE_KG,

    (
        SELECT SUM(IROS.QTDE_PRI)
        FROM TBSAIDAS PVE2
        INNER JOIN TBSAIDAS ROS ON ROS.CHAVE_FATO_ORIG_UN = PVE2.CHAVE_FATO
        INNER JOIN TBSAIDASITEM IROS ON IROS.CHAVE_FATO = ROS.CHAVE_FATO
        WHERE PVE2.NUM_CARGA = ?
        AND PVE2.COD_DOCTO IN ('PVE','PVX', 'PAE')
        AND IROS.COD_PRODUTO = SI.COD_PRODUTO
    ) AS CARREGADO_KG

FROM TBSAIDASITEM SI
INNER JOIN TBSAIDAS S ON S.CHAVE_FATO = SI.CHAVE_FATO AND S.COD_DOCTO IN ('PVE','PVX','PAE')
LEFT JOIN TBPRODUTO P ON P.COD_PRODUTO = SI.COD_PRODUTO
WHERE 
S.NUM_CARGA = ?
GROUP BY SI.COD_PRODUTO
ORDER BY QTDE_TOTAL DESC";

$stmt = $pdoS->prepare($query);
$stmt->execute([$num_carga, $num_carga]);

$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($dados ?: []);
