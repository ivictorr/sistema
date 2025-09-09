<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default';
$filename = "relatorioProducao";
$filterFile = __DIR__ . "/filters/user_{$userId}_{$filename}.txt";

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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Produção</title>

    <!-- Font Awesome (para ícones nos botões) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

    <style>
        /* Estilo da tabela mais compacta e legível */
        #tabela {
            font-size: 12px;
        }
        #tabela thead th {
            background: #2c3e50;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            font-size: 13px;
            white-space: nowrap;
        }
        #tabela tbody td {
            vertical-align: middle;
            padding: 6px 8px;
            white-space: nowrap;
        }
        #tabela tbody tr:hover {
            background-color: #f0f8ff !important;
        }
        #tabela, #tabela th, #tabela td {
            border-color: #dcdcdc !important;
        }
        .btn {
            border-radius: 4px !important;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-5">
    <div class="panel panel-default">
        <div class="panel-heading text-center" style="background:#337ab7; color:white; font-weight:bold; font-size:16px;">
            <i class="fa fa-bar-chart"></i> Relatório de Produção
        </div>
        <div class="panel-body">

            <!-- Formulário de Filtros -->
            <form method="POST" action="" class="form-horizontal">
                <div class="form-group">
                    <label for="produto-select" class="col-sm-2 control-label">Produto:</label>
                    <div class="col-sm-8">
                        <select id="produto-select" name="produto[]" 
                            class="selectpicker form-control" 
                            multiple data-live-search="true" 
                            title="Selecione uma ou mais opções">
                            <?php
                            // Busca produtos do banco
                            $res = $pdoS->query("SELECT * FROM tbProduto WHERE Cod_produto BETWEEN '20000' AND '39999'");
                            while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                                $selected = in_array($r['Cod_produto'], $savedFilters['produto'] ?? []) ? 'selected' : '';
                                echo "<option value='{$r['Cod_produto']}' {$selected}>{$r['Cod_produto']} - {$r['Desc_produto_est']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-sm-2 text-right">
                        <button type="submit" name="gerarRelatorio" class="btn btn-primary btn-block">
                            <i class="fa fa-search"></i> Gerar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Relatório -->
            <?php if (isset($_POST['gerarRelatorio'])): ?>
                <?php
                $produto = $_POST['produto'] ?? [];

                $sql = "SELECT 
                    A.COD_PRODUTO AS Codigo,
                    A.Desc_produto_est,
                    CONCAT(TR.Cod_grupo_rend, ' - ', TR.Desc_grupo_rend) AS Grupo_Rendimento,
                    CONCAT(A.Cod_divisao1, ' - ', TP1.Desc_divisao1) AS Secao,
                    CONCAT(A.Cod_divisao2, ' - ', TP2.Desc_divisao2) AS Grupo,
                    CONCAT(A.Cod_divisao3, ' - ', TP3.Desc_divisao3) AS Sub_Grupo,
                    A.Cod_unidade_pri,
                    A.Cod_unidade_aux,
                    A.Desc_produto_nf,
                    A.Tipo_produto,
                    A.Perc_desossa,
                    TBR.Prazo_validade,
                    TBR.Prazo_maturacao,
                    TBR.Prazo_congelamento,
                    TBR.Temperatura,
                    TBR.Peso_minimo,
                    TBR.Peso_maximo,
                    TBR.Tara_externa,
                    TBR.Tara_interna
                FROM TBPRODUTO A 
                LEFT JOIN tbGrupoRend TR ON A.Cod_grupo_rend = TR.Cod_grupo_rend
                LEFT JOIN tbDivisao1Prod TP1 ON A.Cod_divisao1 = TP1.Cod_divisao1
                LEFT JOIN tbDivisao2Prod TP2 ON A.Cod_divisao2 = TP2.Cod_divisao2
                LEFT JOIN tbDivisao3Prod TP3 ON A.Cod_divisao3 = TP3.Cod_divisao3
                INNER JOIN tbProdutoRef TBR ON TBR.Cod_produto = A.Cod_produto
                WHERE A.Cod_produto BETWEEN '20000' AND '39999'
                AND A.COD_PRODUTO NOT IN ('23633', '30220', '30226', '20000', '20007')";

                if (!empty($produto)) {
                    $sql .= " AND A.COD_PRODUTO IN ('" . implode("','", $produto) . "')";
                }

                try {
                    $stmt = $pdoS->query($sql);
                ?>
                <div class="table-responsive">
                    <table id="tabela" class="table table-striped table-hover table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Produto</th>
                                <th>Grupo Rendimento</th>
                                <th>Seção</th>
                                <th>Grupo</th>
                                <th>Subgrupo</th>
                                <th>Unidade Primária</th>
                                <th>Unidade Auxiliar</th>
                                <th>Validade</th>
                                <th>Maturação</th>
                                <th>Congelamento</th>
                                <th>Temperatura</th>
                                <th class="text-right">Peso Mínimo</th>
                                <th class="text-right">Peso Máximo</th>
                                <th class="text-right">Tara Externa</th>
                                <th class="text-right">Tara Interna</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?= $row['Codigo'] ?></td>
                                    <td class="text-left"><?= $row['Desc_produto_est'] ?></td>
                                    <td><?= $row['Grupo_Rendimento'] ?></td>
                                    <td><?= $row['Secao'] ?></td>
                                    <td><?= $row['Grupo'] ?></td>
                                    <td><?= $row['Sub_Grupo'] ?></td>
                                    <td><?= $row['Cod_unidade_pri'] ?></td>
                                    <td><?= $row['Cod_unidade_aux'] ?></td>
                                    <td><?= $row['Prazo_validade'] ?></td>
                                    <td><?= $row['Prazo_maturacao'] ?></td>
                                    <td><?= $row['Prazo_congelamento'] ?></td>
                                    <td><?= $row['Temperatura'] ?></td>
                                    <td class="text-right"><?= number_format($row['Peso_minimo'], 2, ',', '.') ?></td>
                                    <td class="text-right"><?= number_format($row['Peso_maximo'], 2, ',', '.') ?></td>
                                    <td class="text-right"><?= number_format($row['Tara_externa'], 2, ',', '.') ?></td>
                                    <td class="text-right"><?= number_format($row['Tara_interna'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php } catch (PDOException $e) {
                    echo "<p class='text-danger'>Erro ao consultar o banco de dados: " . $e->getMessage() . "</p>";
                } ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(document).ready(function () {
    $('#tabela').DataTable({
        paging: false,
        autoWidth: false,
        scrollX: true,
        scrollY: "500px",
        scrollCollapse: true,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-success btn-sm' },
            { extend: 'print', text: '<i class="fa fa-print"></i> Imprimir', className: 'btn btn-primary btn-sm' }
        ],
        language: {
            search: "🔎 Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Nenhum registro encontrado",
            infoFiltered: "(filtrado de _MAX_ registros no total)",
            zeroRecords: "Nenhum dado correspondente encontrado"
        }
    });
});
</script>
</body>
</html>
