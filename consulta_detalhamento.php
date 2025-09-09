<?php
header('Content-Type: application/json');
require('configuracao/conexao.php');

try {
    // Recebe os dados enviados pelo JavaScript
    $input = json_decode(file_get_contents("php://input"), true);
    $cargas = $input['cargas'] ?? [];

    if (empty($cargas)) {
        echo json_encode(["error" => "Nenhuma carga enviada."]);
        exit;
    }

    // Cria placeholders dinâmicos
    $placeholders = implode(',', array_fill(0, count($cargas), '?'));

    $query = "SELECT 
        SI.COD_PRODUTO, 
        MAX(P.Desc_produto_est) AS Produto,
        SUM(SI.QTDE_PRI) AS QTDE_TOTAL,
        MAX(S.Num_docto) AS docto,

        (
            SELECT SUM(V.Peso_liquido)
            FROM TBVOLUME V
            WHERE V.COD_PRODUTO = SI.COD_PRODUTO
              AND V.STATUS = 'E' AND
			  SI.Cod_local = V.Cod_local_estoque
        ) AS ESTOQUE_KG,

        (
            SELECT SUM(IROS.QTDE_PRI)
            FROM TBSAIDAS PVE2
            INNER JOIN TBSAIDAS ROS ON ROS.CHAVE_FATO_ORIG_UN = PVE2.CHAVE_FATO
            INNER JOIN TBSAIDASITEM IROS ON IROS.CHAVE_FATO = ROS.CHAVE_FATO
            WHERE PVE2.NUM_CARGA IN ($placeholders)
              AND PVE2.COD_DOCTO IN ('PVE','PVX','PAE','PTS')
              AND IROS.COD_PRODUTO = SI.COD_PRODUTO
        ) AS CARREGADO_KG,

        (
            SELECT SUM(IROS.QTDE_AUX)
            FROM TBSAIDAS PVE2
            INNER JOIN TBSAIDAS ROS ON ROS.CHAVE_FATO_ORIG_UN = PVE2.CHAVE_FATO
            INNER JOIN TBSAIDASITEM IROS ON IROS.CHAVE_FATO = ROS.CHAVE_FATO
            WHERE PVE2.NUM_CARGA IN ($placeholders)
              AND PVE2.COD_DOCTO IN ('PVE','PVX','PAE','PTS')
              AND IROS.COD_PRODUTO = SI.COD_PRODUTO
        ) AS CARREGADO_AUX_KG		

    FROM TBSAIDASITEM SI
    INNER JOIN TBSAIDAS S 
        ON S.CHAVE_FATO = SI.CHAVE_FATO 
       AND S.COD_DOCTO IN ('PVE','PVX','PAE','PTS')
    LEFT JOIN TBPRODUTO P ON P.COD_PRODUTO = SI.COD_PRODUTO

    WHERE S.NUM_CARGA IN ($placeholders)

    GROUP BY SI.COD_PRODUTO, SI.Cod_local
    ORDER BY QTDE_TOTAL DESC
    ";

    // Repete os parâmetros 3 vezes (porque usamos os mesmos placeholders 3 vezes)
    $params = array_merge($cargas, $cargas, $cargas);

    $stmt = $pdoS->prepare($query);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($dados ?: [], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
