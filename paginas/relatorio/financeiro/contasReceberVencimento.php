<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default'; // Presumindo que o ID do usuário está salvo na sessão, use 'default' se não estiver logado
$filterFile = __DIR__ . "/filters/user_{$userId}_contarecvenc.txt";

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
        'tbFormaCob' => $_POST['tbFormaCob'] ?? [],
        'grupoEconomico' => $_POST['grupoEconomico'] ?? [],
        'fornecedor' => $_POST['fornecedor'] ?? [],
        'emissao_de' => $_POST['emissao_de'] ?? '',
        'emissao_ate' => $_POST['emissao_ate'] ?? '',
        'vencimento_de' => $_POST['vencimento_de'] ?? '',
        'vencimento_ate' => $_POST['vencimento_ate'] ?? '',
        'situacao' => $_POST['situacao'] ?? '',
        'status' => $_POST['status'] ?? '',
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
            <h3>CONTAS A RECEBER POR VENCIMENTO</h3>
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
                <label for="tbFormaCob-select" class="col-sm-2 col-form-label text-right">Forma de Pagamento:</label>
                <div class="col-sm-10">
                    <select id="tbFormaCob-select" name="tbFormaCob[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT DISTINCT A.Cod_forma_cob AS DOC, B.Desc_forma_cob AS NOME FROM tbTituloRec A
                        INNER JOIN tbFormaCob B ON A.Cod_forma_cob = B.Cod_forma_cob
                        ");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['DOC'], $savedFilters['tbFormaCob'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['DOC']}' {$selected}>{$r['DOC']} - {$r['NOME']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <!-- Tipo de Documento -->
            <div class="form-group row">
                <label for="fornecedor-select" class="col-sm-2 col-form-label text-right">Fornecedor:</label>
                <div class="col-sm-10">
                    <select id="fornecedor-select" name="fornecedor[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT DISTINCT A.Cod_cadastro AS Cod_Cadastro, A.Nome_cadastro AS Nome_Cadastro FROM tbCadastroGeral A WHERE A.Tipo_cadastro = 'F'
                        ");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_Cadastro'], $savedFilters['fornecedor'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_Cadastro']}' {$selected}>{$r['Cod_Cadastro']} - {$r['Nome_Cadastro']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label for="grupoEconomico-select" class="col-sm-2 col-form-label text-right">Grupo Economico:</label>
                <div class="col-sm-10">
                    <select id="grupoEconomico-select" name="grupoEconomico[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT * FROM tbGrupoLimite
                        ");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_grupo_limite'], $savedFilters['grupoEconomico'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_grupo_limite']}' {$selected}>{$r['Cod_grupo_limite']} - {$r['Nome']}</option>";
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
                <label for="status" class="col-sm-2 col-form-label text-right">Status:</label>
                <div class="col-sm-10">
                    <select id="status" name="status" class="form-control" required>
                        <option value="R" <?= ($savedFilters['status'] ?? '') === 'R' ? 'selected' : '' ?>>Realizado</option>
                        <option value="L" <?= ($savedFilters['status'] ?? '') === 'L' ? 'selected' : '' ?>>Previsto</option>
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
        <p><strong>Forma Pagamento:</strong>
            <?php
            echo isset($_POST['tbFormaCob']) && !empty($_POST['tbFormaCob'])
                ? implode(', ', $_POST['tbFormaCob'])
                : 'TODOS';
            ?>
        </p>
        <p><strong>Fornecedor:</strong>
            <?php
            echo isset($_POST['fornecedor']) && !empty($_POST['fornecedor'])
                ? implode(', ', $_POST['fornecedor'])
                : 'TODOS';
            ?>
        </p>
        <p><strong>Grupo Economico:</strong>
            <?php
            echo isset($_POST['grupoEconomico']) && !empty($_POST['grupoEconomico'])
                ? implode(', ', $_POST['grupoEconomico'])
                : 'NENHUM';
            ?>
        </p>
        <p><strong>Data de Emissão:</strong>
            <?php
            echo (!empty($_POST['emissao_de']) ? $_POST['emissao_de'] : 'TODAS') .
                ' até ' .
                (!empty($_POST['emissao_ate']) ? $_POST['emissao_ate'] : 'TODAS');
            ?>
        </p>
        <p><strong>Data de Vencimento:</strong>
            <?php
            echo (!empty($_POST['vencimento_de']) ? $_POST['vencimento_de'] : 'TODAS') .
                ' até ' .
                (!empty($_POST['vencimento_ate']) ? $_POST['vencimento_ate'] : 'TODAS');
            ?>
        </p>
        <p><strong>Situação:</strong>
            <?php
            echo ($_POST['situacao'] === 'A' ? 'Aberto' : ($_POST['situacao'] === 'L' ? 'Liquidado' : 'TODAS'));
            ?>
        </p>
        <p><strong>Status:</strong>
            <?php
            echo ($_POST['status'] === 'R' ? 'Realizado' : ($_POST['status'] === 'P' ? 'Previsto' : 'TODAS'));
            ?>
        </p>
    </div>
<?php endif; ?>

<!-- Tabela -->
<div class="table-container">
    <?php
    if (isset($_POST['gerarRelatorio'])):
        $filiais = isset($_POST['filial']) ? $_POST['filial'] : [];
        $bancos = isset($_POST['banco']) ? $_POST['banco'] : [];
        $tbFormaCob = isset($_POST['tbFormaCob']) ? $_POST['tbFormaCob'] : [];
        $fornecedor = isset($_POST['fornecedor']) ? $_POST['fornecedor'] : [];
        $grupoEconomico = isset($_POST['grupoEconomico']) ? $_POST['grupoEconomico'] : [];
        $emissao_de = $_POST['emissao_de'] ?? '';
        $emissao_ate = $_POST['emissao_ate'] ?? '';
        $vencimento_de = $_POST['vencimento_de'] ?? '';
        $vencimento_ate = $_POST['vencimento_ate'] ?? '';
        $situacao = $_POST['situacao'] ?? '';
        $status = $_POST['status'] ?? '';

    // Lógica para pegar Cod_cadastro associado ao grupo econômico
    $codCadastros = [];
    if (!empty($grupoEconomico)) {
        $grupoEconomicoList = implode("','", $grupoEconomico);

        $queryGrupoEconomico = "
            SELECT C.Cod_cadastro
            FROM tbGrupoLimite A
            INNER JOIN tbCliente B ON A.Cod_grupo_limite = B.Cod_grupo_limite
            INNER JOIN tbCadastroGeral C ON B.Cod_cadastro = C.Cod_cadastro
            WHERE A.Cod_grupo_limite IN ('{$grupoEconomicoList}')
        ";

        try {
            $stmtGrupoEconomico = $pdoS->query($queryGrupoEconomico);
            $codCadastros = $stmtGrupoEconomico->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            echo "<p>Erro ao buscar Cod_cadastro do Grupo Econômico: " . $e->getMessage() . "</p>";
        }
    }

        $sql = "SELECT ";
            $sql .= "A.Cod_filial as FILIAL, A.Cod_docto AS COD_DOCTO, A.Num_docto AS TITULO, A.Cod_banco_caixa AS CODIGO, B.Nome_agencia AS NOME, A.Valor_saldo AS SALDO, A.Valor_titulo AS VALOR, A.Data_emissao AS DATA_EMISSAO, A.Data_vencto AS DATA_VENCIMENTO, A.Cod_cliente AS FORNECEDOR, C.Nome_cadastro AS NOME_FORNECEDOR";
        $sql .= "
            FROM tbTituloRec A
            INNER JOIN tbBancoCaixa B ON A.Cod_banco_caixa = B.Cod_banco_caixa
            INNER JOIN TBCADASTROGERAL C ON A.Cod_cliente = C.Cod_cadastro
            WHERE A.Status_titulo = '" . $situacao . "' AND A.Natureza_titulo = '".$status."'
        ";

        if (!empty($filiais)) {
            $sql .= " AND A.Cod_filial IN ('" . implode("','", $filiais) . "')";
        }

        if (!empty($bancos)) {
            $sql .= " AND A.Cod_banco_caixa IN ('" . implode("','", $bancos) . "')";
        }
        if (!empty($tbFormaCob)) {
            $sql .= " AND A.Cod_forma_cob IN ('" . implode("','", $tbFormaCob) . "')";
        }
        if (!empty($fornecedor)) {
            $sql .= " AND A.Cod_cliente IN ('" . implode("','", $fornecedor) . "')";
        }
        if (!empty($codCadastros)) {
            $sql .= " AND A.Cod_cliente IN ('" . implode("','", $codCadastros) . "')";
        }
        if (!empty($emissao_de)) {
            $sql .= " AND A.Data_emissao >= '$emissao_de'";
        }
        if (!empty($emissao_ate)) {
            $sql .= " AND A.Data_emissao <= '$emissao_ate'";
        }
        if (!empty($vencimento_de)) {
            $sql .= " AND A.Data_vencto >= '$vencimento_de'";
        }
        if (!empty($vencimento_ate)) {
            $sql .= " AND A.Data_vencto <= '$vencimento_ate'";
        }
        try {
            $stmt = $pdoS->query($sql);

            echo "<h4>Relatório Gerado</h4>";
            echo "<table id='relatorioTabela' class='table table-striped table-bordered'>";
            echo "<thead>";

                echo "<tr><th>FILIAL</th><th>TIPO</th><th>TITULO</th><th>COD</th><th>RAZAO SOCIAL</th><th style='text-align: right;'>VALOR</th><th style='text-align: right;'>SALDO</th></tr>";
            
            echo "</thead><tbody>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                    echo "<td>{$row['FILIAL']}</td>";
                    echo "<td>{$row['COD_DOCTO']}</td>";
                    echo "<td>{$row['TITULO']}</td>";
                    echo "<td>{$row['FORNECEDOR']}</td>";
                    echo "<td>{$row['NOME_FORNECEDOR']}</td>";
                    echo "<td style='text-align: right;'>{$row['VALOR']}</td>";
                    echo "<td style='text-align: right;'>{$row['SALDO']}</td>";
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