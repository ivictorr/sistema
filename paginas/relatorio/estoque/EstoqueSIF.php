<?php
// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'producao_de' => $_POST['producao_de'] ?? '',
        'producao_ate' => $_POST['producao_ate'] ?? '',
        'condicao' => $_POST['condicaoProduto'] ?? '',
		'localestoque' => $_POST['localEstoque'] ?? '',
    ];
}
$locaisPermitidosPorFilial = [
    '100' => ['01'],
    '200' => ['01', '03', '04', '02', '13', '14', '12', '05'],
    '400' => ['01'],
];
$filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; // fallback para 100
$locaisPermitidos = $locaisPermitidosPorFilial[$filial] ?? [];
$codigosIn = implode(',', array_map(fn($cod) => "'" . $cod . "'", $locaisPermitidos));
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
            <h3>RELATORIO DE ESTOQUE POR SIF</h3>
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
			 <div class="form-group row">
                <label for="condicaoProduto" class="col-sm-2 col-form-label text-right">SIF:</label>
                <div class="col-sm-10">
					<input type="text" name="sif" placeholder="Exemplo: 1891 - Xinguara 2852 - Jatai 0010 - Altamira" class="form-control">
                </div>
            </div>
            <div class="form-group text-center">
                <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
            </div>
        </form>
    </div>

    <?php if (isset($_POST['gerarRelatorio'])):
	
	
        $produto = $_POST['produto'] ?? [];
        $producao_de = $_POST['producao_de'] ?? '';
        $producao_ate = $_POST['producao_ate'] ?? '';
        $condicaoProduto = $_POST['condicaoProduto'] ?? '';
        $localEstoque = $_POST['localEstoque'] ?? '';
		$sif = $_POST['sif'] ?? '';

    // Salva o local de estoque na sessão
    $_SESSION['local_estoque'] = $localEstoque;

        $sql = "
            SELECT 
					A.Cod_produto AS PRODUTO,
					MAX(B.Desc_produto_est) AS DESCRICAO,
					COUNT(*) AS CAIXAS,
					SUM(A.Peso_liquido) as PESO
                FROM tbVolume A 
                INNER JOIN tbProduto B ON A.Cod_produto = B.Cod_produto 
                INNER JOIN tbProdutoRef C ON B.Cod_Produto = C.Cod_Produto
				INNER JOIN tbVolumeItem TBI ON A.Cod_filial = TBI.Cod_filial AND A.Serie_volume = TBI.Serie_volume AND A.Num_volume = TBI.Num_volume
                WHERE A.Status = 'E' 
                AND A.Cod_filial_estoque = {$filial}
				AND A.Cod_produto BETWEEN '20000' AND '39999' 
				AND A.Cod_Produto NOT IN ('20007','20041','20000','20042')";
				
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
		if (!empty($sif)) {
            $sql .= " AND LEFT(TBI.Rastreabilidade, 4) = '$sif'";
        }
		$sql .= "GROUP BY A.Cod_produto";

        try {
            $stmt = $pdoS->query($sql);

            echo "<h4>Relatório Gerado</h4>";
    ?>
            <table id="tabela" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Produto</th>
                        <th>Caixas</th>
                        <th>Peso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Variáveis para acumular os totais
                    $totalCaixas = 0;
                    $totalPeso = 0;
 
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
						$totalCaixas += $row['CAIXAS'];
                        $totalPeso += $row['PESO'];
                        // Exibir a linha da tabela
                        echo "<tr class='product-row' data-product-id='{$row['PRODUTO']}'>
                <td>{$row['PRODUTO']}</td>
                <td>{$row['DESCRICAO']}</td>
                <td>{$row['CAIXAS']}</td>
                <td>" . number_format($row['PESO'], 2, ',', '.') . "</td>
            </tr>";
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong><?php echo number_format($totalCaixas, 0, ',', '.'); ?></strong></td>
                        <td><strong><?php echo number_format($totalPeso, 2, ',', '.'); ?></strong></td>
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