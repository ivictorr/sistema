<?php
// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default';
$filterFile = __DIR__ . "/filters/user_{$userId}_resaldorec.txt";

// Função para salvar filtros
function saveFilters($filters, $filePath)
{
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    $data = json_encode($filters);
    file_put_contents($filePath, $data);
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

// Carregar filtros salvos
$savedFilters = loadFilters($filterFile);

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerarRelatorio'])) {
    $filters = [
        'formaCob' => $_POST['formaCob'] ?? [],
        'formaPgto' => $_POST['formaPgto'] ?? [],
		'banco' => $_POST['banco'] ?? [],
		'filial' => $_POST['filial'] ?? [],
        'emissao_de' => $_POST['emissao_de'] ?? '',
    ];

    // Salvar filtros quando necessário
    if (isset($_POST['salvarFiltro'])) {
        saveFilters($filters, $filterFile);
        $savedFilters = $filters;
    }

    // Limpar filtros
    if (isset($_POST['limparFiltro'])) {
        unlink($filterFile);
        $savedFilters = [];
    }


    // Aplicação dos filtros
    $dataEmissao = !empty($_POST['emissao_de']) ? $_POST['emissao_de'] : date('Y-m-d');
    $filtroFormaCob = !empty($_POST['formaCob']) ? "'" . implode("','", $_POST['formaCob']) . "'" : "'100','200'";
    $filtroFormaPgto = !empty($_POST['formaPgto']) ? "'" . implode("','", $_POST['formaPgto']) . "'" : "'100','200'";
	$filiais = isset($_POST['filial']) ? $_POST['filial'] : [];
	$filtroBanco = isset($_POST['banco']) && is_array($_POST['banco']) 
    ? "'" . implode("','", $_POST['banco']) . "'"
    : "'120'";

    // Consulta para Formas de Cobrança
    $queryFormaCobranca = "
        SELECT 
            MAX(B.Desc_forma_cob) AS Forma,
            SUM(A.Valor_saldo) AS Saldo,
            A.Cod_filial
        FROM tbTituloRec A 
        INNER JOIN tbFormaCob B ON A.Cod_forma_cob = B.Cod_forma_cob
        WHERE
            A.Data_emissao <= :dataEmissao AND
            A.Status_titulo = 'A' AND
            A.Cod_forma_cob IN ($filtroFormaCob)
        
    ";
	    if (!empty($filiais)) {
            $queryFormaCobranca .= " AND A.Cod_filial IN ('" . implode("','", $filiais) . "')";
        }
		
		$queryFormaCobranca .= "GROUP BY A.Cod_forma_cob, B.Desc_forma_cob, A.Cod_filial";

    $stmt1 = $pdoS->prepare($queryFormaCobranca);
    $stmt1->bindParam(':dataEmissao', $dataEmissao);
    $stmt1->execute();
    $resultFormaCobranca = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para Formas de Pagamento
    $queryFormaPagamento = "
        SELECT 
            MAX(B.Desc_forma_pgto) AS Forma,
            SUM(A.Valor_saldo) AS Saldo,
            A.Cod_filial
        FROM tbTituloPag A 
        INNER JOIN tbFormaPgto B ON A.Cod_forma_pgto = B.Cod_forma_pgto
        WHERE
            A.Data_emissao <= :dataEmissao AND
            A.Status_titulo = 'A' AND
            A.Cod_forma_pgto IN ($filtroFormaPgto)
        
    ";
	
		if (!empty($filiais)) {
            $queryFormaPagamento .= " AND A.Cod_filial IN ('" . implode("','", $filiais) . "')";
        }
		
		$queryFormaPagamento .= "GROUP BY A.Cod_forma_pgto, B.Desc_forma_pgto, A.Cod_filial";

    $stmt2 = $pdoS->prepare($queryFormaPagamento);
    $stmt2->bindParam(':dataEmissao', $dataEmissao);
    $stmt2->execute();
    $resultFormaPagamento = $stmt2->fetchAll(PDO::FETCH_ASSOC);
	
	
	   // Consulta para Banco
$queryBanco = "
    SELECT 
        MAX(B.Nome_agencia) AS Forma,
        MAX(A.Valor_saldo) AS Saldo,
        MAX(A.Data_saldo) AS DataSaldo,
		A.Cod_filial
    FROM tbSaldoBco A 
    INNER JOIN tbBancoCaixa B ON A.Cod_banco_caixa = B.Cod_banco_caixa
    WHERE
        A.Data_saldo <= :dataEmissao
        AND A.Cod_banco_caixa IN ($filtroBanco)
";

if (!empty($filiais)) {
    $queryBanco .= " AND A.Cod_filial IN ('" . implode("','", $filiais) . "')";
}

$queryBanco .= " GROUP BY A.Cod_banco_caixa,  A.Cod_filial"; // Removemos HAVING

$stmt3 = $pdoS->prepare($queryBanco);
$stmt3->bindParam(':dataEmissao', $dataEmissao);
$stmt3->execute();
$resultBanco = $stmt3->fetchAll(PDO::FETCH_ASSOC);
}
?>
    <div class="panel panel-primary no-print mt-5" style="margin-top: 30px">
        <div class="panel-heading text-center">
            <h3>RESUMO DE GERAL</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
					            <div class="form-group row">
                <label for="filial-select" class="col-sm-2 col-form-label text-right">Escolha a Filial:</label>
                <div class="col-sm-5">
                    <select id="filial-select" name="filial[]" class="selectpicker form-control" data-live-search="true" multiple title="Selecione uma ou mais opções">';
                        <?php
                        $res = $pdoS->query("SELECT * FROM tbFilial A WHERE A.Cod_filial IN ('100','200')");
                        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                            $selected = in_array($r['Cod_filial'], $savedFilters['filial'] ?? []) ? 'selected' : '';
                            echo "<option value='{$r['Cod_filial']}' {$selected}>{$r['Cod_filial']} - {$r['Nome_filial']}</option>";
                        }
                        ?>
                    </select>
                </div>
				            <div class="col-sm-5">
                <input type="date" name="emissao_de" class="form-control" value="<?= $savedFilters['emissao_de'] ?? '' ?>">
            </div>
            </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label text-right">Forma de Cobrança:</label>
            <div class="col-sm-10">
                <select name="formaCob[]" class="selectpicker form-control" multiple data-live-search="true" multiple title="Selecione uma ou mais opções">
                    <?php
                    $res = $pdoS->query("SELECT DISTINCT A.Cod_forma_cob AS DOC, B.Desc_forma_cob AS NOME FROM tbTituloRec A
                    INNER JOIN tbFormaCob B ON A.Cod_forma_cob = B.Cod_forma_cob ORDER BY A.Cod_forma_cob ASC");
                    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                        $selected = in_array($r['DOC'], $savedFilters['formaCob'] ?? []) ? 'selected' : '';
                        echo "<option value='{$r['DOC']}' {$selected}>{$r['DOC']} - {$r['NOME']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label text-right">Forma de Pagamento:</label>
            <div class="col-sm-10">
                <select name="formaPgto[]" class="selectpicker form-control" multiple data-live-search="true" multiple title="Selecione uma ou mais opções">
                    <?php
                    $res = $pdoS->query("SELECT DISTINCT A.Cod_forma_pgto AS DOC, B.Desc_forma_pgto AS NOME FROM tbTituloPag A
                    INNER JOIN tbFormaPgto B ON A.Cod_forma_pgto = B.Cod_forma_pgto ORDER BY A.Cod_forma_pgto ASC");
                    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                        $selected = in_array($r['DOC'], $savedFilters['formaPgto'] ?? []) ? 'selected' : '';
                        echo "<option value='{$r['DOC']}' {$selected}>{$r['DOC']} - {$r['NOME']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label text-right">Bancos:</label>
            <div class="col-sm-10">
                <select name="banco[]" class="selectpicker form-control" multiple data-live-search="true" multiple title="Selecione uma ou mais opções">
                    <?php
                    $res = $pdoS->query("SELECT DISTINCT A.Cod_banco_caixa AS Cod_Banco, A.Nome_agencia AS NOME FROM tbBancoCaixa A
                   ORDER BY A.Cod_banco_caixa ASC");
                    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
                        $selected = in_array($r['Cod_Banco'], $savedFilters['banco'] ?? []) ? 'selected' : '';
                        echo "<option value='{$r['Cod_Banco']}' {$selected}>{$r['Cod_Banco']} - {$r['NOME']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group text-center">
            <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
        </div>
		 </div>
    </form>

 <?php if (isset($_POST['gerarRelatorio'])): ?>
    <h4 class="mt-4 text-center">Contas a Receber</h4>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th style="width: 75%">Forma</th>
                <th>Filial</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalXinguaraReceber = 0;
            $totalJataiReceber = 0;

            foreach ($resultFormaCobranca as $row) {
                $filialNome = ($row['Cod_filial'] == '100') ? 'Xinguara' : 'Jataí';
                $totalXinguaraReceber += ($row['Cod_filial'] == '100') ? $row['Saldo'] : 0;
                $totalJataiReceber += ($row['Cod_filial'] == '200') ? $row['Saldo'] : 0;

                echo "<tr>
                        <td>{$row['Forma']}</td>
                        <td>{$filialNome}</td>
                        <td>R$ " . number_format($row['Saldo'], 2, ',', '.') . "</td>
                      </tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr class="table-primary">
                <td><strong>Total Contas a Receber</strong></td>
                <td><strong>Xinguara</strong></td>
                <td class="text-success"><strong>R$ <?= number_format($totalXinguaraReceber, 2, ',', '.') ?></strong></td>
            </tr>
            <tr class="table-primary">
                <td><strong>Total Contas a Receber</strong></td>
                <td><strong>Jataí</strong></td>
                <td class="text-success"><strong>R$ <?= number_format($totalJataiReceber, 2, ',', '.') ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <h4 class="mt-4 text-center">Contas a Pagar</h4>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th style="width: 75%">Forma</th>
                <th>Filial</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalXinguaraPagar = 0;
            $totalJataiPagar = 0;

            foreach ($resultFormaPagamento as $row) {
                $filialNome = ($row['Cod_filial'] == '100') ? 'Xinguara' : 'Jataí';
                $totalXinguaraPagar += ($row['Cod_filial'] == '100') ? $row['Saldo'] : 0;
                $totalJataiPagar += ($row['Cod_filial'] == '200') ? $row['Saldo'] : 0;

                echo "<tr>
                        <td>{$row['Forma']}</td>
                        <td>{$filialNome}</td>
                        <td>R$ " . number_format($row['Saldo'], 2, ',', '.') . "</td>
                      </tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr class="table-info">
                <td><strong>Total Contas a Pagar</strong></td>
                <td><strong>Xinguara</strong></td>
                <td class="text-danger"><strong>- R$ <?= number_format($totalXinguaraPagar, 2, ',', '.') ?></strong></td>
            </tr>
            <tr class="table-info">
                <td><strong>Total Contas a Pagar</strong></td>
                <td><strong>Jataí</strong></td>
                <td class="text-danger"><strong>- R$ <?= number_format($totalJataiPagar, 2, ',', '.') ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <h4 class="mt-4 text-center">Bancos</h4>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th style="width: 75%">Banco</th>
                <th>Filial</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalXinguaraBancos = 0;
            $totalJataiBancos = 0;

            foreach ($resultBanco as $row) {
                $filialNome = ($row['Cod_filial'] == '100') ? 'Xinguara' : 'Jataí';
                $totalXinguaraBancos += ($row['Cod_filial'] == '100') ? $row['Saldo'] : 0;
                $totalJataiBancos += ($row['Cod_filial'] == '200') ? $row['Saldo'] : 0;

                echo "<tr>
                        <td>{$row['Forma']}</td>
                        <td>{$filialNome}</td>
                        <td>R$ " . number_format($row['Saldo'], 2, ',', '.') . "</td>
                      </tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr class="table-success">
                <td><strong>Total Bancos</strong></td>
                <td><strong>Xinguara</strong></td>
                <td class="text-success"><strong>R$ <?= number_format($totalXinguaraBancos, 2, ',', '.') ?></strong></td>
            </tr>
            <tr class="table-success">
                <td><strong>Total Bancos</strong></td>
                <td><strong>Jataí</strong></td>
                <td class="text-success"><strong>R$ <?= number_format($totalJataiBancos, 2, ',', '.') ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <h4 class="mt-4 text-center">Total Geral</h4>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th colspan="2" style="width: 75%">Categoria</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr class="table-primary">
                <td colspan="2"><strong>Saldo Total Contas a Receber</strong></td>
                <td class="text-success"><strong>R$ <?= number_format($totalXinguaraReceber + $totalJataiReceber, 2, ',', '.') ?></strong></td>
            </tr>
            <tr class="table-info">
                <td colspan="2"><strong>Saldo Total Contas a Pagar</strong></td>
                <td class="text-danger"><strong>- R$ <?= number_format($totalXinguaraPagar + $totalJataiPagar, 2, ',', '.') ?></strong></td>
            </tr>
            <tr class="table-success">
                <td colspan="2"><strong>Saldo Total Bancos</strong></td>
                <td class="text-success"><strong>R$ <?= number_format($totalXinguaraBancos + $totalJataiBancos, 2, ',', '.') ?></strong></td>
            </tr>
            <tr class="table-dark">
                <td colspan="2"><strong>Total Final</strong></td>
                <td><strong>R$ <?= number_format(($totalXinguaraReceber + $totalJataiReceber) - ($totalXinguaraPagar + $totalJataiPagar) + ($totalXinguaraBancos + $totalJataiBancos), 2, ',', '.') ?></strong></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>