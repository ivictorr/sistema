<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default';
$filterFile = __DIR__ . "/filters/user_{$userId}_estoque.txt";

// Funções para salvar, carregar e limpar filtros
function saveFilters($filters, $filePath)
{
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    file_put_contents($filePath, json_encode($filters));
}

function loadFilters($filePath)
{
    if (file_exists($filePath)) {
        return json_decode(file_get_contents($filePath), true);
    }
    return [];
}

function clearFilters($filePath)
{
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Carregar os filtros salvos
$savedFilters = loadFilters($filterFile);

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'produto' => $_POST['produto'] ?? [],
        'producao_de' => $_POST['producao_de'] ?? '',
        'producao_ate' => $_POST['producao_ate'] ?? '',
        'condicao' => $_POST['condicaoProduto'] ?? '',
        'localestoque' => $_POST['localEstoque'] ?? '',
    ];

    if (isset($_POST['salvarFiltro'])) {
        saveFilters($filters, $filterFile);
        $savedFilters = $filters;
    }

    if (isset($_POST['limparFiltro'])) {
        clearFilters($filterFile);
        $savedFilters = [];
    }
}
$filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; // fallback para 100
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<style>
    .table-container {
        padding: 20px;
    }

    .table {
        margin-top: 20px;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table thead {
        background-color: #007bff;
        color: white;
    }

    .table tbody tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .table tbody tr:hover {
        background-color: #e9ecef;
    }

    .filter-summary {
        margin-top: 20px;
        padding: 10px;
        background-color: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 10px;
        font-size: 14px;
        text-align: left;
    }

    .filter-summary p {
        margin: 5px 0;
    }

    .filter-summary strong {
        color: #0d47a1;
        font-weight: bold;
    }

    #productModal .modal-dialog {
        width: 90%;
        /* Aumenta a largura do modal */
        max-width: 1200px;
        /* Define um limite para a largura máxima */
    }

    #productModal .modal-body {
        max-height: 70vh;
        /* Define uma altura máxima para a área de conteúdo */
        overflow-y: auto;
        /* Habilita a rolagem vertical */
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<div class="container mt-5">
    <br>
    <div class="panel panel-primary no-print mt-5" style="margin-top: 30px">
        <div class="panel-heading text-center">
            <h3>RELATORIO DE ESTOQUE x RESERVADO</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
            <!-- Seleção de Filial -->
            <div class="form-group row">
                <label for="produto-select" class="col-sm-2 col-form-label text-right">Produto:</label>
                <div class="col-sm-10">
                    <select id="produto-select" name="produto[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT * FROM tbProduto WHERE Cod_produto BETWEEN '20000' AND '39999'");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_produto'], $savedFilters['produto'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_produto']}' {$selected}>{$r['Cod_produto']} - {$r['Desc_produto_est']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label for="produto-select" class="col-sm-2 col-form-label text-right">Local de Estoque:</label>
                <div class="col-sm-10">
                    <select id="localEstoque" name="localEstoque" class="form-control">
                        <?php
                        // Recuperar os locais de estoque do banco de dados
                        $stmtLocais = $pdoS->query("SELECT DISTINCT Desc_local,Cod_local FROM tbLocalEstoque WHERE Cod_filial = {$filial} ORDER BY Desc_local");
                        while ($local = $stmtLocais->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . htmlspecialchars($local['Cod_local']) . '">' . htmlspecialchars($local['Desc_local']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label text-right">Data de Produção:</label>
                <div class="col-sm-5">
                    <input type="date" name="producao_de" class="form-control" value="<?= $savedFilters['producao_de'] ?? '' ?>">
                </div>
                <div class="col-sm-5">
                    <input type="date" name="producao_ate" class="form-control" value="<?= $savedFilters['producao_ate'] ?? '' ?>">
                </div>
            </div>
            <div class="form-group row">
                <label for="condicaoProduto" class="col-sm-2 col-form-label text-right">Condição do Produto:</label>
                <div class="col-sm-10">
                    <select id="condicaoProduto" name="condicaoProduto" class="form-control">
                        <option value="">Todos</option>
                        <option value="R" <?= ($savedFilters['condicao'] ?? '') === 'R' ? 'selected' : '' ?>>Resfriado</option>
                        <option value="C" <?= ($savedFilters['condicao'] ?? '') === 'C' ? 'selected' : '' ?>>Congelado</option>
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
    
    
        $produto = $_POST['produto'] ?? [];
        $producao_de = $_POST['producao_de'] ?? '';
        $producao_ate = $_POST['producao_ate'] ?? '';
        $condicaoProduto = $_POST['condicaoProduto'] ?? '';
        $localEstoque = $_POST['localEstoque'] ?? '';

    // Salva o local de estoque na sessão
    $_SESSION['local_estoque'] = $localEstoque;

        $sql = "
            SELECT 
    E.Cod_produto AS PRODUTO,
    E.Produto AS DESCRICAO,
    E.Peso_Liquido_Total AS PESO_BRUTO,
    E.PRECO AS PRECOV1,
    R.PESO_RESERV AS RESERVADO,
    C.CARREGADO AS CARREGADO,
    (ISNULL(E.Peso_Liquido_Total, 0) - ISNULL(R.PESO_RESERV, 0) + ISNULL(C.CARREGADO, 0)) AS DISPONIVEL
            FROM (
                SELECT
                    MAX(A.Cod_produto) AS Cod_produto,
                    B.Desc_produto_est AS Produto, 
                    SUM(A.Peso_liquido) AS Peso_Liquido_Total, 
                    SUM(A.Peso_bruto) AS Peso_Bruto_Total,
                        (
        SELECT TOP 1 TBP.Preco_v1
        FROM tbListaPrecoItem TBP
        WHERE TBP.Cod_produto = B.Cod_produto AND Cod_lista = '2000'
    ) AS PRECO
                FROM tbVolume A 
                INNER JOIN tbProduto B ON A.Cod_produto = B.Cod_produto 
                INNER JOIN tbProdutoRef C ON B.Cod_Produto = C.Cod_Produto
                WHERE A.Status = 'E' 
                AND A.Cod_filial_estoque = {$filial}
                AND A.Cod_produto BETWEEN '20000' AND '39999' 
                AND A.Cod_Produto NOT IN ('20007','20041','20000','20042')
";


        if (!empty($produto)) {
            $sql .= " AND A.Cod_produto IN ('" . implode("','", $produto) . "')";
        }
        if (!empty($producao_de)) {
            $sql .= " AND A.Data_producao >= '$producao_de'";
        }
        if (!empty($producao_ate)) {
            $sql .= " AND A.Data_producao <= '$producao_ate'";
        }
        if (!empty($condicaoProduto)) {
            $sql .= " AND C.Tipo_temperatura = '$condicaoProduto'";
        }
        if (!empty($localEstoque)) {
            $sql .= " AND A.Cod_local_estoque = '$localEstoque'";
        }

        $sql .= "
                GROUP BY B.Cod_produto, B.Desc_produto_est
            ) E
LEFT JOIN (
    SELECT 
        TBI.Cod_produto,
        SUM(TBI.Qtde_pri) AS PESO_RESERV
    FROM tbSaidas PVE
    LEFT JOIN tbSaidas ROS
        ON ROS.Chave_fato_orig_un = PVE.Chave_fato
        AND ROS.COD_DOCTO IN ('ROS','RTS','RAE')
        AND ROS.Cod_filial = PVE.Cod_filial
    INNER JOIN tbSaidasItem TBI
        ON TBI.Chave_fato = PVE.Chave_fato
    WHERE 
        PVE.COD_DOCTO IN ('PVE','PAE')
        AND PVE.Cod_filial = {$filial}
        AND PVE.Status <> 'C'
        AND TBI.Cod_local = {$localEstoque}
        AND PVE.Cod_tipo_mv IN ('T500', 'T700', 'E500','M500','M501','M502','M503','T800')
        AND PVE.Data_v2 BETWEEN DATEADD(DAY, -15, GETDATE()) AND DATEADD(YEAR, 2, GETDATE())

        -- Filtros de exclusão
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
    GROUP BY TBI.Cod_produto
) AS R ON E.Cod_produto = R.Cod_produto
LEFT JOIN (
SELECT 
    PVEI.COD_PRODUTO,
    SUM(PVEI.QTDE_PRI) AS CARREGADO

FROM TBSAIDAS ROS
INNER JOIN TBSAIDASITEM PVEI ON ROS.CHAVE_FATO = PVEI.CHAVE_FATO
LEFT JOIN TBSAIDAS NE ON ROS.Chave_fato = NE.Chave_fato_orig_un
LEFT JOIN TBSAIDASITEM PAVI ON PAVI.Chave_fato_orig = ROS.Chave_fato
    AND PAVI.Cod_produto = PVEI.Cod_produto

WHERE 
    ROS.Cod_tipo_mv IN ('T510','T710','E510')
    AND ROS.COD_DOCTO IN ('ROS','RTS')
    AND ROS.Cod_filial = {$filial}
    AND ROS.STATUS <> 'C'
    AND PVEI.Cod_local = {$localEstoque}
    AND ROS.Data_v1 BETWEEN DATEADD(DAY, -15, GETDATE()) AND DATEADD(YEAR, 2, GETDATE())

    -- Exclui se tiver NE vinculada ao ROS
    AND NE.Chave_fato IS NULL

    -- Exclui se tiver PAV do tipo T524 vinculado ao ROS
    AND NOT EXISTS (
        SELECT 1
        FROM TBSAIDAS PAVX
        WHERE PAVX.CHAVE_FATO_ORIG_UN = ROS.CHAVE_FATO
        AND PAVX.COD_DOCTO = 'PAV'
        AND PAVX.Cod_tipo_mv = 'T524'
        AND PAVX.Cod_filial = ROS.Cod_filial
    )

    -- Exclui se tiver qualquer item (PAVS) com chave_fato_orig = ROS
    AND NOT EXISTS (
        SELECT 1
        FROM TBSAIDASITEM PAVS
        WHERE PAVS.Chave_fato_orig = ROS.CHAVE_FATO
    )

    -- Exclui se houver PTC vinculado ao ROS
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidasItem TBI_PTC
        INNER JOIN tbSaidas PTC
            ON PTC.Chave_fato = TBI_PTC.Chave_fato
            AND PTC.COD_DOCTO = 'PTC'
            AND PTC.Cod_filial = ROS.Cod_filial
        WHERE TBI_PTC.Chave_fato_orig = ROS.Chave_fato
    )

    -- Exclui se houver PTO vinculado ao ROS
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidasItem TBI_PTO
        INNER JOIN tbSaidas PTO
            ON PTO.Chave_fato = TBI_PTO.Chave_fato
            AND PTO.COD_DOCTO = 'PTO'
            AND PTO.Cod_filial = ROS.Cod_filial
        WHERE TBI_PTO.Chave_fato_orig = ROS.Chave_fato
    )

    -- Exclui se houver NE/NEE diretamente do PVE (ROS chave_fato_orig_un)
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidas NE_PVE
        WHERE NE_PVE.Chave_fato_orig_un = ROS.Chave_fato
          AND NE_PVE.COD_DOCTO IN ('NE', 'NEE')
          AND NE_PVE.Cod_filial = ROS.Cod_filial
    )

    -- Exclui se houver PAV do tipo 'PAV' diretamente do PVE
    AND NOT EXISTS (
        SELECT 1
        FROM tbSaidas PAV
        WHERE PAV.Chave_fato_orig_un = ROS.Chave_fato
          AND PAV.COD_DOCTO = 'PAV'
          AND PAV.Cod_filial = ROS.Cod_filial
    )

GROUP BY 
    PVEI.COD_PRODUTO

HAVING SUM(PVEI.QTDE_PRI) <> 0
    ) AS C ON E.Cod_produto = C.Cod_produto
";

        try {
            $stmt = $pdoS->query($sql);

            // ============================
            // NOVO: buscar 'proj_qtde' por produto no MySQL (último snapshot da filial)
            // ============================
            $projMap = [];
            try {
                $ps = $pdoM->prepare("
                    SELECT s1.cod_produto, s1.proj_qtde
                    FROM simulacao_desossa s1
                    INNER JOIN (
                        SELECT cod_produto, MAX(created_at) AS max_created
                        FROM simulacao_desossa
                        WHERE filial = :filial
                        GROUP BY cod_produto
                    ) s2
                      ON s2.cod_produto = s1.cod_produto
                     AND s1.created_at = s2.max_created
                    WHERE s1.filial = :filial
                ");
                $ps->execute([':filial' => $filial]);
                while ($pr = $ps->fetch(PDO::FETCH_ASSOC)) {
                    $projMap[$pr['cod_produto']] = (float)$pr['proj_qtde'];
                }
            } catch (PDOException $e) {
                // opcional: error_log('Erro projMap: '.$e->getMessage());
            }

            echo "<h4>Relatório Gerado</h4>";
    ?>
            <table id="tabela" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Produto</th>
                        <th>Estoque</th>
                        <th>Previsto</th> <!-- NOVO -->
                        <th>Reservado</th>
                        <th>Carregado</th>
                        <th>Disponível</th>
                        <th>Preço</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Variáveis para acumular os totais
                    $totalPesoBruto = 0;
                    $totalReservado = 0;
                    $totalCarregado = 0;
                    $totalDisponivel = 0;
                    $totalPreco = 0;
                    $totalPrevisto = 0; // NOVO

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Calcular o preço total por linha
                        $precoTotal = $row['PRECOV1'] * $row['PESO_BRUTO'];

                        // NOVO: previsto (proj_qtde) a partir do MySQL
                        $previsto = $projMap[$row['PRODUTO']] ?? 0.0;

                        // Acumular os valores nos totais
                        $totalPesoBruto += $row['PESO_BRUTO'];
                        $totalReservado += $row['RESERVADO'];
                        $totalCarregado += $row['CARREGADO'];
                        $totalDisponivel += $row['DISPONIVEL'];
                        $totalPreco += $precoTotal;
                        $totalPrevisto += $previsto; // NOVO

                        // Exibir a linha da tabela
                        echo "<tr class='product-row' data-product-id='{$row['PRODUTO']}'>
                <td>{$row['PRODUTO']}</td>
                <td>{$row['DESCRICAO']}</td>
                <td data-order='{$row['PESO_BRUTO']}'>" . number_format($row['PESO_BRUTO'], 2, ',', '.') . "</td>
                <td data-order='{$previsto}'>" . number_format($previsto, 3, ',', '.') . "</td> <!-- NOVO -->
                <td data-order='{$row['RESERVADO']}'>" . number_format($row['RESERVADO'], 2, ',', '.') . "</td>
                <td data-order='{$row['CARREGADO']}'>" . number_format($row['CARREGADO'], 2, ',', '.') . "</td>
                <td data-order='{$row['DISPONIVEL']}'>" . number_format($row['DISPONIVEL'], 2, ',', '.') . "</td>
                <td data-order='{$precoTotal}'>" . number_format($precoTotal, 2, ',', '.') . "</td>
                <td>
                <div class='btn-group' role='group' style='display: flex; gap: 5px;'>
                    <button class='btn btn-sm btn-primary btn-detalhe open-modal' data-cod-produto='{$row['PRODUTO']}' data-toggle='modal' data-target='#productModal'>
                       <i class='glyphicon glyphicon-search'></i>
                    </button>
                 
                    <button class='btn btn-sm btn-info btn-estoque' data-cod-produto='{$row['PRODUTO']}' data-toggle='modal' data-target='#estoqueModal'>
                        <i class='glyphicon glyphicon-list-alt'></i>
                    </button>
                    </div>
                </td>
            </tr>";
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong><?php echo number_format($totalPesoBruto, 2, ',', '.'); ?></strong></td>
                        <td><strong><?php echo number_format($totalPrevisto, 3, ',', '.'); ?></strong></td> <!-- NOVO -->
                        <td><strong><?php echo number_format($totalReservado, 2, ',', '.'); ?></strong></td>
                        <td><strong><?php echo number_format($totalCarregado, 2, ',', '.'); ?></strong></td>
                        <td><strong><?php echo number_format($totalDisponivel, 2, ',', '.'); ?></strong></td>
                        <td><strong><?php echo number_format($totalPreco, 2, ',', '.'); ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <script>
                $(document).ready(function() {
                    $('#tabela').DataTable({
                        dom: 'Bfrtip',
                        paging: false,
                        buttons: [{
                                extend: 'print',
                                text: 'Imprimir',
                                footer: true, // Inclui o rodapé na impressão
                                exportOptions: {
                                    columns: ':not(:last-child)' // Exclui a última coluna (Ações) da impressão
                                },
                                customize: function(win) {
                                    // Adiciona estilo ao rodapé para que ele apareça na impressão
                                    $(win.document.body).find('tfoot').css('display', 'table-footer-group');
                                }
                            },
                            'csv',
                            'excel',
                            'pdf'
                        ],
                        order: [
                            [3, 'desc'] // OBS.: agora 3 é "Previsto"; se quiser manter 'Reservado', troque para [4,'desc'].
                        ],
                    });
                });
            </script>

    <?php
        } catch (PDOException $e) {
            echo "<p>Erro ao conectar ou consultar o banco de dados: " . $e->getMessage() . "</p>";
        }
    endif;
    ?>
</div>
<!-- Modal de Estoque -->
<div id="estoqueModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="estoqueModalLabel">
    <div class="modal-dialog" role="document" style="width: 90%; max-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="estoqueModalLabel">Detalhes do Estoque</h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-striped text-center">
                    <thead>
                        <tr>
                            <th class="text-center">Codigo</th>
                            <th class="text-center">Nomenclatura</th>
                            <th class="text-center">Data Produção</th>
                            <th class="text-center">Validade</th>
                            <th class="text-center">Caixas</th>
                            <th class="text-center">Peso Líquido</th>
                            <th class="text-center">Peso Bruto</th>
                        </tr>
                    </thead>
                    <tbody id="estoqueDetails">
                        <!-- Preenchido via AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer text-center">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal -->
<div id="productModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="productModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="productModalLabel">Detalhes do Produto</h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Reservado</th>
                            <th>TMV</th>
                            <th>N°Pedido</th>
                            <th>Preço</th>
                            <th>Cliente</th>
                            <th>Data Embarque</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody id="productDetails">
                        <!-- Detalhes do produto serão preenchidos aqui -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).on('click', '.open-modal', function() {
        var codProduto = $(this).data('cod-produto');

        $.ajax({
            url: 'detalhes_pedidos.php',
            type: 'GET',
            data: {
                cod_produto: codProduto
            },
            success: function(response) {
                console.log('Resposta recebida:', response);
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                console.log('Dados processados:', data);
                var tableContent = '';

                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(function(item) {
                        tableContent += `
                        <tr>
                            <td>${item.COD_PRODUTO.trim()}</td>
                            <td>${parseFloat(item.PESO_RESERV).toLocaleString('pt-BR', { minimumFractionDigits: 3 })}</td>
                            <td>${item.Cod_tipo_mv}</td>
                            <td>${item.Num_docto}</td>
                            <td>${item.Preco}</td>
                            <td>${item.Cliente}</td>
                            <td>${item.Data_v1}</td>
                            <td>${item.Cod_usuario}</td>
                        </tr>`;
                    });
                } else {
                    tableContent = '<tr><td colspan="5">Nenhum detalhe encontrado para este produto.</td></tr>';
                }

                $('#productDetails').html(tableContent);
                $('#productModal').modal('show'); // Mostra o modal
            },
            error: function() {
                $('#productDetails').html('<tr><td colspan="5">Erro ao carregar os detalhes do produto.</td></tr>');
            }
        });
    });
    
$(document).on('click', '.btn-estoque', function () {
    var codProduto = $(this).data('cod-produto');

    $.ajax({
        url: 'detalhes_estoque.php',
        type: 'GET',
        data: {
            cod_produto: codProduto
        },
        success: function (response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            var tableContent = '';

            if (Array.isArray(data.dados) && data.dados.length > 0) {
                data.dados.forEach(function (item) {
                    tableContent += `
                        <tr>
                            <td>${item.Produto}</td>
                            <td>${item.Nome_Produto}</td>
                            <td>${item.Data_Producao}</td>
                            <td>${item.Data_Validade}</td>
                            <td>${item.Total_Caixas}</td>
                            <td>${item.Peso_Liquido}</td>
                            <td>${item.Peso_Bruto}</td>
                        </tr>`;
                });

                // Linha com resumo total
                tableContent += `
                    <tr style="font-weight: bold; background: #f2f2f2;">
                        <td colspan="4">Totais:</td>
                        <td>${data.resumo.total_caixas}</td>
                        <td>${data.resumo.peso_liquido_total}</td>
                        <td>${data.resumo.peso_bruto_total}</td>
                    </tr>
                `;
            } else {
                tableContent = '<tr><td colspan="5" class="text-center">Nenhum detalhe de estoque encontrado.</td></tr>';
            }

            $('#estoqueDetails').html(tableContent);
            $('#estoqueModal').modal('show');
        },
        error: function () {
            $('#estoqueDetails').html('<tr><td colspan="5" class="text-center">Erro ao carregar os dados do estoque.</td></tr>');
        }
    });
});

</script>
