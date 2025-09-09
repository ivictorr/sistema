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
        'produto' => $_POST['produto'] ?? [],
        'producao_de' => $_POST['producao_de'] ?? '',
        'producao_ate' => $_POST['producao_ate'] ?? '',
        'localestoque' => $_POST['localEstoque'] ?? '',
		'situacaoEstoque' => $_POST['situacaoEstoque'] ?? [],
		'bloqueadosEstoque' => $_POST['bloqueadosEstoque'] ?? [],
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
            <h3>RELATORIO DE REASTREABILIDADE</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
            <!-- SeleÃ§Ã£o de Filial -->
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
    <label for="situacaoEstoque-select" class="col-sm-2 col-form-label text-right">Status:</label>
    <div class="col-sm-10">
        <select id="situacaoEstoque-select" name="situacaoEstoque[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">
            <?php
            $options = [
                'E' => 'ESTOQUE',
                'B' => 'BAIXADA',
                'C' => 'CANCELADA'
            ];
            foreach ($options as $value => $label) {
                $selected = in_array($value, $savedFilters['situacaoEstoque'] ?? []) ? 'selected' : '';
                echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
            }
            ?>
        </select>
    </div>
</div>
<div class="form-group row">
    <label for="bloqueadosEstoque-select" class="col-sm-2 col-form-label text-right">Bloqueados:</label>
    <div class="col-sm-10">
        <select id="bloqueadosEstoque-select" name="bloqueadosEstoque[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">
            <?php
            $options = [
                'N' => 'SIM',
                '' => 'NAO',
            ];
            foreach ($options as $value => $label) {
                $selected = in_array($value, $savedFilters['bloqueadosEstoque'] ?? []) ? 'selected' : '';
                echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
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
            <div class="form-group text-center">
                <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
            </div>
        </form>
    </div>

    <?php if (isset($_POST['gerarRelatorio'])):
        $produto = $_POST['produto'] ?? [];
        $producao_de = $_POST['producao_de'] ?? '';
        $producao_ate = $_POST['producao_ate'] ?? '';
        $localEstoque = $_POST['localEstoque'] ?? '';
		$situacaoEstoque = $_POST['situacaoEstoque'] ?? [];
		$bloqueadosEstoque = $_POST['bloqueadosEstoque'] ?? [];

        $sql = "SELECT 
            A.Cod_produto, 
            B.Desc_produto_est, 
            A.Data_producao,
			A.Data_validade,
			tbi.Rastreabilidade,
			A.Status,
			A.Liberacao_contaminacao,
			TBC.Nome_cadastro
        FROM tbVolume A
		LEFT JOIN tbVolumeItem tbi ON A.Cod_filial = tbi.Cod_Filial AND A.Serie_volume = tbi.Serie_volume AND A.Num_volume = tbi.Num_volume
        INNER JOIN tbProduto B ON A.Cod_produto = B.Cod_produto
        INNER JOIN tbProdutoRef C ON B.Cod_Produto = C.Cod_Produto
		INNER JOIN TBCADASTROGERAL TBC ON A.Cod_funcionario = TBC.Cod_cadastro
        WHERE A.Cod_filial = '{$filialSelecionada}'
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
        if (!empty($localEstoque)) {
            $sql .= " AND A.Cod_local_estoque = '$localEstoque'";
        }
		if (!empty($situacaoEstoque)) {
            $sql .= " AND A.Status IN ('" . implode("','", $situacaoEstoque) . "')";
        }
		if (!empty($bloqueadosEstoque)) {
            $sql .= " AND A.Liberacao_contaminacao IN ('" . implode("','", $bloqueadosEstoque) . "')";
        }
        try {
            $stmt = $pdoS->query($sql);

            echo "<h4>Relatório Gerado</h4>";
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
            <th>Data Produção</th>
            <th>Data Validade</th>
            <th>Rastreabilidade</th>
            <th>Situação</th>
            <th>Bloqueado</th>
            <th>Funcionário</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Inicializar contadores
        $totalCaixas = 0;
        $totalBloqueados = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Formatar as datas para o formato DD/MM/AAAA
            $dataProducao = $row['Data_producao'] ? (new DateTime($row['Data_producao']))->format('d/m/Y') : '';
            $dataValidade = $row['Data_validade'] ? (new DateTime($row['Data_validade']))->format('d/m/Y') : '';

            // Traduzir status para os valores correspondentes e definir a classe CSS
            $status = '';
            $rowClass = '';

            switch ($row['Status']) {
                case 'B':
                    $status = 'Baixado';
                    $rowClass = 'row-baixado';
                    break;
                case 'C':
                    $status = 'Cancelado';
                    $rowClass = 'row-cancelado';
                    break;
                case 'E':
                    $status = 'Estoque';
                    $rowClass = 'row-estoque';
                    break;
                default:
                    $status = 'Desconhecido';
            }

            // Incrementar contadores
            $totalCaixas++;
            if ($row['Liberacao_contaminacao'] == 'S') {
                $totalBloqueados++;
            }

            echo "<tr class='product-row {$rowClass}'>
                <td>{$row['Cod_produto']}</td>
                <td>{$row['Desc_produto_est']}</td>
                <td>{$dataProducao}</td>
                <td>{$dataValidade}</td>
                <td>{$row['Rastreabilidade']}</td>
                <td>{$status}</td>
                <td>" . ($row['Liberacao_contaminacao'] == 'N' ? 'SIM' : 'NAO') . "</td>
                <td>{$row['Nome_cadastro']}</td>
            </tr>";
        }
        ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5" class="text-right">Total Caixas:</th>
            <td colspan="3"><?= $totalCaixas ?></td>
        </tr>
        <tr>
            <th colspan="5" class="text-right">Total Bloqueados:</th>
            <td colspan="3"><?= $totalBloqueados ?></td>
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