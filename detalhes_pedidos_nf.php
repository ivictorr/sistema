<?php
require './configuracao/conexao.php'; // Inclui a conexão com o banco de dados


if (isset($_GET['cod_produto']) && isset($_GET['cod_filial'])) {
    $codProduto = $_GET['cod_produto'];
    $codFilial = $_GET['cod_filial'];
    $emissaoDe = $_GET['emissao_de'] ?? '';
    $emissaoAte = $_GET['emissao_ate'] ?? '';

    // Recuperando `localestoque` da requisição GET ou da sessão
    $localEstoque = $_GET['localestoque'] ?? $_SESSION['localestoque'] ?? [];
	
	$tipo_venda = $_SESSION['tipoVenda'] ?? '';

    // Se localEstoque vier como JSON string, decodificar para array
    if (is_string($localEstoque)) {
        $localEstoque = json_decode($localEstoque, true);
    }

    // Garantir que seja um array válido
    if (!is_array($localEstoque)) {
        $localEstoque = [];
    }

    try {
        // Criando a query principal
        $sql = "SELECT 
                    B.Cod_produto,
                    B.Qtde_pri,
                    A.Cod_tipo_mv,
                    A.Num_docto,
                    B.Valor_unitario,
                    TBE.Nome_cadastro AS Cliente,
                    A.Data_v2,
                    A.CHAVE_FATO
                FROM tbSaidas A
                INNER JOIN tbSaidasItem B ON A.CHAVE_FATO = B.CHAVE_FATO AND B.Num_subItem = 0
                INNER JOIN tbProduto tbp ON B.Cod_produto = tbp.Cod_produto
                INNER JOIN tbCadastroGeral TBE ON A.Cod_cli_for = TBE.Cod_cadastro
				INNER JOIN TBTIPOMVESTOQUE D ON A.COD_TIPO_MV = D.COD_TIPO_MV AND A.COD_DOCTO = D.COD_DOCTO
                WHERE 
                    A.COD_DOCTO IN ('NE') 
                    AND A.Cod_filial = ? 
                    AND B.Qtde_pri > 0
                    AND B.COD_PRODUTO = ? 
                    AND A.Status <> 'C'";

        // Criando o array de parâmetros na mesma ordem
        $params = [$codFilial, $codProduto];

        // Adicionando as condições de data apenas se os parâmetros forem fornecidos
        if (!empty($emissaoDe)) {
            $sql .= " AND A.Data_v2 >= ?";
            $params[] = $emissaoDe;
        }
        if (!empty($emissaoAte)) {
            $sql .= " AND A.Data_v2 <= ?";
            $params[] = $emissaoAte;
        }
if ($tipo_venda === 'MI') {
    // Remove T525 de forma incondicional, antes de qualquer lógica de OR
    $sql .= " AND A.Cod_tipo_mv <> 'T525'";
    $sql .= " AND (D.Perfil_tmv IN ('VDA0301') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
} elseif ($tipo_venda === 'ME') {
    $sql .= " AND D.Perfil_tmv IN ('VDA0302')";
} elseif ($tipo_venda === 'TR') {
    $sql .= " AND A.Cod_tipo_mv IN ('T720')";
} else {
    $sql .= " AND (D.Perfil_tmv IN ('VDA0301','VDA0302') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
}


        // Adicionando filtro de Local Estoque
        if (!empty($localEstoque)) {
            // Criando placeholders dinâmicos para os valores de local estoque
            $placeholders = implode(',', array_fill(0, count($localEstoque), '?'));
            $sql .= " AND B.Cod_local IN ($placeholders)";
            // Adicionando os valores ao array de parâmetros
            $params = array_merge($params, $localEstoque);
        }

        // Debugging: Verificar a query e os parâmetros antes da execução
        // echo $sql;
        // var_dump($params);

        $stmt = $pdoS->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($resultados);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro na consulta: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Parâmetros insuficientes fornecidos."]);
}

?>
