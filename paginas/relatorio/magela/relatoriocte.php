<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default'; // Presumindo que o ID do usuário está salvo na sessão, use 'default' se não estiver logado
$filterFile = __DIR__ . "/filters/user_{$userId}_magela.txt";

// Função para salvar filtros
function saveFilters($filters, $filePath)
{
    $directory = dirname($filePath); // Obtém o diretório do arquivo

    // Verifica se o diretório existe, caso contrário, cria
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true); // Cria o diretório com permissões recursivas
    }

    $data = json_encode($filters);
    file_put_contents($filePath, $data); // Salva o arquivo
}

// Função para limpar filtros
function clearFilters($filePath) {
    if (file_exists($filePath)) {
        unlink($filePath); // Remove o arquivo de filtros
    }
}
// Função para carregar filtros
function loadFilters($filePath)
{
    if (file_exists($filePath)) {
        $data = file_get_contents($filePath);
        return json_decode($data, true);
    }
    return [];
}

// Carregar os filtros salvos
$savedFilters = loadFilters($filterFile);

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter filtros do formulário
    $filters = [
        'transportadora' => $_POST['transportadora'] ?? [],
        'emissao_de' => $_POST['emissao_de'] ?? '',
        'emissao_ate' => $_POST['emissao_ate'] ?? '',
    ];

    if (isset($_POST['salvarFiltro'])) {
        // Salvar filtros no arquivo
        saveFilters($filters, $filterFile);

        // Atualizar os filtros carregados para exibição imediata
        $savedFilters = $filters;
    }

    if (isset($_POST['limparFiltro'])) {
        // Limpar os filtros
        clearFilters($filterFile);
        $savedFilters = []; // Resetar os filtros carregados
    }
}
?>
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

    .table tbody tr:hover {
        background-color: #e9ecef;
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

<div class="container mt-5">
    <br>
    <div class="panel panel-primary no-print mt-5" style="margin-top: 30px">
        <div class="panel-heading text-center">
            <h3>RELATORIO CTES</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
                        <!-- Seleção de Banco -->
                        <div class="form-group row">
                <label for="transportadora-select" class="col-sm-2 col-form-label text-right">Transportadora:</label>
                <div class="col-sm-10">
                    <select id="transportadora-select" name="transportadora[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT DISTINCT A.Cod_cadastro, A.Nome_Cadastro FROM tbCadastroGeral A WHERE A.Tipo_cadastro = 'T' AND A.Cod_Cadastro IN ('40645','40646','40643','40186')");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_cadastro'], $savedFilters['transportadora'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_cadastro']}' {$selected}>{$r['Cod_cadastro']} - {$r['Nome_Cadastro']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <!-- Data de Emissão -->
            <div class="form-group row">
                <label class="col-sm-2 col-form-label text-right">Data de Emissão:</label>
                <div class="col-sm-5">
                    <input type="date" name="emissao_de" class="form-control" value="<?= $savedFilters['emissao_de'] ?? '' ?>">
                </div>
                <div class="col-sm-5">
                    <input type="date" name="emissao_ate" class="form-control" value="<?= $savedFilters['emissao_ate'] ?? '' ?>">
                </div>
            </div>
            <div class="form-group text-center">
                <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
                <button type="submit" name="salvarFiltro" class="btn btn-secondary">Salvar Filtro</button>
                <button type="submit" name="limparFiltro" class="btn btn-danger">Limpar Filtro</button>
            </div>
        </form>
    </div>
</div>

<!-- Resumo dos Filtros -->
<?php if (isset($_POST['gerarRelatorio'])): ?>
    <div id="filterSummary" class="filter-summary">
        <strong>Filtros Aplicados:</strong>
        <p><strong>Transportadora:</strong>
            <?php
            echo isset($_POST['transportadora']) && !empty($_POST['transportadora'])
                ? implode(', ', $_POST['transportadora'])
                : 'TODAS';
            ?>
        </p>
        <p><strong>Data de Emissão:</strong>
            <?php
            echo (!empty($_POST['emissao_de']) ? $_POST['emissao_de'] : 'TODAS') .
                ' até ' .
                (!empty($_POST['emissao_ate']) ? $_POST['emissao_ate'] : 'TODAS');
            ?>
        </p>
    </div>
<?php endif; ?>

<!-- Tabela -->
<div class="table-container">
    <?php
    if (isset($_POST['gerarRelatorio'])):
        $transportadora = isset($_POST['transportadora']) ? $_POST['transportadora'] : [];
        $emissao_de = $_POST['emissao_de'] ?? '';
        $emissao_ate = $_POST['emissao_ate'] ?? '';

        $sql = "SELECT ";
     
            $sql .= "    
            TBE.Data_emissao AS DataEmissaoEntrada,
            TBP.Data_emissao AS DataEmissaoTitulo,
            TBP.Data_vencto AS DataVencimentoTitulo,
            TBP.Status_titulo AS StatusTitulo,
            TBP.Num_docto AS NumeroDocumento,
            TBC.Nome_cadastro AS NomeFornecedor,
            TBP.Valor_titulo AS ValorTitulo,
            TBP.Num_parcela AS TituloParcela
            ";

            $sql .= "
            FROM 
            tbTituloPag TBP
        INNER JOIN 
            tbEntradas TBE 
            ON TBP.Chave_fato_orig = TBE.Chave_fato
        INNER JOIN 
            tbCadastroGeral TBC 
            ON TBP.Cod_fornecedor = TBC.Cod_cadastro
            ";

        if (!empty($transportadora)) {
            $sql .= " WHERE TBP.Cod_fornecedor IN ('" . implode("','", $transportadora) . "')";
        }else{
            $sql .= " WHERE TBP.Cod_fornecedor IN ('40645','40646','40643','40186')";
        }

        if (!empty($emissao_de)) {
            $sql .= " AND TBP.Data_emissao >= '$emissao_de'";
        }

        if (!empty($emissao_ate)) {
            $sql .= " AND TBP.Data_emissao <= '$emissao_ate'";
        }

        try {
            $stmt = $pdoS->query($sql);

            echo "<table id='tabela' class='table table-striped table-bordered'>";
            echo "<thead>";
                echo "<tr><th>DATA E.CTE</th><th>DATA L.TITULO</th><th>DATA V.TITULO</th><th>SITUAÇÃO</th><th>N°CTE</th><th>NOME</th><th style='text-align: right;'>VALOR</th></tr>";
            echo "</thead>";
            echo "<tbody>";
            
            $total_valor = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	    switch ($row['StatusTitulo']) {
        case 'L':
            $statusTitulo = 'LIQUIDADO';
            break;
        case 'A':
            $statusTitulo = 'ABERTO';
            break;
        default:
            $statusTitulo = $row['StatusTitulo']; // Valor padrão
            break;
    }
                echo "<tr>";
                    echo "<td>" . date('d/m/Y', strtotime($row['DataEmissaoEntrada'])) . "</td>";
                    echo "<td>" . date('d/m/Y', strtotime($row['DataEmissaoTitulo'])) . "</td>";
                     echo "<td>" . date('d/m/Y', strtotime($row['DataVencimentoTitulo'])) . "</td>";
                    echo "<td>{$statusTitulo}</td>";
                    echo "<td>{$row['NumeroDocumento']} / {$row['TituloParcela']}</td>";
                    echo "<td>{$row['NomeFornecedor']}</td>";
                    echo "<td style='text-align: right;'>" . number_format($row['ValorTitulo'], 2, ',', '.') . "</td>";
                echo "</tr>";
                $total_valor += $row['ValorTitulo'];
            }
            
            echo "</tbody>";
            
            // Adicionando o rodapé com os totais
            echo "<tfoot>";
            echo "<tr style='font-weight: bold;'>";
            echo "<td colspan='6' style='text-align: right;'>Total:</td>";
            echo "<td style='text-align: right;'>" . number_format($total_valor, 2, ',', '.') . "</td>";
            echo "</tr>";
            echo "</tfoot>";
            
            echo "</table>";
        } catch (PDOException $e) {
            echo "<p>Erro ao conectar ou consultar o banco de dados: " . $e->getMessage() . "</p>";
        }
    endif;
    ?>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
$(document).ready(function() {
    $('#tabela').DataTable({
        dom: 'Bfrtip',
        paging: false,
        buttons: [
            {
                extend: 'print',
                text: 'Imprimir',
                footer: true, // Inclui o rodapé na impressão
                exportOptions: {
                    columns: ':not(:last-child)' // Exclui a última coluna (Ações) da impressão
                },
                customize: function (win) {
                    // Adiciona estilo ao rodapé para que ele apareça na impressão
                    $(win.document.body).find('tfoot').css('display', 'table-footer-group');
                }
            },
            'csv', 
            'excel', 
            'pdf'
        ],
        order: [[3, 'desc']],
    });
});
</script>