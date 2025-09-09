<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default'; // Presumindo que o ID do usuário está salvo na sessão, use 'default' se não estiver logado
$filterFile = __DIR__ . "/filters/user_{$userId}_resaldorec.txt";

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
        'banco' => $_POST['banco'] ?? [],
        'tipoDocto' => $_POST['tipoDocto'] ?? [],
        'formaCob' => $_POST['formaCob'] ?? [],
        'emissao_de' => $_POST['emissao_de'] ?? '',
        'emissao_ate' => $_POST['emissao_ate'] ?? '',
        'vencimento_de' => $_POST['vencimento_de'] ?? '',
        'vencimento_ate' => $_POST['vencimento_ate'] ?? '',
        'situacao' => $_POST['situacao'] ?? '',
        'tipo_relatorio' => $_POST['tipo_relatorio'] ?? 'sintetico',
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
            <h3>RESUMO DE SALDO (CONTAS A RECEBER)</h3>
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
            <!-- Seleção de Banco -->
            <div class="form-group row">
                <label for="banco-select" class="col-sm-2 col-form-label text-right">Escolha o Banco:</label>
                <div class="col-sm-10">
                    <select id="banco-select" name="banco[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT * FROM tbBancoCaixa");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_banco_caixa'], $savedFilters['banco'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_banco_caixa']}' {$selected}>{$r['Cod_banco_caixa']} - {$r['Nome_agencia']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label for="formaCob-select" class="col-sm-2 col-form-label text-right">Forma de Cobrança:</label>
                <div class="col-sm-10">
                    <select id="formaCob-select" name="formaCob[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT DISTINCT A.Cod_forma_cob AS DOC, B.Desc_forma_cob AS NOME FROM tbTituloRec A
                        INNER JOIN tbFormaCob B ON A.Cod_forma_cob = B.Cod_forma_cob
                        ");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['DOC'], $savedFilters['formaCob'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['DOC']}' {$selected}>{$r['DOC']} - {$r['NOME']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <!-- Tipo de Documento -->
            <div class="form-group row">
                <label for="tipoDocto-select" class="col-sm-2 col-form-label text-right">Tipo de Documento:</label>
                <div class="col-sm-10">
                    <select id="tipoDocto-select" name="tipoDocto[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT DISTINCT A.Cod_docto AS DOC, B.Desc_documento AS NOME FROM tbTituloRec A
                        INNER JOIN tbTipoDocumento B ON A.Cod_docto = B.Cod_docto
                        ");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['DOC'], $savedFilters['tipoDocto'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['DOC']}' {$selected}>{$r['DOC']} - {$r['NOME']}</option>";
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
            <!-- Data de Vencimento -->
            <div class="form-group row">
                <label class="col-sm-2 col-form-label text-right">Data de Vencimento:</label>
                <div class="col-sm-5">
                    <input type="date" name="vencimento_de" class="form-control" value="<?= $savedFilters['vencimento_de'] ?? '' ?>">
                </div>
                <div class="col-sm-5">
                    <input type="date" name="vencimento_ate" class="form-control" value="<?= $savedFilters['vencimento_ate'] ?? '' ?>">
                </div>
            </div>
            <!-- Situação -->
            <div class="form-group row">
                <label for="situacao" class="col-sm-2 col-form-label text-right">Situação:</label>
                <div class="col-sm-10">
                    <select id="situacao" name="situacao" class="form-control">
                        <option value="A" <?= ($savedFilters['situacao'] ?? '') === 'A' ? 'selected' : '' ?>>Aberto</option>
                        <option value="L" <?= ($savedFilters['situacao'] ?? '') === 'L' ? 'selected' : '' ?>>Liquidado</option>
                    </select>
                </div>
            </div>
            <!-- Tipo de Relatório -->
            <div class="form-group row">
                <label for="tipo-relatorio" class="col-sm-2 col-form-label text-right">Tipo de Relatório:</label>
                <div class="col-sm-10">
                    <select id="tipo-relatorio" name="tipo_relatorio" class="form-control" required>
                        <option value="sintetico" <?= ($savedFilters['tipo_relatorio'] ?? '') === 'sintetico' ? 'selected' : '' ?>>Sintético</option>
                        <option value="analitico" <?= ($savedFilters['tipo_relatorio'] ?? '') === 'analitico' ? 'selected' : '' ?>>Analítico</option>
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
        <p><strong>Banco:</strong>
            <?php
            echo isset($_POST['banco']) && !empty($_POST['banco'])
                ? implode(', ', $_POST['banco'])
                : 'TODOS';
            ?>
        </p>
        <p><strong>Forma de Cobrança:</strong>
            <?php
            echo isset($_POST['formaCob']) && !empty($_POST['formaCob'])
                ? implode(', ', $_POST['formaCob'])
                : 'TODOS';
            ?>
        </p>
        <p><strong>Data de Vencimento:</strong>
            <?php
            echo (!empty($_POST['vencimento_de']) ? $_POST['vencimento_de'] : 'TODAS') .
                ' até ' .
                (!empty($_POST['vencimento_ate']) ? $_POST['vencimento_ate'] : 'TODAS');
            ?>
        </p>
        <p><strong>Tipo de Documento:</strong>
            <?php
            echo isset($_POST['tipoDocto']) && !empty($_POST['tipoDocto'])
                ? implode(', ', $_POST['tipoDocto'])
                : 'TODOS';
            ?>
        </p>
        <p><strong>Forma de Cobrança:</strong>
            <?php
            echo isset($_POST['formaCob']) && !empty($_POST['formaCob'])
                ? implode(', ', $_POST['formaCob'])
                : 'TODOS';
            ?>
        </p>
        <p><strong>Data de Emissão:</strong>
            <?php
            echo (!empty($_POST['emissao_de']) ? $_POST['emissao_de'] : 'TODAS') .
                ' até ' .
                (!empty($_POST['emissao_ate']) ? $_POST['emissao_ate'] : 'TODAS');
            ?>
        </p>
        <p><strong>Situação:</strong>
            <?php
            echo ($_POST['situacao'] === 'A' ? 'Aberto' : ($_POST['situacao'] === 'L' ? 'Liquidado' : 'TODAS'));
            ?>
        </p>
    </div>
<?php endif; ?>

<!-- Tabela -->
<div class="table-container">
    <?php
    if (isset($_POST['gerarRelatorio'])):
        $tipo_relatorio = $_POST['tipo_relatorio'] ?? 'sintetico';
        $filiais = isset($_POST['filial']) ? $_POST['filial'] : [];
        $bancos = isset($_POST['banco']) ? $_POST['banco'] : [];
        $vencimento_de = $_POST['vencimento_de'] ?? '';
        $vencimento_ate = $_POST['vencimento_ate'] ?? '';
        $tipoDocto = isset($_POST['tipoDocto']) ? $_POST['tipoDocto'] : [];
        $formaCob = isset($_POST['formaCob']) ? $_POST['formaCob'] : [];
        $emissao_de = $_POST['emissao_de'] ?? '';
        $emissao_ate = $_POST['emissao_ate'] ?? '';
        $situacao = $_POST['situacao'] ?? '';

        $sql = "SELECT ";

        if ($tipo_relatorio === 'sintetico') {
            $sql .= "A.Cod_banco_caixa AS CODIGO, MAX(B.Nome_agencia) AS NOME, SUM(A.Valor_saldo) AS SALDO, COUNT(*) AS QUANTIDADE_TITULOS";
        } else {
            $sql .= "A.Num_docto AS TITULO, A.Cod_banco_caixa AS CODIGO, B.Nome_agencia AS NOME, A.Valor_saldo AS VALOR, A.Data_emissao AS DATA_EMISSAO, A.Data_vencto AS DATA_VENCIMENTO";
        }

        $sql .= "
            FROM tbTituloRec A
            INNER JOIN tbBancoCaixa B ON A.Cod_banco_caixa = B.Cod_banco_caixa
            WHERE A.Status_titulo = '" . $situacao . "'
        ";

        if (!empty($filiais)) {
            $sql .= " AND A.Cod_filial IN ('" . implode("','", $filiais) . "')";
        }

        if (!empty($bancos)) {
            $sql .= " AND A.Cod_banco_caixa IN ('" . implode("','", $bancos) . "')";
        }

        if (!empty($vencimento_de)) {
            $sql .= " AND A.Data_vencto >= '$vencimento_de'";
        }

        if (!empty($vencimento_ate)) {
            $sql .= " AND A.Data_vencto <= '$vencimento_ate'";
        }

        if (!empty($tipoDocto)) {
            $sql .= " AND A.Cod_docto IN ('" . implode("','", $tipoDocto) . "')";
        }

        if (!empty($formaCob)) {
            $sql .= " AND A.Cod_forma_cob IN ('" . implode("','", $formaCob) . "')";
        }

        if (!empty($emissao_de)) {
            $sql .= " AND A.Data_emissao >= '$emissao_de'";
        }

        if (!empty($emissao_ate)) {
            $sql .= " AND A.Data_emissao <= '$emissao_ate'";
        }

        if ($tipo_relatorio === 'sintetico') {
            $sql .= " GROUP BY A.Cod_banco_caixa";
        }

        try {
            $stmt = $pdoS->query($sql);

            echo "<h4>Relatório Gerado</h4>";
            echo "<table id='relatorioTabela' class='table table-striped table-bordered'>";
            echo "<thead>";
            if ($tipo_relatorio === 'sintetico') {
                echo "<tr><th>CODIGO</th><th>NOME</th><th>SALDO</th><th>QUANTIDADE DE TÍTULOS</th></tr>";
            } else {
                echo "<tr><th>TÍTULO</th><th>CODIGO</th><th>NOME</th><th style='text-align: right;'>VALOR</th><th>DATA EMISSÃO</th><th>DATA VENCIMENTO</th></tr>";
            }
            echo "</thead><tbody>";

            $total_valor = 0;

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
                    echo "<td>" . (!empty($row['DATA_EMISSAO']) ? date('d/m/Y', strtotime($row['DATA_EMISSAO'])) : '-') . "</td>";
                    echo "<td>" . (!empty($row['DATA_VENCIMENTO']) ? date('d/m/Y', strtotime($row['DATA_VENCIMENTO'])) : '-') . "</td>";
                    $total_valor += $row['VALOR'];
                }
                echo "</tr>";
            }

            if ($tipo_relatorio === 'analitico') {
                // Totalizador para Analítico
                echo "<tr style='font-weight: bold;'>";
                echo "<td colspan='3' style='text-align: right;'>Total:</td>";
                echo "<td style='text-align: right;'>" . number_format($total_valor, 2, ',', '.') . "</td>";
                echo "<td></td>";
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