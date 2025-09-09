<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default'; // Presumindo que o ID do usuário está salvo na sessão, use 'default' se não estiver logado
$filterFile = __DIR__ . "/filters/user_{$userId}_comercial1.txt";

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
function clearFilters($filePath)
{
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
        'localestoque' => $_POST['localEstoque'] ?? [],
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
<?php 
$filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; // fallback para 100
$locaisPermitidosPorFilial = [
    '100' => ['01'],
    '200' => ['01', '03', '04', '02', '13', '14', '12', '05'],
    '400' => ['01'],
];
$locaisPermitidos = $locaisPermitidosPorFilial[$filial] ?? [];
$codigosIn = implode(',', array_map(fn($cod) => "'" . $cod . "'", $locaisPermitidos));
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            <h3>RELATORIO NOTAS FISCAIS POR PRODUTOS</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
            <!-- Seleção de Banco -->
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
            <!-- Data de Emissão -->
            <div class="form-group row">
                <label class="col-sm-2 col-form-label text-right">Data de Emissão:</label>
                <div class="col-sm-5">
                    <input type="date" name="emissao_de" class="form-control"
                        value="<?= $savedFilters['emissao_de'] ?? '' ?>">
                </div>
                <div class="col-sm-5">
                    <input type="date" name="emissao_ate" class="form-control"
                        value="<?= $savedFilters['emissao_ate'] ?? '' ?>">
                </div>
            </div>
			            <div class="form-group">
                <label class="col-sm-2 control-label">Tipo de Venda:</label>
                <div class="col-sm-10">
                    <select name="tipo_venda" class="selectpicker form-control" title="Selecione uma ou mais opções">
                        <option value="TODOS" <?= ($savedFilters['tipo_venda'] ?? '') == 'TODOS' ? 'selected' : '' ?>>Todos</option>
                        <option value="MI" <?= ($savedFilters['tipo_venda'] ?? '') == 'MI' ? 'selected' : '' ?>>Venda Mercado Interno</option>
                        <option value="ME" <?= ($savedFilters['tipo_venda'] ?? '') == 'ME' ? 'selected' : '' ?>>Venda Mercado Externo</option>
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
        <p><strong>Local Estoque:</strong>
            <?php
            echo isset($_POST['localEstoque']) && !empty($_POST['localestoque'])
                ? implode(', ', $_POST['localEstoque'])
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
    $localestoque = $_POST['localEstoque'] ?? [];
    $emissao_de = $_POST['emissao_de'] ?? '';
    $emissao_ate = $_POST['emissao_ate'] ?? '';
	$tipo_venda = $_POST['tipo_venda'] ?? 'TODOS';
			$_SESSION['tipoVenda'] = $tipo_venda;
		
		$_SESSION['localestoque'] = $_POST['localEstoque'];
		$jsonSeason = json_encode($_SESSION['localestoque']);



    $sql = "SELECT 
        B.Cod_produto,
        SUM(B.Qtde_pri) AS PESO,
        SUM(B.Qtde_aux) AS CX,
        AVG(B.Valor_unitario) AS MEDIA,
        MAX(tbp.Desc_produto_est) AS NOME,
        SUM(B.Valor_total) AS VALOR,
        MAX(A.Cod_filial) AS COD_FILIAL
        FROM tbSaidas A
        INNER JOIN tbSaidasItem B ON A.CHAVE_FATO = B.CHAVE_FATO AND B.Num_subItem = 0
        INNER JOIN tbProduto tbp ON B.Cod_produto = tbp.Cod_produto
		INNER JOIN TBTIPOMVESTOQUE D ON A.COD_TIPO_MV = D.COD_TIPO_MV AND A.COD_DOCTO = D.COD_DOCTO
        WHERE 
            A.COD_DOCTO IN ('NE') 
            AND A.Cod_filial = ?
            AND B.Cod_produto BETWEEN '20000' AND '39999' 
            AND B.Qtde_pri > 0 
            AND A.Status <> 'C' ";

    $params = [$filial];
if ($tipo_venda === 'MI') {
    // Remove T525 de forma incondicional, antes de qualquer lógica de OR
    $sql .= " AND A.Cod_tipo_mv <> 'T525'";
    $sql .= " AND (D.Perfil_tmv IN ('VDA0301') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
} elseif ($tipo_venda === 'ME') {
    $sql .= " AND D.Perfil_tmv IN ('VDA0302')";
} else {
	$sql .= " AND A.Cod_tipo_mv <> 'T525'";
    $sql .= " AND (D.Perfil_tmv IN ('VDA0301','VDA0302') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
}

    if (!empty($localEstoque)) {
        $sql .= " AND B.Cod_local IN (" . implode(",", array_fill(0, count($localEstoque), "?")) . ")";
        $params = array_merge($params, $localEstoque);
    }
    if (!empty($emissao_de)) {
        $sql .= " AND A.Data_v1 >= ?";
        $params[] = $emissao_de;
    }
    if (!empty($emissao_ate)) {
        $sql .= " AND A.Data_v1 <= ?";
        $params[] = $emissao_ate;
    }
    
    $sql .= " GROUP BY B.Cod_produto ";

    try {
        $stmt = $pdoS->prepare($sql);
        $stmt->execute($params);

        echo "<table id='tabela' class='table table-striped table-bordered'>";
        echo "<thead>";
        echo "<tr>
                <th>PRODUTO</th>
                <th>NOME</th>
                <th>CX</th>
                <th>KG</th>
                <th>MÉDIA</th>
                <th style='text-align: right;'>VALOR</th>
                <th>AÇÃO</th>
              </tr>";
        echo "</thead>";
        echo "<tbody>";

        $totalCX = 0;
        $totalPeso = 0;
        $totalValor = 0;
		
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $media = $row['PESO'] > 0 ? $row['VALOR'] / $row['PESO'] : 0;

            // Acumulando os totais
            $totalCX += $row['CX'];
            $totalPeso += $row['PESO'];
            $totalValor += $row['VALOR'];

            echo "<tr class='product-row' data-product-id='{$row['Cod_produto']}'>";
            echo "<td>{$row['Cod_produto']}</td>";
            echo "<td>{$row['NOME']}</td>";
            echo "<td>" . number_format($row['CX'], 0, ',', '.') . "</td>"; 
            echo "<td>" . number_format($row['PESO'], 2, ',', '.') . "</td>"; 
            echo "<td>" . number_format($media, 2, ',', '.') . "</td>"; 
            echo "<td style='text-align: right;'>" . number_format($row['VALOR'], 2, ',', '.') . "</td>"; 
			
echo "<td>
<div class='btn-group' role='group' style='display: flex; gap: 5px;'>
    <button class='btn btn-sm btn-info btn-sm open-modal' 
        data-cod-produto='{$row['Cod_produto']}' 
        data-cod-filial='{$row['COD_FILIAL']}' 
        data-emissao-de='" . htmlspecialchars($emissao_de, ENT_QUOTES, 'UTF-8') . "' 
        data-emissao-ate='" . htmlspecialchars($emissao_ate, ENT_QUOTES, 'UTF-8') . "' 
        data-localestoque='" . htmlspecialchars($jsonSeason, ENT_QUOTES, 'UTF-8') . "' 
		data-tipovenda='" . htmlspecialchars($_SESSION['tipoVenda'], ENT_QUOTES, 'UTF-8') . "' 
        data-toggle='modal' 
        data-target='#notafiscalModal'>
         <i class='glyphicon glyphicon-search'></i>
    </button>
	    <button class='btn btn-sm btn-info btn-sm' 
        data-cod-produto='{$row['Cod_produto']}' 
        data-cod-filial='{$row['COD_FILIAL']}' 
        data-emissao-de='" . htmlspecialchars($emissao_de, ENT_QUOTES, 'UTF-8') . "' 
        data-emissao-ate='" . htmlspecialchars($emissao_ate, ENT_QUOTES, 'UTF-8') . "' 
        data-localestoque='" . htmlspecialchars($jsonSeason, ENT_QUOTES, 'UTF-8') . "' 
		data-tipovenda='" . htmlspecialchars($_SESSION['tipoVenda'], ENT_QUOTES, 'UTF-8') . "' 
        data-toggle='modal' 
        data-target='#produtosModal'>
        <i class='glyphicon glyphicon-list-alt'></i>
    </button>
	</div>
</td>";



            echo "</tr>";
        }
        echo "</tbody>";

        $mediaGeral = $totalPeso > 0 ? $totalValor / $totalPeso : 0;

        // Rodapé com os totais
        echo "<tfoot>";
        echo "<tr style='font-weight: bold;'>";
        echo "<td colspan='2' style='text-align: right;'>Total:</td>";
        echo "<td>" . number_format($totalCX, 1, ',', '.') . "</td>"; 
        echo "<td>" . number_format($totalPeso, 2, ',', '.') . "</td>"; 
        echo "<td>" . number_format($mediaGeral, 2, ',', '.') . "</td>"; 
        echo "<td style='text-align: right;'>" . number_format($totalValor, 2, ',', '.') . "</td>"; 
        echo "<td></td>";
        echo "</tr>";
        echo "</tfoot>";
        echo "</table>";

    } catch (PDOException $e) {
        echo "<p>Erro ao conectar ou consultar o banco de dados: " . $e->getMessage() . "</p>";
    }
endif;
?>

</div>
<div id="produtosModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="estoqueModalLabel">
    <div class="modal-dialog" role="document" style="width: 90%; max-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="estoqueModalLabel">Detalhes do Estoque</h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-striped text-center">
                    <thead>
                        <tr>
							<th class="text-center">Codigo</th>
							<th class="text-center">Nomenclatura</th>
                            <th class="text-center">Data Produção</th>
                            <th class="text-center">Validade</th>
                            <th class="text-center">Caixas</th>
                            <th class="text-center">Peso Líquido</th>
                            <th class="text-center">Peso Bruto</th>
                        </tr>
                    </thead>
                    <tbody id="estoqueDetails">
                        <!-- Preenchido via AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer text-center">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="notafiscalModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="productModalLabel">
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
                            <th>Produto</th>
                            <th>Peso</th>
                            <th>TMV</th>
                            <th>N°Pedido</th>
                            <th>Preço</th>
                            <th>Cliente</th>
                            <th>Data Embarque</th>
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
$(document).on('click', '.open-modal', function() {
    var button = $(this);
    var codProduto = button.data('cod-produto');
    var codFilial = button.data('cod-filial');
    var emissaoDe = button.data('emissao-de');
    var emissaoAte = button.data('emissao-ate');
    var localEstoque = button.data('localestoque');
	var tipoVenda = button.data('tipovenda');

    console.log("Código do Produto:", codProduto);
    console.log("Código da Filial:", codFilial);
    console.log("Data de Emissão (De):", emissaoDe);
    console.log("Data de Emissão (Até):", emissaoAte);
    console.log("Locais de Estoque:", localEstoque);

    $.ajax({
        url: 'detalhes_pedidos_nf.php',
        type: 'GET',
        data: {
            cod_produto: codProduto,
            cod_filial: codFilial,
            emissao_de: emissaoDe,
            emissao_ate: emissaoAte,
            localestoque: localEstoque, // Enviando como array
			tipovenda: tipoVenda
        },
        success: function(response) {
            console.log('Resposta recebida:', response);
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            var tableContent = '';

            if (Array.isArray(data) && data.length > 0) {
                data.forEach(function(item) {
                    tableContent += `
                    <tr>
                        <td>${item.Cod_produto ? item.Cod_produto.trim() : ''}</td>
                        <td>${item.Qtde_pri ? parseFloat(item.Qtde_pri).toLocaleString('pt-BR', { minimumFractionDigits: 3 }) : ''}</td>
                        <td>${item.Cod_tipo_mv || ''}</td>
                        <td>${item.Num_docto || ''}</td>
                        <td>${item.Valor_unitario ? parseFloat(item.Valor_unitario).toFixed(2) : ''}</td>
                        <td>${item.Cliente || ''}</td>
                        <td>${item.Data_v2 ? item.Data_v2.split(' ')[0] : ''}</td>
                    </tr>`;
                });
            } else {
                tableContent = '<tr><td colspan="7">Nenhum detalhe encontrado para este produto.</td></tr>';
            }

            $('#productDetails').html(tableContent);
            $('#notafiscalModal').modal('show');
        },
        error: function() {
            $('#productDetails').html('<tr><td colspan="7">Erro ao carregar os detalhes do produto.</td></tr>');
        }
    });
});

</script>
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