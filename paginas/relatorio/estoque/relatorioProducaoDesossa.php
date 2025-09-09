<?php
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
        /* Define um limite para a largura mÃ¡xima */
    }

    #productModal .modal-body {
        max-height: 70vh;
        /* Define uma altura mÃ¡xima para a Ã¡rea de conteÃºdo */
        overflow-y: auto;
        /* Habilita a rolagem vertical */
    }
</style>

<!-- Bootstrap CSS -->
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
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
            <h3>RELATORIO DE ESTOQUE (APENAS PRODUÇÃO DESOSSA)</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
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
                    <input type="date" name="producao_de" class="form-control" value="">
                </div>
                <div class="col-sm-5">
                    <input type="date" name="producao_ate" class="form-control" value="">
                </div>
            </div>
            <div class="form-group text-center">
                <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
            </div>
        </form>
    </div>

<?php if (isset($_POST['gerarRelatorio'])):

    $producao_de = $_POST['producao_de'] ?? '';
    $producao_ate = $_POST['producao_ate'] ?? '';
    $localEstoque = $_POST['localEstoque'] ?? '';

    $sql = "SELECT 
    D.Cod_produto, 
    E.Desc_produto_est, 
    COUNT(*) AS Total_registros,
    SUM(D.Peso_liquido) AS Peso_liquido,
    SUM(D.Peso_bruto) AS Peso_bruto
FROM tbEntradas a 
INNER JOIN tbentradasitem b ON a.chave_fato = b.chave_fato AND b.Num_subItem = 0
INNER JOIN tbentradasitemrom c ON a.Chave_fato = c.Chave_fato AND b.Num_item = c.Num_item AND b.Num_subItem = c.Num_subItem
INNER JOIN tbvolume d ON c.Cod_filial_volume = d.Cod_filial AND c.Serie_volume = d.Serie_volume AND c.Num_volume = d.Num_volume
INNER JOIN tbproduto e ON d.Cod_produto = e.Cod_produto
WHERE 
    a.cod_docto = 'RPE'
    AND d.status = 'E' 
    AND d.Cod_local_estoque = '01' 
    AND d.cod_filial = '{$filial}'
    AND NOT EXISTS (
        SELECT 1
        FROM tbEntradas a2
        INNER JOIN tbEntradasItem b2 ON a2.chave_fato = b2.chave_fato AND b2.Num_subItem = 0
        INNER JOIN tbEntradasItemRom c2 ON a2.Chave_fato = c2.Chave_fato AND b2.Num_item = c2.Num_item AND b2.Num_subItem = c2.Num_subItem
        WHERE 
            c2.Cod_filial_volume = d.Cod_filial 
            AND c2.Serie_volume = d.Serie_volume 
            AND c2.Num_volume = d.Num_volume
            AND a2.Cod_docto <> 'RPE' 
            AND a2.Data_v1 > a.Data_v1
    )
";

    if (!empty($producao_de)) {
        $sql .= " AND A.Data_v1 BETWEEN '$producao_de' AND '$producao_ate'";
    }
    $sql .= " GROUP BY D.Cod_produto, E.Desc_produto_est";

    try {
        $stmt = $pdoS->query($sql);

        echo "<h4>Relatório Gerado</h4>";
        // Aqui você pode montar uma tabela HTML com os dados retornados
?>

<style>
    .row-baixado {
        background-color: #f8d7da; /* Vermelho claro */
        color: #721c24; /* Texto vermelho */
    }

    .row-cancelado {
        background-color: #f8d7da; /* Vermelho claro */
        color: #721c24; /* Texto vermelho */
    }

    .row-estoque {
        background-color: #d4edda; /* Verde claro */
        color: #155724; /* Texto verde */
    }
</style>

<table id="tabela" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Código</th>
            <th>Produto</th>
			<th>Caixas</th>
            <th>Peso Liquido </th>
            <th>Peso Bruto</th>
        </tr>
    </thead>
    <tbody>
        <?php
$totalCaixas = 0;
$totalPesoLiquido = 0;
$totalPesoBruto = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $totalCaixas += $row['Total_registros'];
    $totalPesoLiquido += $row['Peso_liquido'];
    $totalPesoBruto += $row['Peso_bruto'];
            echo "<tr class='product-row'>
                <td>{$row['Cod_produto']}</td>
                <td>{$row['Desc_produto_est']}</td>
				<td>{$row['Total_registros']}</td>
                <td>{$row['Peso_liquido']}</td>
                <td>{$row['Peso_bruto']}</td>
            </tr>";
        }
        ?>
		<tfoot>
    <tr style="font-weight: bold; background-color: #d1ecf1; color: #0c5460;">
        <td colspan="2">Totais</td>
        <td><?= $totalCaixas ?></td>
        <td><?= number_format($totalPesoLiquido, 2, ',', '.') ?></td>
        <td><?= number_format($totalPesoBruto, 2, ',', '.') ?></td>
    </tr>
</tfoot>
    </tbody>
</table>

            <script>
                $(document).ready(function() {
                    $('#tabela').DataTable({
                        dom: 'Bfrtip',
                        paging: false,
                        buttons: [{
                                extend: 'print',
                                text: 'Imprimir',
                                footer: true, // Inclui o rodapÃ© na impressÃ£o
                                exportOptions: {
                                    columns: ':not(:last-child)' // Exclui a Ãºltima coluna (AÃ§Ãµes) da impressÃ£o
                                },
                                customize: function(win) {
                                    // Adiciona estilo ao rodapÃ© para que ele apareÃ§a na impressÃ£o
                                    $(win.document.body).find('tfoot').css('display', 'table-footer-group');
                                }
                            },
                            'csv',
                            'excel',
                            'pdf'
                        ],
                        order: [
                            [3, 'desc']
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