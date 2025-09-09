<?php
function saveFilters($filters, $filePath)
{
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    $data = json_encode($filters);
    file_put_contents($filePath, $data);
}

function clearFilters($filePath)
{
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

function loadFilters($filePath)
{
    if (file_exists($filePath)) {
        $data = file_get_contents($filePath);
        return json_decode($data, true);
    }
    return [];
}

$userId = $_SESSION['user_id'] ?? 'default';
$filterFile = __DIR__ . "/filters/user_{$userId}_comercial1.txt";
$savedFilters = loadFilters($filterFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'localestoque' => $_POST['localestoque'] ?? [],
        'emissao_de' => $_POST['emissao_de'] ?? '',
        'emissao_ate' => $_POST['emissao_ate'] ?? '',
        'tipo_venda' => $_POST['tipo_venda'] ?? 'TODOS'
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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório por Grupo</title>
	
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <style>
        .filter-summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 10px;
            font-size: 14px;
        }
        .panel {
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .table th, .table td {
            font-size: 13px;
            vertical-align: middle;
        }
@media print {
    .panel-info {
        page-break-before: always;
        page-break-inside: avoid;
        display: block;
    }

    .panel-info:last-of-type {
        page-break-after: auto;
    }
}
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="panel panel-primary no-print" style="margin-top: 30px">
        <div class="panel-heading text-center">
            <h3>RELATÓRIO MÉDIA POR GRUPO RENDIMENTO</h3>
        </div>
        <form method="POST" class="form-horizontal" style="margin: 20px 10px;">

            <div class="form-group row">
                <label for="produto-select" class="col-sm-2 col-form-label text-right">Local de Estoque:</label>
                <div class="col-sm-10">
<select id="localEstoque" name="localEstoque[]" class="form-control selectpicker" multiple data-live-search="true" title="Selecione um ou mais locais">
    <?php
    $selectedLocais = $savedFilters['localestoque'] ?? [];
    $stmtLocais = $pdoS->query("SELECT DISTINCT Desc_local, Cod_local 
        FROM tbLocalEstoque 
        WHERE Cod_filial = {$filial} 
        AND Cod_local IN ($codigosIn)
        ORDER BY Desc_local");

    while ($local = $stmtLocais->fetch(PDO::FETCH_ASSOC)) {
        $selected = in_array($local['Cod_local'], $selectedLocais) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($local['Cod_local']) . "\" $selected>" . htmlspecialchars($local['Desc_local']) . '</option>';
    }
    ?>
</select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Tipo de Venda:</label>
                <div class="col-sm-10">
                    <select name="tipo_venda" class="selectpicker form-control" title="Selecione uma ou mais opções">
                        <option value="TODOS" <?= ($savedFilters['tipo_venda'] ?? '') == 'TODOS' ? 'selected' : '' ?>>Todos</option>
                        <option value="MI" <?= ($savedFilters['tipo_venda'] ?? '') == 'MI' ? 'selected' : '' ?>>Venda Mercado Interno</option>
                        <option value="ME" <?= ($savedFilters['tipo_venda'] ?? '') == 'ME' ? 'selected' : '' ?>>Venda Mercado Externo</option>
						 <option value="TR" <?= ($savedFilters['tipo_venda'] ?? '') == 'TR' ? 'selected' : '' ?>>Transferencia entre Filiais</option>
                    </select>
                </div>
            </div>

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

            <div class="form-group text-center">
                <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
                <button type="submit" name="salvarFiltro" class="btn btn-success">Salvar Filtro</button>
                <button type="submit" name="limparFiltro" class="btn btn-danger">Limpar Filtro</button>
            </div>
        </form>
    </div>

    <?php if (isset($_POST['gerarRelatorio'])):
        $localestoque = $_POST['localEstoque'] ?? [];
		$emissao_de = $_POST['emissao_de'] ?? '';
		$emissao_ate = $_POST['emissao_ate'] ?? '';
        $tipo_venda = $_POST['tipo_venda'] ?? 'TODOS';
		$_SESSION['tipoVenda'] = $tipo_venda;
		
		$_SESSION['localestoque'] = $_POST['localEstoque'];
		$jsonSeason = json_encode($_SESSION['localestoque']);

        $sql = "SELECT B.Qtde_pri AS PESO, B.Valor_unitario, C.Cod_grupo_rend,
                    CASE 
						WHEN C.Cod_produto BETWEEN '20000' AND '29999' THEN 'MATERIA PRIMA'
                        WHEN C.Cod_grupo_rend LIKE 'D%' AND C.Cod_produto NOT IN ('30168') THEN 'DIANTEIRO'
                        WHEN C.Cod_grupo_rend LIKE 'T%' AND C.Cod_produto NOT IN ('30143', '30170', '30148', '30326', '30167') THEN 'TRASEIRO'
                        WHEN C.Cod_grupo_rend LIKE 'C%' THEN 'COSTELA'
                        WHEN C.Cod_grupo_rend LIKE 'M%' AND C.Cod_produto NOT IN ('35064', '35069') THEN 'MIÚDOS'
                        ELSE 'OUTROS'
                    END AS Grupo,
                    C.Desc_produto_est AS NOME,
                    C.Cod_produto
                FROM TBSAIDAS A
                INNER JOIN TBSAIDASITEM B ON A.Chave_fato = B.Chave_fato AND B.Num_subItem = 0
                INNER JOIN TBTIPOMVESTOQUE D ON A.COD_TIPO_MV = D.COD_TIPO_MV AND A.COD_DOCTO = D.COD_DOCTO
                INNER JOIN TBPRODUTO C ON B.Cod_produto = C.Cod_produto
                WHERE A.Status <> 'C'
                         AND (
                (C.Cod_produto BETWEEN '30000' AND '39999')
                OR (C.Cod_produto BETWEEN '20000' AND '29999')
            )
                AND A.Cod_tipo_mv NOT IN ('T525')";

        if ($tipo_venda === 'MI') {
            $sql .= " AND (D.Perfil_tmv IN ('VDA0301') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
        } elseif ($tipo_venda === 'ME') {
            $sql .= " AND D.Perfil_tmv IN ('VDA0302')";
        } elseif ($tipo_venda === 'TR') {
            $sql .= " AND A.Cod_tipo_mv IN ('T720')";
        } else {
            $sql .= " AND (D.Perfil_tmv IN ('VDA0301','VDA0302') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
        }

        $params = [];
        if (!empty($emissao_de)) { $sql .= " AND A.Data_movto >= ?"; $params[] = $emissao_de; }
        if (!empty($emissao_ate)) { $sql .= " AND A.Data_movto <= ?"; $params[] = $emissao_ate; }
		if (!empty($filial)) { $sql .= " AND A.Cod_filial = ?"; $params[] = $filial; }
    if (!empty($localestoque)) {
        $placeholders = implode(',', array_fill(0, count($localestoque), '?'));
        $sql .= " AND B.Cod_local IN ($placeholders)";
        $params = array_merge($params, $localestoque);
    }



        $stmt = $pdoS->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grupos = [];
        foreach ($resultados as $row) {
            $grupo = $row['Grupo'];
            $codigo = $row['Cod_produto'];

            if (!isset($grupos[$grupo])) {
                $grupos[$grupo] = ['produtos' => [], 'Peso_Total_Grupo' => 0, 'Valor_Total_Grupo' => 0];
            }

            if (!isset($grupos[$grupo]['produtos'][$codigo])) {
                $grupos[$grupo]['produtos'][$codigo] = [
                    'Cod_produto' => $codigo,
                    'NOME' => $row['NOME'],
                    'Peso_Total' => 0,
                    'Valor_Total' => 0
                ];
            }

            $grupos[$grupo]['produtos'][$codigo]['Peso_Total'] += $row['PESO'];
            $grupos[$grupo]['produtos'][$codigo]['Valor_Total'] += $row['PESO'] * $row['Valor_unitario'];
            $grupos[$grupo]['Peso_Total_Grupo'] += $row['PESO'];
            $grupos[$grupo]['Valor_Total_Grupo'] += $row['PESO'] * $row['Valor_unitario'];
        }

        // Ordena os grupos conforme ordem desejada
        $ordemGrupos = ['MATERIA PRIMA', 'TRASEIRO', 'DIANTEIRO', 'MIÚDOS', 'COSTELA'];
        $gruposOrdenados = [];
        foreach ($ordemGrupos as $g) {
            if (isset($grupos[$g])) {
                $gruposOrdenados[$g] = $grupos[$g];
                unset($grupos[$g]);
            }
        }
        foreach ($grupos as $g => $dados) {
            $gruposOrdenados[$g] = $dados;
        }
        $grupos = $gruposOrdenados;
    ?>
    <div class="filter-summary">
        <strong>Filtros Aplicados:</strong><br>
        <strong>Filial:</strong> <?= $filial ?? 'Todas' ?><br>
       <strong>Locais:</strong> <?= implode(', ', $_POST['localEstoque'] ?? []) ?: 'Todos' ?><br>
		<strong>Tipo Venda:</strong> <?= $_POST['tipo_venda'] ?? 'Todas' ?><br>
        <strong>Data:</strong> <?= $_POST['emissao_de'] ?? 'Início' ?> até <?= $_POST['emissao_ate'] ?? 'Fim' ?>

    </div>
<div class="text-center no-print" style="margin: 20px 0;">
    <button onclick="window.print()" class="btn btn-default btn-lg">
        <span class="glyphicon glyphicon-print"></span> Imprimir Relatório
    </button>
</div>
   <div class="row">
    <?php
    $i = 0;
    foreach ($grupos as $grupo => $dadosGrupo):
        $i++;
        // Força quebra de página antes de cada grupo (exceto o primeiro)
        if ($i > 1) echo '<div style="page-break-before: always;"></div>';
    ?>
    <div class="col-md-12">
        <div class="panel panel-info">
            <div class="panel-heading text-center"><h4 style="margin:0;"><?= strtoupper("Grupo $grupo") ?></h4></div>
            <div class="panel-body">
                <table class="table table-bordered table-striped small">
                    <thead>
                        <tr class="info text-center">
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Peso Total (Kg)</th>
                            <th>Valor Total (R$)</th>
                            <th>Média R$/Kg</th>
                            <th class="no-print"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dadosGrupo['produtos'] as $produto): 
                            $media = $produto['Peso_Total'] > 0 ? $produto['Valor_Total'] / $produto['Peso_Total'] : 0;
                        ?>
                        <tr>
                            <td><?= $produto['Cod_produto'] ?></td>
                            <td><?= $produto['NOME'] ?></td>
                            <td class="text-right"><?= number_format($produto['Peso_Total'], 3, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($produto['Valor_Total'], 2, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($media, 2, ',', '.') ?></td>
                            <td class="no-print">				
                                <div class='btn-group' role='group' style='display: flex; gap: 5px;'>
                                    <button class='btn btn-sm btn-info btn-sm open-modal' 
                                        data-cod-produto="<?=$produto['Cod_produto']?>"
                                        data-cod-filial="<?=$filial?>"
                                        data-emissao-de="<?=htmlspecialchars($emissao_de, ENT_QUOTES, 'UTF-8')?>" 
                                        data-emissao-ate="<?=htmlspecialchars($emissao_ate, ENT_QUOTES, 'UTF-8')?>"  
                                        data-localestoque="<?= htmlspecialchars($jsonSeason, ENT_QUOTES, 'UTF-8')?>" 
                                        data-tipovenda="<?= htmlspecialchars($_SESSION['tipoVenda'], ENT_QUOTES, 'UTF-8')?>" 
                                        data-toggle="modal" 
                                        data-target="#notafiscalModal">
                                        <i class="glyphicon glyphicon-search"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="info">
                            <td colspan="2" class="text-right">TOTAL DO GRUPO</td>
                            <td class="text-right"><?= number_format($dadosGrupo['Peso_Total_Grupo'], 3, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($dadosGrupo['Valor_Total_Grupo'], 2, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($dadosGrupo['Valor_Total_Grupo'] / $dadosGrupo['Peso_Total_Grupo'], 2, ',', '.') ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
    <?php endif; ?>
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