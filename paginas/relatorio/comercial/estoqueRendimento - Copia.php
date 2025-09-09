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
        'filial' => $_POST['filial'] ?? '',
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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório por Grupo</title>
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
            .no-print { display: none; }
            .filter-summary { display: block; }
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
            <div class="form-group">
                <label class="col-sm-2 control-label">Filial:</label>
                <div class="col-sm-10">
                    <select id="filial-select" name="filial" class="selectpicker form-control" title="Selecione uma ou mais opções">
                        <?php
                        $res = $pdoS->query("SELECT * FROM tbFilial A WHERE A.Cod_filial IN ('100','200')");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($savedFilters['filial'] ?? '') == $r['Cod_filial'] ? 'selected' : '';
                            echo "<option value='{$r['Cod_filial']}' $selected>{$r['Cod_filial']} - {$r['Nome_filial']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Local Estoque:</label>
                <div class="col-sm-10">
                    <select id="localestoque-select" name="localestoque[]" class="selectpicker form-control" title="Selecione uma ou mais opções">
                        <?php
                        $res = $pdoS->query("SELECT DISTINCT Cod_local, Desc_local FROM tbLocalEstoque WHERE COD_FILIAL IN ('200','100') AND Cod_local IN ('01','02','03','04','05','12','13','14') AND Estoque_disponivel = 'S' ORDER BY Cod_local ASC");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_local'], $savedFilters['localestoque'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_local']}' $selected>{$r['Cod_local']} - {$r['Desc_local']}</option>";
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
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Data de Emissão:</label>
                <div class="col-sm-5">
                    <input type="date" name="emissao_de" class="form-control" value="<?= $savedFilters['emissao_de'] ?? '' ?>">
                </div>
                <div class="col-sm-5">
                    <input type="date" name="emissao_ate" class="form-control" value="<?= $savedFilters['emissao_ate'] ?? '' ?>">
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
        $filial = $_POST['filial'];
        $localestoque = $_POST['localestoque'] ?? [];
        $emissao_de = $_POST['emissao_de'] ?? '';
        $emissao_ate = $_POST['emissao_ate'] ?? '';
        $tipo_venda = $_POST['tipo_venda'] ?? 'TODOS';

        $sql = "SELECT B.Qtde_pri AS PESO, B.Valor_unitario, C.Cod_grupo_rend,
                    CASE 
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
                AND C.Cod_produto BETWEEN '30000' AND '39999'
                AND A.Cod_tipo_mv NOT IN ('T525')";

        if ($tipo_venda === 'MI') {
            $sql .= " AND (D.Perfil_tmv IN ('VDA0301') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
        } elseif ($tipo_venda === 'ME') {
            $sql .= " AND D.Perfil_tmv IN ('VDA0302')";
        } else {
            $sql .= " AND (D.Perfil_tmv IN ('VDA0301','VDA0302') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
        }

        $params = [];
        if (!empty($emissao_de)) { $sql .= " AND A.Data_emissao >= ?"; $params[] = $emissao_de; }
        if (!empty($emissao_ate)) { $sql .= " AND A.Data_emissao <= ?"; $params[] = $emissao_ate; }
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
        $ordemGrupos = ['TRASEIRO', 'DIANTEIRO', 'MIÚDOS', 'COSTELA'];
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
        <strong>Filial:</strong> <?= $_POST['filial'] ?? 'Todas' ?><br>
        <strong>Locais:</strong> <?= isset($_POST['localestoque']) ? implode(', ', $_POST['localestoque']) : 'Todos' ?><br>
		<strong>Tipo Venda:</strong> <?= $_POST['tipo_venda'] ?? 'Todas' ?><br>
        <strong>Data:</strong> <?= $_POST['emissao_de'] ?? 'Início' ?> até <?= $_POST['emissao_ate'] ?? 'Fim' ?>

    </div>
<div class="text-center no-print" style="margin: 20px 0;">
    <button onclick="window.print()" class="btn btn-default btn-lg">
        <span class="glyphicon glyphicon-print"></span> Imprimir Relatório
    </button>
</div>
    <div class="row">
        <?php foreach ($grupos as $grupo => $dadosGrupo): ?>
        <div class="col-md-6">
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
								<th></th>
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
								<td>				<div class='btn-group' role='group' style='display: flex; gap: 5px;'>
                    <button class='btn btn-sm btn-primary btn-detalhe open-modal' data-cod-produto='{$row['PRODUTO']}' data-toggle='modal' data-target='#productModal'>
                       <i class='glyphicon glyphicon-search'></i>
                    </button>
					</div></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="info">
                                <td colspan="2" class="text-right">TOTAL DO GRUPO</td>
                                <td class="text-right"><?= number_format($dadosGrupo['Peso_Total_Grupo'], 3, ',', '.') ?></td>
                                <td class="text-right"><?= number_format($dadosGrupo['Valor_Total_Grupo'], 2, ',', '.') ?></td>
                                <td class="text-right"><?= number_format($dadosGrupo['Valor_Total_Grupo'] / $dadosGrupo['Peso_Total_Grupo'], 2, ',', '.') ?></td>
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
                            <th>Produto</th>
                            <th>Reservado</th>
                            <th>TMV</th>
                            <th>N°Pedido</th>
                            <th>Preço</th>
                            <th>Cliente</th>
                            <th>Data Embarque</th>
                            <th>Usuário</th>
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

