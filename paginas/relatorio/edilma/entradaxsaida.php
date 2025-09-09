<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default'; // Presumindo que o ID do usuário está salvo na sessão, use 'default' se não estiver logado
$filterFile = __DIR__ . "/filters/user_{$userId}_entradaxsaida.txt";

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
        'filial' => $_POST['filial'] ?? [],
        'movt_de' => $_POST['movt_de'] ?? '',
        'movt_ate' => $_POST['movt_ate'] ?? '',
        'tmv_cod_tipo_mv' => $_POST['tmv_cod_tipo_mv'] ?? [],
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
            <h3>RELATORIO ENTRADAS X SAIDAS</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
            <!-- Seleção de Filial -->
            <div class="form-group row">
                <label for="filial-select" class="col-sm-2 col-form-label text-right">Escolha a Filial:</label>
                <div class="col-sm-10">
                    <select id="filial-select" name="filial[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT * FROM tbFilial");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_filial'], $savedFilters['filial'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_filial']}' {$selected}>{$r['Cod_filial']} - {$r['Nome_filial']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
                        <!-- Sele��o de TMV_COD_TIPO_MV -->
            <div class="form-group row">
                <label for="tmv-select" class="col-sm-2 col-form-label text-right">Tipo de Movimento:</label>
                <div class="col-sm-10">
                    <select id="tmv-select" name="tmv_cod_tipo_mv[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT DISTINCT A.TMV_COD_TIPO_MV, B.DESCRICAO FROM VW_MOVTO_ENT_SAI_ITENS A INNER JOIN tbTipoMvEstoque B ON A.TMV_COD_TIPO_MV = B.COD_TIPO_MV");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['TMV_COD_TIPO_MV'], $savedFilters['tmv_cod_tipo_mv'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['TMV_COD_TIPO_MV']}' {$selected}>{$r['TMV_COD_TIPO_MV']} - {$r['DESCRICAO']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Data de Emissão -->
            <div class="form-group row">
                <label class="col-sm-2 col-form-label text-right">Data de Movimento:</label>
                <div class="col-sm-5">
                    <input type="date" name="movt_de" class="form-control" value="<?= $savedFilters['movt_de'] ?? '' ?>">
                </div>
                <div class="col-sm-5">
                    <input type="date" name="movt_ate" class="form-control" value="<?= $savedFilters['movt_ate'] ?? '' ?>">
                </div>
            </div>
            <div class="form-group text-center">
                <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
                <button type="submit" name="salvarFiltro" class="btn btn-secondary">Salvar Filtro</button>
            </div>
        </form>
    </div>
</div>

<!-- Resumo dos Filtros -->
<?php if (isset($_POST['gerarRelatorio'])): ?>
    <div id="filterSummary" class="filter-summary">
        <strong>Filtros Aplicados:</strong>
        <p><strong>Filiais:</strong>
            <?php
            echo isset($_POST['filial']) && !empty($_POST['filial'])
                ? implode(', ', $_POST['filial'])
                : 'TODAS';
            ?>
        </p>
        <p><strong>Data de Movimento:</strong>
            <?php
            echo (!empty($_POST['movt_de']) ? $_POST['movt_de'] : 'TODAS') .
                ' até ' .
                (!empty($_POST['movt_ate']) ? $_POST['movt_ate'] : 'TODAS');
            ?>
        </p>
    </div>
<?php endif; ?>

<!-- Tabela -->
<div class="table-container">
    <?php
    if (isset($_POST['gerarRelatorio'])):
        $filiais = isset($_POST['filial']) ? $_POST['filial'] : [];
        $tmv_cod_tipo_mv = $_POST['tmv_cod_tipo_mv'] ?? [];
        $movt_de = $_POST['movt_de'] ?? '';
        $movt_ate = $_POST['movt_ate'] ?? '';

        $sql = "SELECT * FROM VW_MOVTO_ENT_SAI_ITENS A WHERE QTDE_PRI <> 0";

        if (!empty($filiais)) {
            $sql .= "AND A.FILIAL IN ('" . implode("','", $filiais) . "')";
        }
        if (!empty($tmv_cod_tipo_mv)) {
            $sql .= " AND TMV_COD_TIPO_MV IN ('" . implode("','", $tmv_cod_tipo_mv) . "')";
        }
        if (!empty($movt_de)) {
            $sql .= " AND A.DATA_MOVTO >= '$movt_de'";
        }

        if (!empty($movt_ate)) {
            $sql .= " AND A.DATA_MOVTO <= '$movt_ate'";
        }


        try {
            $stmt = $pdoS->query($sql);

            echo "<h4>Relatorio Gerado</h4>";
            echo "<table id='relatorioTabela' class='table table-striped table-bordered'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>FILIAL</th>";
            echo "<th>TMV_COD_TIPO_MV</th>";
            echo "<th>TMV_DESCRICAO</th>";
            echo "<th>TMV_CLASSE</th>";
            echo "<th>TMV_PERFIL</th>";
            echo "<th>CLI_FOR_COD_CADASTRO</th>";
            echo "<th>CLI_FOR_NOME_CADATRO</th>";
            echo "<th>UF</th>";
            echo "<th>CPF_CGC</th>";
            echo "<th>TIPO_FJ</th>";
            echo "<th>DATA_MOVTO</th>";
            echo "<th>DATA_V1</th>";
            echo "<th>DATA_V2</th>";
            echo "<th>DATA_ESTOQUE</th>";
            echo "<th>CHAVE_FATO</th>";
            echo "<th>CHAVE_FATO_ORIG_UN</th>";
            echo "<th>COD_DOCTO</th>";
            echo "<th>Serie_seq</th>";
            echo "<th>NUM_DOCTO</th>";
            echo "<th>COD_LOCAL</th>";
            echo "<th>COD_LINHA</th>";
            echo "<th>DATA_HORA</th>";
            echo "<th>Cod_divisao1</th>";
            echo "<th>Desc_divisao1</th>";
            echo "<th>Cod_divisao2</th>";
            echo "<th>Desc_divisao2</th>";
            echo "<th>Cod_divisao3</th>";
            echo "<th>Desc_divisao3</th>";
            echo "<th>COD_PRODUTO</th>";
            echo "<th>DESC_PRODUTO_EST</th>";
            echo "<th>NUM_ITEM</th>";
            echo "<th>NUM_SUBITEM</th>";
            echo "<th>Atlz_estoque</th>";
            echo "<th>ATLZ_SALDO_AUX</th>";
            echo "<th>ATLZ_SALDO_PRI</th>";
            echo "<th>QTDE_PRI</th>";
            echo "<th>QTDE_AUX</th>";
			echo "<th>V. UNT</th>";
			echo "<th>V. TOTAL</th>";
            echo "<th>COD_UNIDADE_PRI</th>";
            echo "<th>NUM_LOTE</th>";
            echo "<th>CHAVE_FATO_MP</th>";
            echo "<th>COD_UNIDADE_AUX</th>";
            echo "<th>ORDEM</th>";
            echo "<th>TIPO</th>";
			echo "<th>MOTIVO</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>{$row['FILIAL']}</td>";
                echo "<td>{$row['TMV_COD_TIPO_MV']}</td>";
                echo "<td>{$row['TMV_DESCRICAO']}</td>";
                echo "<td>{$row['TMV_CLASSE']}</td>";
                echo "<td>{$row['TMV_PERFIL']}</td>";
                echo "<td>{$row['CLI_FOR_COD_CADASTRO']}</td>";
                echo "<td>{$row['CLI_FOR_NOME_CADATRO']}</td>";
                echo "<td>{$row['UF']}</td>";
                echo "<td>{$row['CPF_CGC']}</td>";
                echo "<td>{$row['TIPO_FJ']}</td>";
                echo "<td>" . (!empty($row['DATA_MOVTO']) ? date('d/m/Y', strtotime($row['DATA_MOVTO'])) : '-') . "</td>";
                echo "<td>" . (!empty($row['DATA_V1']) ? date('d/m/Y', strtotime($row['DATA_V1'])) : '-') . "</td>";
                echo "<td>" . (!empty($row['DATA_V2']) ? date('d/m/Y', strtotime($row['DATA_V2'])) : '-') . "</td>";
                echo "<td>{$row['DATA_ESTOQUE']}</td>";
                echo "<td>{$row['CHAVE_FATO']}</td>";
                echo "<td>{$row['CHAVE_FATO_ORIG_UN']}</td>";
                echo "<td>{$row['COD_DOCTO']}</td>";
                echo "<td>{$row['Serie_seq']}</td>";
                echo "<td>{$row['NUM_DOCTO']}</td>";
                echo "<td>{$row['COD_LOCAL']}</td>";
                echo "<td>{$row['COD_LINHA']}</td>";
                echo "<td>{$row['DATA_HORA']}</td>";
                echo "<td>{$row['Cod_divisao1']}</td>";
                echo "<td>{$row['Desc_divisao1']}</td>";
                echo "<td>{$row['Cod_divisao2']}</td>";
                echo "<td>{$row['Desc_divisao2']}</td>";
                echo "<td>{$row['Cod_divisao3']}</td>";
                echo "<td>{$row['Desc_divisao3']}</td>";
                echo "<td>{$row['COD_PRODUTO']}</td>";
                echo "<td>{$row['DESC_PRODUTO_EST']}</td>";
                echo "<td>{$row['NUM_ITEM']}</td>";
                echo "<td>{$row['NUM_SUBITEM']}</td>";
                echo "<td>{$row['Atlz_estoque']}</td>";
                echo "<td>{$row['ATLZ_SALDO_AUX']}</td>";
                echo "<td>{$row['ATLZ_SALDO_PRI']}</td>";
                echo "<td>" . number_format($row['QTDE_PRI'], 3, '.', '') . "</td>";
				echo "<td>" . number_format($row['QTDE_AUX'], 3, '.', '') . "</td>";
				echo "<td>{$row['Valor_unitario']}</td>";
				echo "<td>{$row['Valor_total']}</td>";
                echo "<td>{$row['COD_UNIDADE_PRI']}</td>";
                echo "<td>{$row['NUM_LOTE']}</td>";
                echo "<td>{$row['CHAVE_FATO_MP']}</td>";
                echo "<td>{$row['COD_UNIDADE_AUX']}</td>";
                echo "<td>{$row['ORDEM']}</td>";
                echo "<td>{$row['TIPO']}</td>";
				echo "<td>{$row['DESC_MOTIVO']}</td>";
                echo "</tr>";
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
            paging: false, // Remove paginação
            buttons: [{
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