<?php
header('Content-Type: application/json');
require '../configuracao/conexao.php';

$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d');
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

try {
    // Buscar lote atual
    $stmt = $pdoS->query("SELECT TOP 1 Num_lote FROM TBROMANEIOABATE WHERE Data_abate = CAST(GETDATE() AS DATE) ORDER BY Datahora DESC");
    $lote = $stmt->fetchColumn();

    if (!$lote) {
        echo json_encode(["error" => "Nenhum lote encontrado"]);
        exit;
    }

    // Buscar dados relacionados
    $carcacas = $pdoS->query("
        SELECT A.Num_lote, A.Peso_carcaca1, A.Peso_carcaca2, A.Datahora, 
               CG.Nome_cadastro, PROP.Nome, REF_CCA.Sexo
        FROM TBROMANEIOABATE A
        INNER JOIN tbEntradas B ON A.Chave_fato = B.Chave_fato
        INNER JOIN tbCadastroGeral CG ON CG.Cod_cadastro = B.Cod_cli_for
        INNER JOIN tbPropriedade PROP ON PROP.Cod_propriedade = B.Cod_propriedade AND PROP.Cod_pecuarista = CG.Cod_cadastro
        LEFT JOIN tbProdutoRef REF_CCA ON REF_CCA.Cod_produto = A.Cod_produto
        WHERE A.Num_lote = '$lote' AND Data_abate BETWEEN '$dataInicio' AND '$dataFim'
        ORDER BY A.Datahora
    ")->fetchAll(PDO::FETCH_ASSOC);

    $infoabate = $pdoS->query("
        SELECT 
            CONVERT(DATE, MAX(Data_abate)) AS DATA_ABATE,
            FORMAT(MIN(Datahora), 'HH:mm:ss') AS DATA_INICIAL,
            FORMAT(MAX(Datahora), 'HH:mm:ss') AS DATA_FINAL,
            FORMAT(
                DATEADD(SECOND, 
                    DATEDIFF(SECOND, MIN(Datahora), MAX(Datahora)) - 
                    CASE 
                        WHEN MIN(Datahora) <= '12:00:00' AND MAX(Datahora) > '12:00:00' THEN 3600 
                        ELSE 0 
                    END, 
                0), 
                'HH:mm:ss'
            ) AS TEMPO_TOTAL
        FROM TBROMANEIOABATE
        WHERE Data_abate BETWEEN '$dataInicio' AND '$dataFim'
    ")->fetchAll(PDO::FETCH_ASSOC);

    $infoabatidos = $pdoS->query("
        SELECT 
            SUM(QTDE_CB_ESCALA) AS TOTAL,
            SUM(QTDE_CB_ABATIDA) AS ABATIDO,
            SUM(QTDE_CB_M_EAS) AS MACHO,
            SUM(QTDE_CB_F_EAS) AS FEMEA
        FROM VW_CONTRATO_X_ABATE
        WHERE COD_FILIAL = '".$GLOBALS['FILIAL_USUARIO']."' AND RAA IS NOT NULL AND Data_abate BETWEEN '$dataInicio' AND '$dataFim'
    ")->fetchAll(PDO::FETCH_ASSOC);

    $infoabatidoslote = $pdoS->query("
        SELECT 
            SUM(QTDE_CB_ESCALA) AS TOTAL_LOTE,
            SUM(QTDE_CB_ABATIDA) AS ABATIDO_LOTE,
            SUM(QTDE_CB_M_EAS) AS MACHO_LOTE,
            SUM(QTDE_CB_F_EAS) AS FEMEA_LOTE,
            SUM(PESO_ABATIDO) AS PESO_ABATIDO_LOTE,
            SUM(PESO_ABATIDO / NULLIF(QTDE_CB_ABATIDA, 0)) AS MEDIA_KG_LOTE,
            SUM((PESO_ABATIDO / 15) / NULLIF(QTDE_CB_ABATIDA, 0)) AS MEDIA_ARROBA_LOTE
        FROM VW_CONTRATO_X_ABATE
        WHERE Num_lote_abate = '$lote' AND Data_abate BETWEEN '$dataInicio' AND '$dataFim' AND COD_FILIAL = '".$GLOBALS['FILIAL_USUARIO']."' AND RAA IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);

    $programacaoabate = $pdoS->query("
SELECT 
    Num_lote_abate,
    PRODUTOR,
    NOME_FAZENDA,
    SUM(QTDE_CB_M_EAS) AS QTDE_CB_M_EAS,
    SUM(QTDE_CB_F_EAS) AS QTDE_CB_F_EAS,
    SUM(QTDE_CB_ESCALA) AS QTDE_CB_ESCALA,
    SUM(QTDE_CB_ABATIDA) AS QTDE_CB_ABATIDA,
    SUM(PESO_ABATIDO) AS PESO_ABATIDO
FROM VW_CONTRATO_X_ABATE
WHERE 
    COD_FILIAL = '".$GLOBALS['FILIAL_USUARIO']."' 
    AND RAA IS NOT NULL
    AND Data_abate BETWEEN '$dataInicio' AND '$dataFim'
    AND Num_lote_abate IS NOT NULL
GROUP BY Num_lote_abate, PRODUTOR, NOME_FAZENDA
ORDER BY CAST(Num_lote_abate AS INT)
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'lote' => $lote,
        'carcacas' => $carcacas,
        'infoabate' => $infoabate,
        'infoabatidos' => $infoabatidos,
        'infoabatidoslote' => $infoabatidoslote,
        'programacaoabate' => $programacaoabate
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
