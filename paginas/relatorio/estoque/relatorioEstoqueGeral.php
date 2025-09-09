<?php 
$filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; // fallback para 100
$locaisPermitidosPorFilial = [
    '100' => ['01'],
    '200' => ['01', '04', '05'],
    '400' => ['01'],
];
$locaisPermitidos = $locaisPermitidosPorFilial[$filial] ?? [];
$codigosIn = implode(',', array_map(fn($cod) => "'" . $cod . "'", $locaisPermitidos));
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<style>
    .table-container {
        padding: 20px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        margin-bottom: 30px;
        border: 1px solid #ddd;
    }

    .table-container h3 {
        text-align: center;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 10px;
        color: white;
        font-weight: bold;
    }

    .table {
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #ccc;
    }

    .table thead {
        background-color: #007bff;
        color: white;
    }

    .table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .table tbody tr:hover {
        background-color: #d9edf7;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .table th, .table td {
        text-align: center;
        vertical-align: middle;
        padding: 12px;
    }

    .table tfoot {
        font-weight: bold;
        background-color: #e9ecef;
    }

    .materia-prima {
        background: linear-gradient(135deg, #007bff, #0056b3);
    }

    .produto-resfriado {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }

    .produto-congelado {
        background: linear-gradient(135deg, #17a2b8, #0f6674);
    }

    @media print {
        body {
            visibility: hidden;
        }

        .container {
            visibility: visible;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .table-container {
            page-break-inside: avoid;
            border: none;
            box-shadow: none;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
        }

        .table th, .table td {
            border: 1px solid black;
            padding: 8px;
        }

        .no-print {
            display: none !important;
        }
    }
</style>

<?php $filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<div class="container mt-5">
    <br>
    <div class="panel panel-primary no-print mt-5">
        <div class="panel-heading text-center">
            <h3>RELATÓRIO DE ESTOQUE VALORIZADO</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
            <div class="form-group row">
                <label for="produto-select" class="col-sm-2 col-form-label text-right">Local de Estoque:</label>
                <div class="col-sm-10">
                    <select id="localEstoque" name="localEstoque" class="form-control">
                        <?php
                        // Recuperar os locais de estoque do banco de dados
                        $stmtLocais = $pdoS->query("SELECT DISTINCT Desc_local, Cod_local 
          FROM tbLocalEstoque 
          WHERE Cod_filial = {$filial} 
          AND Cod_local IN ($codigosIn)
          ORDER BY Desc_local");
                        while ($local = $stmtLocais->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . htmlspecialchars($local['Cod_local']) . '">' . htmlspecialchars($local['Desc_local']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group text-center">
                <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
                <button type="submit" name="salvarFiltro" class="btn btn-secondary">Salvar Filtro</button>
                <button type="submit" name="limparFiltro" class="btn btn-danger">Limpar Filtro</button>
            </div>
        </form>
    </div>

    <?php if (isset($_POST['gerarRelatorio'])):

        $filialSelecionada = is_array($filial) ? reset($filial) : $filial;
        $localSelecionado = $_POST['localEstoque'] ?? '';

$sql = "
;WITH Estoque AS (
    SELECT
        MAX(A.Cod_produto) AS Cod_produto,
        B.Desc_produto_est AS Produto, 
        COUNT(A.Cod_produto) AS QTD_REGISTROS,
        SUM(A.Peso_liquido) AS Peso_Liquido_Total, 
        SUM(A.Peso_bruto) AS Peso_Bruto_Total,
        MAX(B.COD_DIVISAO1) AS COD_DIVISAO1,
        MAX(B.COD_DIVISAO2) AS COD_DIVISAO2,
        MAX(C.TIPO_TEMPERATURA) AS TIPO_TEMPERATURA
    FROM tbVolume A 
    INNER JOIN tbProduto B ON A.Cod_produto = B.Cod_produto 
    INNER JOIN tbProdutoRef C ON B.Cod_Produto = C.Cod_Produto
    WHERE A.Status = 'E' 
      AND A.Cod_local_estoque = '{$localSelecionado}'
	  AND A.Cod_filial_estoque = {$filial}
      AND A.Cod_produto BETWEEN '20000' AND '39999' 
      AND A.Cod_Produto NOT IN ('20007', '20041', '20000', '20042')
    GROUP BY B.Cod_produto, B.Desc_produto_est
),
Vendas8Dias AS (
    SELECT 
        SI.COD_PRODUTO,
        SUM(SI.Valor_total) / NULLIF(SUM(SI.QTDE_PRI),0) AS MediaVenda
    FROM TBSAIDAS S
    INNER JOIN TBSAIDASITEM SI 
        ON S.CHAVE_FATO = SI.CHAVE_FATO 
       AND SI.NUM_SUBITEM = 0
    INNER JOIN TBTIPOMVESTOQUE D 
        ON S.COD_TIPO_MV = D.COD_TIPO_MV 
       AND S.COD_DOCTO = D.COD_DOCTO
    WHERE (D.Perfil_tmv IN ('VDA0301') OR S.Cod_tipo_mv IN ('T186','T570','T571','X520'))
      AND S.DATA_MOVTO BETWEEN DATEADD(DAY,-7,GETDATE()) AND GETDATE()
	  AND S.Cod_filial = {$filial}
    GROUP BY SI.COD_PRODUTO
),
UltimaNF AS (
    SELECT 
        SI.COD_PRODUTO,
        SI.Valor_unitario,
        ROW_NUMBER() OVER (PARTITION BY SI.COD_PRODUTO ORDER BY S.DATA_MOVTO DESC) AS rn
    FROM TBSAIDASITEM SI
    INNER JOIN TBSAIDAS S ON SI.CHAVE_FATO = S.CHAVE_FATO
    WHERE SI.NUM_SUBITEM = 0
      AND S.COD_DOCTO = 'NE'
	  AND S.COD_TIPO_MV NOT IN ('T530')
	  AND S.Cod_filial = {$filial}
)
SELECT 
    E.Cod_produto AS PRODUTO,
    E.Produto AS DESCRICAO,
    E.QTD_REGISTROS,
    E.Peso_Liquido_Total AS PESO_BRUTO,
    COALESCE(V.MediaVenda, U.Valor_unitario) AS PRECOV1,
    CASE 
        WHEN V.MediaVenda IS NOT NULL THEN 'MÉDIA'
        WHEN U.Valor_unitario IS NOT NULL THEN 'NE'
        ELSE 'SEM_PRECO'
    END AS ORIGEM_PRECO,
    E.COD_DIVISAO1,
    E.COD_DIVISAO2,
    E.TIPO_TEMPERATURA
FROM Estoque E
LEFT JOIN Vendas8Dias V ON E.Cod_produto = V.COD_PRODUTO
LEFT JOIN UltimaNF U ON E.Cod_produto = U.COD_PRODUTO AND U.rn = 1;
";


        try {
            $stmt = $pdoS->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<h4>Relatório Gerado</h4>";

            $materiaPrima = $produtoAcabadoResfriado = $produtoAcabadoCongelado = [];

            foreach ($rows as $row) {
                if (in_array($row['COD_DIVISAO1'], [20]) && in_array($row['COD_DIVISAO2'], [20, 21, 314])) {
                    $materiaPrima[] = $row;
                } elseif ($row['TIPO_TEMPERATURA'] == 'R') {
                    $produtoAcabadoResfriado[] = $row;
                } elseif ($row['TIPO_TEMPERATURA'] == 'C') {
                    $produtoAcabadoCongelado[] = $row;
                }
            }
    ?>
<div class="container">
    <h2 class="text-center">RELATÓRIO DE ESTOQUE GERAL VALORIZADO</h2>
    <button class="btn btn-success no-print" onclick="window.print()">Imprimir Relatório</button>

    <!-- Matéria Prima -->
    <?php
    $totalPesoBrutoMP = $totalPrecoTotalMP = $totalRegistrosMP = 0;
    ?>
    <div class="table-container">
        <h3 class="materia-prima">Matéria Prima</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Caixas</th>
                    <th>Peso Bruto</th>
                    <th>Preço Unitário</th>
                    <th>Preço Total</th>
                    <th>Origem do Preço</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materiaPrima as $row):
                    $precoTotal = $row['PRECOV1'] * $row['PESO_BRUTO'];
                    $totalPesoBrutoMP += $row['PESO_BRUTO'];
                    $totalPrecoTotalMP += $precoTotal;
                    $totalRegistrosMP += $row['QTD_REGISTROS'];
                ?>
                <tr>
                    <td><?= $row['PRODUTO'] ?></td>
                    <td><?= $row['DESCRICAO'] ?></td>
                    <td><?= $row['QTD_REGISTROS'] ?></td>
                    <td><?= number_format($row['PESO_BRUTO'], 2, ',', '.') ?></td>
                    <td><?= number_format($row['PRECOV1'], 2, ',', '.') ?></td>
                    <td><?= number_format($precoTotal, 2, ',', '.') ?></td>
                    <td><?= $row['ORIGEM_PRECO'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td><?= $totalRegistrosMP ?></td>
                    <td><?= number_format($totalPesoBrutoMP, 2, ',', '.') ?></td>
                    <td></td>
                    <td><?= number_format($totalPrecoTotalMP, 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Produto Acabado Resfriado -->
    <?php
    $totalPesoBrutoPA_R = $totalPrecoTotalPA_R = $totalRegistrosPA_R = 0;
    ?>
    <div class="table-container">
        <h3 class="produto-resfriado">Produto Acabado Resfriado</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Caixas</th>
                    <th>Peso Bruto</th>
                    <th>Preço Unitário</th>
                    <th>Preço Total</th>
                    <th>Origem do Preço</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtoAcabadoResfriado as $row):
                    $precoTotal = $row['PRECOV1'] * $row['PESO_BRUTO'];
                    $totalPesoBrutoPA_R += $row['PESO_BRUTO'];
                    $totalPrecoTotalPA_R += $precoTotal;
                    $totalRegistrosPA_R += $row['QTD_REGISTROS'];
                ?>
                <tr>
                    <td><?= $row['PRODUTO'] ?></td>
                    <td><?= $row['DESCRICAO'] ?></td>
                    <td><?= $row['QTD_REGISTROS'] ?></td>
                    <td><?= number_format($row['PESO_BRUTO'], 2, ',', '.') ?></td>
                    <td><?= number_format($row['PRECOV1'], 2, ',', '.') ?></td>
                    <td><?= number_format($precoTotal, 2, ',', '.') ?></td>
                    <td><?= $row['ORIGEM_PRECO'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td><?= $totalRegistrosPA_R ?></td>
                    <td><?= number_format($totalPesoBrutoPA_R, 2, ',', '.') ?></td>
                    <td></td>
                    <td><?= number_format($totalPrecoTotalPA_R, 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Produto Acabado Congelado -->
    <?php
    $totalPesoBrutoPA_C = $totalPrecoTotalPA_C = $totalRegistrosPA_C = 0;
    ?>
    <div class="table-container">
        <h3 class="produto-congelado">Produto Acabado Congelado</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Caixas</th>
                    <th>Peso Bruto</th>
                    <th>Preço Unitário</th>
                    <th>Preço Total</th>
                    <th>Origem do Preço</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtoAcabadoCongelado as $row):
                    $precoTotal = $row['PRECOV1'] * $row['PESO_BRUTO'];
                    $totalPesoBrutoPA_C += $row['PESO_BRUTO'];
                    $totalPrecoTotalPA_C += $precoTotal;
                    $totalRegistrosPA_C += $row['QTD_REGISTROS'];
                ?>
                <tr>
                    <td><?= $row['PRODUTO'] ?></td>
                    <td><?= $row['DESCRICAO'] ?></td>
                    <td><?= $row['QTD_REGISTROS'] ?></td>
                    <td><?= number_format($row['PESO_BRUTO'], 2, ',', '.') ?></td>
                    <td><?= number_format($row['PRECOV1'], 2, ',', '.') ?></td>
                    <td><?= number_format($precoTotal, 2, ',', '.') ?></td>
                    <td><?= $row['ORIGEM_PRECO'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td><?= $totalRegistrosPA_C ?></td>
                    <td><?= number_format($totalPesoBrutoPA_C, 2, ',', '.') ?></td>
                    <td></td>
                    <td><?= number_format($totalPrecoTotalPA_C, 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- TOTAL GERAL -->
    <?php
    $totalGeralCaixas = $totalRegistrosMP + $totalRegistrosPA_R + $totalRegistrosPA_C;
    $totalGeralPeso = $totalPesoBrutoMP + $totalPesoBrutoPA_R + $totalPesoBrutoPA_C;
    $totalGeralValor = $totalPrecoTotalMP + $totalPrecoTotalPA_R + $totalPrecoTotalPA_C;
    ?>
    <div class="table-container">
        <h3 class="text-center bg-dark text-white p-2" style="border-radius: 10px;">Total Geral do Estoque</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Total de Caixas</th>
                    <th>Total de Peso Bruto</th>
                    <th>Total Valorizado (R$)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= $totalGeralCaixas ?></strong></td>
                    <td><strong><?= number_format($totalGeralPeso, 2, ',', '.') ?></strong></td>
                    <td><strong><?= number_format($totalGeralValor, 2, ',', '.') ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


    <?php
        } catch (PDOException $e) {
            echo "<p>Erro ao consultar: " . $e->getMessage() . "</p>";
        }
    endif;
    ?>
</div>
