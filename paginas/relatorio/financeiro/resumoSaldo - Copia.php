<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<style>
    body {
        background-color: #f8f9fa;
    }

    h2,
    h4 {
        color: #333;
        text-align: center;
        margin-bottom: 20px;
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

    .table-container {
        padding: 20px;
    }

    .filter-summary {
        margin-top: 20px;
        padding: 10px;
        background-color: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 10px;
        font-size: 14px;
        line-height: 1.5;
        text-align: left;
    }

    .filter-summary p {
        margin: 5px 0;
    }

    .filter-summary strong {
        color: #0d47a1;
        font-weight: bold;
    }

    @media print {
        body {
            background-color: white;
        }

        .table-container {
            padding: 0;
        }

        .no-print {
            display: none;
        }

        .filter-summary {
            display: block;
            margin-top: 20px;
        }

        .logo-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-header img {
            max-width: 150px;
        }
    }
</style>

<div class="container">
    <!-- Filtro -->
    <div class="panel panel-primary no-print" style="margin-top: 50px">
        <div class="panel-heading text-center">
            <h4>RESUMO DE SALDO</h4>
        </div>
        <div class="panel-body">
            <form method="POST" action="" class="form-horizontal">
                <!-- Seleção de Filial -->
                <div class="form-group row">
                    <label for="filial-select" class="col-sm-2 col-form-label text-right">Escolha a Filial:</label>
                    <div class="col-sm-10">
                        <?php
                        try {
                            $res = $pdoS->query("SELECT * FROM tbFilial");
                            echo '<select id="filial-select" name="filial[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                            while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $r['Cod_filial'] . '">' . $r['Cod_filial'] . ' - ' . $r['Nome_filial'] . '</option>';
                            }
                            echo '</select>';
                        } catch (PDOException $e) {
                            echo "<p>Erro ao carregar filiais: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                </div>
                <!-- Seleção de Banco -->
                <div class="form-group row">
                    <label for="banco-select" class="col-sm-2 col-form-label text-right">Escolha o Banco:</label>
                    <div class="col-sm-10">
                        <?php
                        try {
                            $res = $pdoS->query("SELECT * FROM tbBancoCaixa");
                            echo '<select id="banco-select" name="banco[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                            while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $r['Cod_banco_caixa'] . '">' . $r['Cod_banco_caixa'] . ' - ' . $r['Nome_agencia'] . '</option>';
                            }
                            echo '</select>';
                        } catch (PDOException $e) {
                            echo "<p>Erro ao carregar bancos: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>
                </div>
                <!-- Seleção de Tipo de Relatório -->
                <div class="form-group row">
                    <label for="tipo-relatorio" class="col-sm-2 col-form-label text-right">Tipo de Relatório:</label>
                    <div class="col-sm-10">
                        <select id="tipo-relatorio" name="tipo_relatorio" class="form-control" required>
                            <option value="sintetico">Sintético</option>
                            <option value="analitico">Analítico</option>
                        </select>
                    </div>
                </div>
                <div class="form-group text-center">
                    <button type="submit" name="gerarRelatorio" class="btn btn-primary btn-lg">Gerar Relatório</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumo dos Filtros -->
    <?php if (isset($_POST['gerarRelatorio'])): ?>
    <div id="filterSummary" class="filter-summary">
        <strong>Filtros Aplicados:</strong>
        <p><strong>Filiais:</strong> <?php echo implode(', ', isset($_POST['filial']) ? $_POST['filial'] : ['Nenhuma']); ?></p>
        <p><strong>Bancos:</strong> <?php echo implode(', ', isset($_POST['banco']) ? $_POST['banco'] : ['Nenhum']); ?></p>
        <p><strong>Tipo de Relatório:</strong> <?php echo ucfirst($_POST['tipo_relatorio']); ?></p>
        <p><strong>Data Impressão:</strong> </p>
    </div>
    <?php endif; ?>

    <!-- Tabela -->
    <div class="table-container">
        <?php
        if (isset($_POST['gerarRelatorio'])):
            $filiais = isset($_POST['filial']) ? $_POST['filial'] : [];
            $bancos = isset($_POST['banco']) ? $_POST['banco'] : [];
            $tipo_relatorio = isset($_POST['tipo_relatorio']) ? $_POST['tipo_relatorio'] : 'sintetico';

            $sql = "SELECT ";

            if ($tipo_relatorio === 'sintetico') {
                $sql .= "
                    A.Cod_banco_caixa AS CODIGO, 
                    MAX(B.Nome_agencia) AS NOME, 
                    SUM(A.Valor_saldo) AS SALDO, 
                    COUNT(*) AS QUANTIDADE_TITULOS 
                ";
            } else {
                $sql .= "
                    A.Num_docto AS TITULO, 
                    A.Cod_banco_caixa AS CODIGO, 
                    B.Nome_agencia AS NOME, 
                    A.Valor_saldo AS VALOR, 
                    A.Data_emissao AS DATA_TITULO
                ";
            }

            $sql .= "
                FROM tbTituloRec A
                INNER JOIN tbBancoCaixa B ON A.Cod_banco_caixa = B.Cod_banco_caixa
                WHERE A.Status_titulo = 'A'
            ";

            if (!empty($filiais)) {
                $filiais_in = "'" . implode("','", $filiais) . "'";
                $sql .= " AND A.Cod_filial IN ($filiais_in)";
            }

            if (!empty($bancos)) {
                $bancos_in = "'" . implode("','", $bancos) . "'";
                $sql .= " AND B.Cod_banco_caixa IN ($bancos_in)";
            }

            if ($tipo_relatorio === 'sintetico') {
                $sql .= " GROUP BY A.Cod_banco_caixa";
            }

            try {
                $stmt = $pdoS->query($sql);

                echo "<h4>Relatório Gerado</h4>";
                echo "<table id='relatorioTabela' class='table table-bordered'>";
                echo "<thead>";
                if ($tipo_relatorio === 'sintetico') {
                    echo "<tr><th>CODIGO</th><th>NOME</th><th>SALDO</th><th>QUANTIDADE DE TÍTULOS</th></tr>";
                } else {
                    echo "<tr><th>TÍTULO</th><th>CODIGO</th><th>NOME</th><th style='text-align: right;'>VALOR</th><th>DATA TÍTULO</th></tr>";
                }
                echo "</thead><tbody>";

                $total = 0;

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    if ($tipo_relatorio === 'sintetico') {
                        echo "<td>{$row['CODIGO']}</td>";
                        echo "<td>{$row['NOME']}</td>";
                        echo "<td style='text-align: right;'>" . number_format($row['SALDO'], 2, ',', '.') . "</td>";
                        echo "<td>{$row['QUANTIDADE_TITULOS']}</td>";
                    } else {
                        echo "<td>{$row['TITULO']}</td>";
                        echo "<td>{$row['CODIGO']}</td>";
                        echo "<td>{$row['NOME']}</td>";
                        echo "<td style='text-align: right;'>" . number_format($row['VALOR'], 2, ',', '.') . "</td>";

                        $dataTitulo = !empty($row['DATA_TITULO']) ? date('d/m/Y', strtotime($row['DATA_TITULO'])) : '-';
                        echo "<td>{$dataTitulo}</td>";

                        $total += $row['VALOR'];
                    }
                    echo "</tr>";
                }

                if ($tipo_relatorio === 'analitico') {
                    echo "<tr><td colspan='3' style='text-align: right; font-weight: bold;'>Total:</td>";
                    echo "<td style='text-align: right; font-weight: bold;'>" . number_format($total, 2, ',', '.') . "</td><td></td></tr>";
                }

                echo "</tbody></table>";
            } catch (PDOException $e) {
                echo "<p>Erro ao conectar ou consultar o banco de dados: " . $e->getMessage() . "</p>";
            }
        endif;
        ?>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
    $(document).ready(function() {
        $('.selectpicker').selectpicker();
        $('#relatorioTabela').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'print',
                    text: 'Imprimir',
                    customize: function(win) {
                        $(win.document.body)
                            .append($('.filter-summary').clone().css('display', 'block'));
                    }
                },
                'csv', 'excel', 'pdf'
            ]
        });
    });
</script>
