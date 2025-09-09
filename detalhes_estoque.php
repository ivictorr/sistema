<?php
header('Content-Type: application/json; charset=utf-8');
require './configuracao/conexao.php';

try {
    $productId = trim($_GET['cod_produto'] ?? '');
    $localEstoque = $_SESSION['local_estoque'] ?? '';
    $filial = $GLOBALS['FILIAL_USUARIO'] ?? '100';

    if (empty($productId)) {
        echo json_encode(['error' => 'Parâmetro cod_produto não fornecido ou vazio.']);
        exit;
    }

    $sql = "
        SELECT 
			A.Cod_produto AS Produto,
			MAX(B.Desc_produto_est) AS Nome_Produto,
            A.Data_producao AS Data_Producao,
            A.Data_validade AS Data_Validade,
            COUNT(*) AS Total_Caixas,
            SUM(A.Peso_liquido) AS Peso_Liquido,
            SUM(A.Peso_bruto) AS Peso_Bruto
        FROM TBVolume A
		INNER JOIN TbProduto B ON A.Cod_produto = B.Cod_produto
        WHERE
            A.Status = 'E'
            AND A.Cod_produto = :product_id
            AND A.Cod_filial = :filial
            AND A.Cod_local_estoque = :localEstoque
        GROUP BY A.Cod_produto, A.Data_producao, A.Data_validade
        ORDER BY A.Data_producao ASC
    ";

    $stmt = $pdoS->prepare($sql);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
    $stmt->bindParam(':filial', $filial, PDO::PARAM_STR);
    $stmt->bindParam(':localEstoque', $localEstoque, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Totais
    $totalCaixas = 0;
    $totalPesoLiq = 0;
    $totalPesoBruto = 0;

    $formatted = array_map(function ($row) use (&$totalCaixas, &$totalPesoLiq, &$totalPesoBruto) {
        $totalCaixas += (int)$row['Total_Caixas'];
        $totalPesoLiq += (float)$row['Peso_Liquido'];
        $totalPesoBruto += (float)$row['Peso_Bruto'];

        return [
			'Produto' => $row['Produto'],
			'Nome_Produto' => $row['Nome_Produto'],
            'Data_Producao' => date('d/m/Y', strtotime($row['Data_Producao'])),
            'Data_Validade' => date('d/m/Y', strtotime($row['Data_Validade'])),
            'Total_Caixas'  => (int)$row['Total_Caixas'],
            'Peso_Liquido'  => number_format($row['Peso_Liquido'], 3, ',', ''),
            'Peso_Bruto'    => number_format($row['Peso_Bruto'], 3, ',', '')
        ];
    }, $result);

    $resumo = [
        'total_caixas' => $totalCaixas,
        'peso_liquido_total' => number_format($totalPesoLiq, 3, ',', ''),
        'peso_bruto_total' => number_format($totalPesoBruto, 3, ',', '')
    ];

    echo json_encode([
        'dados' => $formatted,
        'resumo' => $resumo
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro no servidor.', 'details' => $e->getMessage()]);
}
