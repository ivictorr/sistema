<?php
// Caminho para salvar os filtros do usuÃ¡rio
$userId = $_SESSION['user_id'] ?? 'default';
$filename = "relatorioProducao";  // substitua por variável ou método para obter o nome do arquivo
$filterFile = __DIR__ . "/filters/user_{$userId}_{$filename}.txt";


// FunÃ§Ãµes para salvar, carregar e limpar filtros
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

// Verificar se o formulÃ¡rio foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'producao_de' => $_POST['producao_de'] ?? '',
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
            <h3>RELATORIO CONFERENCIA PRODUÇÃO X PALLET</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">

            <div class="form-group row">
                <label class="col-sm-2 col-form-label text-right">Data de Produção:</label>
                <div class="col-sm-10">
                    <input type="date" name="producao_de" class="form-control" value="<?= $savedFilters['producao_de'] ?? '' ?>">
                </div>
            </div>
			            <div class="form-group row">
                <label class="col-sm-2 col-form-label text-right">Tipo de Produção:</label>
                <div class="col-sm-10">
                                            <select id="tipoProduto" name="tipoProduto" class="form-control">
                                <option value="">TODOS</option>
                                <option value="DESOSSA">DESOSSA</option>
                                <option value="MIUDOS">MIUDOS</option>
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
        $producao_de = $_POST['producao_de'] ?? '';
		$tipoProduto = $_POST['tipoProduto'] ?? '';

        $sql = "SELECT ";

        $sql .= "
    A.Cod_produto,
    MAX(B.Desc_produto_est) AS Desc_produto,
	MAX(A.Data_embalagem) as embalagem,
    COUNT(*) AS Quantidade_Producao,
    COUNT(*) - (
        SELECT COUNT(*)
        FROM tbVolume B
        WHERE B.Cod_produto = A.Cod_produto
          AND B.Cod_filial = '100'
          AND B.Data_embalagem = '$producao_de'
          AND B.Num_pallet = 0
		  AND B.Status <> 'C'
    ) AS Quantidade_Paletizada
FROM tbVolume A
INNER JOIN tbProduto B ON A.Cod_produto = B.Cod_produto
WHERE A.Cod_filial = '100'
 AND A.Data_embalagem = '$producao_de'";
          if ($tipoProduto === 'DESOSSA') {
            $sql .= " AND A.Cod_produto BETWEEN 30000 AND 34999";
        } elseif ($tipoProduto === 'MIUDOS') {
            $sql .= " AND A.Cod_produto BETWEEN 35000 AND 35100";
        }
  $sql.="
  AND A.Status <> 'C'
GROUP BY A.Cod_produto";


        try {
            $stmt = $pdoS->query($sql);

            echo "<h4>Relatório Gerado</h4>";
    ?>
<style>
    .bg-verde-claro {
        background-color: #d4edda; /* Verde claro */
        color: #155724; /* Texto verde */
    }

    .bg-vermelho-claro {
        background-color: #f8d7da; /* Vermelho claro */
        color: #721c24; /* Texto vermelho */
    }

    .bg-amarelo-claro {
        background-color: #fff3cd; /* Amarelo claro */
        color: #856404; /* Texto amarelo */
    }
</style>

<table id="tabela" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Código</th>
            <th>Produto</th>
            <th>Produzidas</th>
            <th>Endereçadas</th>
            <th>Diferença</th>
			<th>Ação</th>
        </tr>
    </thead>
<tbody>
    <?php
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $diferenca = $row['Quantidade_Producao'] - $row['Quantidade_Paletizada'];
        // Definir a classe CSS com base na diferença
        $classeDiferenca = '';
        if ($diferenca == 0) {
            $classeDiferenca = 'bg-verde-claro';
        } elseif ($diferenca > 0) {
            $classeDiferenca = 'bg-vermelho-claro';
        } elseif ($diferenca < 0) {
            $classeDiferenca = 'bg-amarelo-claro';
        }

        echo "<tr>
            <td class='{$classeDiferenca}'>{$row['Cod_produto']}</td>
            <td class='{$classeDiferenca}'>{$row['Desc_produto']}</td>
            <td class='{$classeDiferenca}'>{$row['Quantidade_Producao']}</td>
            <td class='{$classeDiferenca}'>{$row['Quantidade_Paletizada']}</td>
            <td class='{$classeDiferenca}'>{$diferenca}</td>
			             <td class='{$classeDiferenca}'>
<button 
    class='btn btn-info btn-sm open-modal' 
    data-cod-produto='{$row['Cod_produto']}'
    data-cod-embalagem='{$row['embalagem']}'
    data-toggle='modal' 
    data-target='#productModal'>
    Ver Detalhes
</button>
                </td>
        </tr>";
    }
    ?>
</tbody>
</table>
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
                            <th>Codigo</th>
							<th>Codigo Barra</th>
                            <th>Peso Liquido</th>
                            <th>Peso Bruto</th>
							<th>Status</th>
                            <th>Balanceiro</th>
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
			
<script>
    $(document).on('click', '.open-modal', function() {
    var codProduto = $(this).data('cod-produto');
    var codEmbalagem = $(this).data('cod-embalagem');

        $.ajax({
            url: 'detalhes_produtos_pallet.php',
            type: 'GET',
        data: {
            cod_produto: codProduto,
            cod_embalagem: codEmbalagem,
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
                            <td>${item.Cod_produto}</td>
							<td>${item.Cod_completo}</td>
							<td>${item.Peso_liquido}</td>
							<td>${item.Peso_bruto}</td>
							<td>${item.Status}</td>
							<td>${item.Nome_cadastro}</td>
					</tr>`;
                    });
                } else {
                    tableContent = '<tr><td colspan="5">Nenhum detalhe encontrado para este produto.</td></tr>';
                }

                $('#productDetails').html(tableContent);
                $('#productModal').modal('show'); // Mostra o modal
				    // Corrigir barra de rolagem ao fechar o modal
    $('#productModal').on('hidden.bs.modal', function () {
        $('body').removeClass('modal-open'); // Remove a classe modal-open
        $('.modal-backdrop').remove(); // Remove o backdrop
        $('body').css('overflow', 'auto'); // Restaura a barra de rolagem
    });
            },
            error: function() {
                $('#productDetails').html('<tr><td colspan="5">Erro ao carregar os detalhes do produto.</td></tr>');
            }
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