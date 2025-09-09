<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default'; // Presumindo que o ID do usuário está salvo na sessão, use 'default' se não estiver logado
$filterFile = __DIR__ . "/filters/user_{$userId}_comercial.txt";

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
        'tipo_mv' => $_POST['tipo_mv'] ?? [],
        'localestoque' => $_POST['localestoque'] ?? [],
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
            <h3>RELATORIO NOTAS FISCAIS</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
            <!-- Seleção de Filial -->
            <div class="form-group row">
                <label for="filial-select" class="col-sm-2 col-form-label text-right">Escolha a Filial:</label>
                <div class="col-sm-10">
                    <select id="filial-select" name="filial[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT * FROM tbFilial WHERE Cod_filial IN ('100','200')");
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
                <label for="tipo_mv-select" class="col-sm-2 col-form-label text-right">Escolha o Movimento:</label>
                <div class="col-sm-10">
                    <select id="tipo_mv-select" name="tipo_mv[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT
                                            DISTINCT A.COD_TIPO_MV, 
                                            B.Descricao FROM TBSAIDAS A 
                                            INNER JOIN tbTipoMvEstoque B ON A.Cod_tipo_mv = B.Cod_tipo_mv
                                            WHERE A.COD_DOCTO = 'NE' AND A.COD_FILIAL IN ('100','200') AND A.COD_TIPO_MV NOT IN ('T525','T728','T828','T826','T820','T821','T185','T187','T531','T532','T535','T827')");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['COD_TIPO_MV'], $savedFilters['tipo_mv'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['COD_TIPO_MV']}' {$selected}>{$r['COD_TIPO_MV']} - {$r['Descricao']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
                        <!-- Seleção de Banco -->
                        <div class="form-group row">
                <label for="localestoque-select" class="col-sm-2 col-form-label text-right">Local Estoque:</label>
                <div class="col-sm-10">
                    <select id="localestoque-select" name="localestoque[]" class="selectpicker form-control" multiple data-live-search="true" title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT DISTINCT Desc_local,Cod_local FROM tbLocalEstoque WHERE COD_FILIAL IN ('100','200') AND Cod_local IN ('01','02','03','04','05','12','13','14') ORDER BY Cod_local ASC");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_local'], $savedFilters['localestoque'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_local']}' {$selected}>{$r['Cod_local']} - {$r['Desc_local']}</option>";
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
        <p><strong>Filiais:</strong>
            <?php
            echo isset($_POST['filial']) && !empty($_POST['filial'])
                ? implode(', ', $_POST['filial'])
                : 'TODAS';
            ?>
        </p>
        <p><strong>Tipo Movimento:</strong>
            <?php
            echo isset($_POST['tipo_mv']) && !empty($_POST['tipo_mv'])
                ? implode(', ', $_POST['tipo_mv'])
                : 'TODOS';
            ?>
        </p>
        <p><strong>Local Estoque:</strong>
            <?php
            echo isset($_POST['localestoque']) && !empty($_POST['localestoque'])
                ? implode(', ', $_POST['localestoque'])
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
    </div>
<?php endif; ?>

<!-- Tabela -->
<div class="table-container">
    <?php
    if (isset($_POST['gerarRelatorio'])):
        $filiais = isset($_POST['filial']) ? $_POST['filial'] : [];
        $tipo_mv = isset($_POST['tipo_mv']) ? $_POST['tipo_mv'] : [];
        $localEstoque = isset($_POST['localestoque']) ? $_POST['localestoque'] : [];
        $emissao_de = $_POST['emissao_de'] ?? '';
        $emissao_ate = $_POST['emissao_ate'] ?? '';

        $sql = "SELECT ";
     
            $sql .= "DISTINCT A.Num_docto, B.Nome_Cadastro AS Cliente, A.Cod_vend_comp, B.Uf, A.Peso_bruto, A.Valor_produtos, C.Nome_Cadastro AS Vendedor, A.Cod_tipo_mv";

            $sql .= "
            FROM TBSAIDAS A 
            INNER JOIN tbCadastroGeral B ON A.Cod_cli_for = B.Cod_cadastro
            LEFT JOIN tbCadastroGeral C ON A.Cod_vend_comp = C.Cod_cadastro
            INNER JOIN (
            SELECT Chave_fato, Cod_local
            FROM TBSAIDASITEM ";
            if (!empty($localEstoque)) {
              $sql .= "WHERE Qtde_und > 0 AND Cod_local IN ('" . implode("','", $localEstoque) . "')";
          }
          $sql .= " 
            GROUP BY Chave_fato, Cod_local
            ) E ON A.Chave_fato_orig_un = E.Chave_fato
            WHERE A.COD_DOCTO = 'NE'
            AND COD_TIPO_MV NOT IN ('T525','T728','T828','T826','T820','T821','T185','T187','T531','T532','T535','T827')
            AND A.STATUS <> 'C'
";

        if (!empty($filiais)) {
            $sql .= " AND A.Cod_filial IN ('" . implode("','", $filiais) . "')";
        }

        if (!empty($tipo_mv)) {
            $sql .= " AND A.Cod_tipo_mv IN ('" . implode("','", $tipo_mv) . "')";
        }
        if (!empty($emissao_de)) {
            $sql .= " AND A.Data_v1 >= '$emissao_de'";
        }

        if (!empty($emissao_ate)) {
            $sql .= " AND A.Data_v1 <= '$emissao_ate'";
        }

        try {
            $stmt = $pdoS->query($sql);

            echo "<table id='tabela' class='table table-striped table-bordered'>";
            echo "<thead>";
                echo "<tr><th>N°</th><th>TMV</th><th>CLIENTE</th><th>VENDEDOR</th><th>ESTADO</th><th>KG</th><th style='text-align: right;'>VALOR</th></tr>";
            echo "</thead>";
            echo "<tbody>";
            
            $total_valor = 0;
            $total_peso = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                    echo "<td>{$row['Num_docto']}</td>";
                    echo "<td>{$row['Cod_tipo_mv']}</td>";
                    echo "<td>{$row['Cliente']}</td>";
                    echo "<td>{$row['Vendedor']}</td>";
                    echo "<td>{$row['Uf']}</td>";
                    echo "<td>" . number_format($row['Peso_bruto'], 2, ',', '.') . "</td>";
                    echo "<td style='text-align: right;'>" . number_format($row['Valor_produtos'], 2, ',', '.') . "</td>";
                echo "</tr>";
                $total_valor += $row['Valor_produtos'];
                $total_peso += $row['Peso_bruto'];
            }
            
            echo "</tbody>";
            
            // Adicionando o rodapé com os totais
            echo "<tfoot>";
            echo "<tr style='font-weight: bold;'>";
            echo "<td colspan='5' style='text-align: right;'>Total:</td>";
            echo "<td style='text-align: right;'>" . number_format($total_peso, 2, ',', '.') . "</td>";
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