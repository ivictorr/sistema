<?php

// Captura a data do filtro
$dataFiltro = isset($_GET['data_abate']) ? $_GET['data_abate'] : date('Y-m-d');

// Consulta SQL para buscar os dados agrupados por lote no MySQL (logs_monitoramento)
$stmt = $pdoM->prepare("
    SELECT lote, COUNT(*) as total, id_sisbov
    FROM logs_monitoramento 
    WHERE data_abate = ?
    GROUP BY lote
    ORDER BY lote ASC
");
$stmt->execute([$dataFiltro]);
$lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de Monitoramento</title>
    <style>
        .panel-heading {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
            cursor: pointer;
        }
        .hidden-row {
            display: none;
            transition: all 0.3s ease-in-out;
        }
        .btn-primary {
            background-color: #337ab7;
            border-color: #2e6da4;
        }
        .btn-primary:hover {
            background-color: #286090;
        }
        .input-date {
            width: 220px;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="panel panel-primary">
        <div class="panel-heading">Relatório de Monitoramento</div>
        <div class="panel-body">

            <!-- Filtro por Data -->
            <form method="GET" action="" class="form-inline text-center">
                <input type="hidden" name="relatorioMonitoramento" value=""> <!-- Garante que 'relatorioMonitoramento' esteja na URL -->
                <div class="form-group">
                    <label for="data_abate">Filtrar por Data de Abate:</label>
                    <input type="date" id="data_abate" name="data_abate" class="form-control input-date" value="<?= $dataFiltro ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>

            <!-- Tabela Principal -->
            <table class="table table-bordered table-hover table-striped">
                <thead class="bg-primary">
                    <tr>
                        <th>Lote</th>
                        <th>Romaneio</th>
                        <th>Pecuarista</th>
                        <th>Fazenda</th>
                        <th>Total de Registros</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lotes as $lote): 
                        $stmtDetalhes = $pdoS->prepare("
                            SELECT 
                                s.num_docto AS romaneio, 
                                RTRIM(CG.NOME_CADASTRO) AS PRODUTOR,
                                PROP.NOME AS NOME_FAZENDA
                            FROM tbromaneioabate r
                            INNER JOIN tbentradas s ON r.chave_fato = s.chave_fato
                            INNER JOIN TBCADASTROGERAL CG ON CG.COD_CADASTRO = S.COD_CLI_FOR
                            INNER JOIN TBPROPRIEDADE PROP ON PROP.COD_PROPRIEDADE = S.COD_PROPRIEDADE 
                                AND PROP.COD_PECUARISTA = CG.COD_CADASTRO
                            WHERE r.id_sisbov = ?
                        ");
                        $stmtDetalhes->execute([$lote['id_sisbov']]);
                        $detalhes = $stmtDetalhes->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <tr class="toggle-row" data-lote="<?= $lote['lote'] ?>">
                        <td><strong><?= $lote['lote'] ?></strong></td>
                        <td><?= $detalhes ? $detalhes['romaneio'] : 'N/A' ?></td>
                        <td><?= $detalhes ? $detalhes['PRODUTOR'] : 'N/A' ?></td>
                        <td><?= $detalhes ? $detalhes['NOME_FAZENDA'] : 'N/A' ?></td>
                        <td><?= $lote['total'] ?></td>
                    </tr>
                    <tr id="detalhes-<?= $lote['lote'] ?>" class="hidden-row">
                        <td colspan="5">
                            <table class="table table-condensed table-bordered">
                                <thead>
                                    <tr class="active">
                                        <th>ID SISBOV</th>
                                        <th>Animal</th>
                                        <th>Data Abate</th>
                                        <th>Prazo</th>
                                        <th>Data Registro</th>
                                    </tr>
                                </thead>
                                <tbody id="detalhes-body-<?= $lote['lote'] ?>"></tbody>
                            </table>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $(".toggle-row").click(function() {
            let lote = $(this).data("lote");
            let detalhesRow = $("#detalhes-" + lote);

            if (detalhesRow.is(":visible")) {
                detalhesRow.slideUp();
            } else {
                $.get("buscar_registros.php", { lote: lote, data_abate: $("#data_abate").val() }, function(data) {
                    $("#detalhes-body-" + lote).html(data);
                    detalhesRow.slideDown();
                });
            }
        });
    });
</script>

</body>
</html>
