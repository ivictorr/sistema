<?php
// -------------------------------------------------------------
// RELATÓRIO: SIMULAÇÃO DA DESOSSA (com Traseiro, Dianteiro, PA)
// MSSQL (ODBC Driver 17) ➜ Snapshot MySQL
// -------------------------------------------------------------

// Garante que as conexões com o banco de dados estão disponíveis
if (!isset($pdoM) || !isset($pdoS)) {
    die('Conexões não disponíveis. Inclua primeiro o script de conexão ($pdoM e $pdoS).');
}

// --- LEITURA DE FILTROS E PARÂMETROS ---
$filial        = $GLOBALS['FILIAL_USUARIO'] ?? '100';
$today         = date('Y-m-d');
$snapshotId    = $_GET['snapshot'] ?? $_POST['snapshot_id'] ?? $_SESSION['last_snapshot'] ?? '';
$dataIni       = $_POST['dataIni']    ?? $_GET['di']    ?? $today;
$dataFim       = $_POST['dataFim']    ?? $_GET['df']    ?? $today;
$cabecasHoje   = (int)($_POST['cabecasHoje']   ?? $_GET['cab']   ?? 0);
$traseiroHoje  = (int)($_POST['traseiroHoje']  ?? $_GET['tr']    ?? 0);
$dianteiroHoje = (int)($_POST['dianteiroHoje'] ?? $_GET['diant'] ?? 0);
$paHoje        = (int)($_POST['paHoje']        ?? $_GET['pa']    ?? 0);

$msgSucesso = $msgErro = '';

// --- LÓGICA PARA SALVAR EDIÇÕES NO MYSQL ---
if (isset($_POST['salvar_edicoes']) && !empty($_POST['proj'])) {
    try {
        $pdoM->beginTransaction();
        $upd = $pdoM->prepare("UPDATE simulacao_desossa SET proj_qtde = :proj WHERE id = :id");
        
        foreach ($_POST['proj'] as $id => $val) {
            $id  = (int)$id;
            // Limpa e formata o valor para o padrão float do banco
            $val = str_replace(['.', ' '], '', (string)$val);
            $val = str_replace(',', '.', $val);
            if ($id > 0) {
                $upd->execute([':proj' => (float)$val, ':id' => $id]);
            }
        }
        
        $pdoM->commit();
        $msgSucesso = 'Alterações salvas com sucesso!';
    } catch (Throwable $e) {
        if ($pdoM->inTransaction()) $pdoM->rollBack();
        $msgErro = 'Erro ao salvar alterações: ' . $e->getMessage();
    }
}

// --- LÓGICA PARA GERAR O SNAPSHOT ---
if (isset($_POST['simular'])) {
    $snapshotId = bin2hex(random_bytes(16));
    try {
        // SQL Otimizada e Corrigida com a busca de estoque
        $sql = "
            WITH EstoqueAtual AS (
                -- NOVO: CTE para buscar o estoque atual de cada produto
                SELECT
                    A.Cod_produto,
                    SUM(A.Peso_liquido) AS estoque_atual
                FROM tbVolume A
                WHERE A.Status = 'E'
                  AND A.Cod_filial_estoque = ? -- Novo parâmetro para filial de estoque
                GROUP BY A.Cod_produto
            ),
            ProducaoDesossa AS (
                SELECT
                    v.COD_PRODUTO,
                    LTRIM(RTRIM(v.DESC_PRODUTO_EST)) AS DESC_PRODUTO_EST,
                    v.COD_GRUPO_REND,
                    v.NOME_GRUPO_REND,
                    UPPER(v.COD_DOCTO) AS COD_DOCTO,
                    CASE 
                        WHEN v.COD_LINHA = 'D01' THEN 'TRASEIRO'
                        WHEN v.COD_LINHA = 'D02' THEN 'DIANTEIRO'
                        WHEN v.COD_LINHA = 'B01' THEN 'MIUDOS'
                        ELSE 'OUTROS'
                    END AS TIPO_CORTE,
                    SUM(v.QTDE_PRI) AS TOTAL_QTDE_PRI
                FROM vwAtak4Net_RendimentoDaDesossa v
                WHERE v.COD_FILIAL = ?
                  AND UPPER(v.COD_DOCTO) IN ('RPE','RPM')
                  AND v.QTDE_PRI > 0
                  AND v.DATA_ESTOQUE BETWEEN ? AND ?
                  AND v.COD_GRUPO_REND NOT LIKE 'S%'
                  AND v.COD_GRUPO_REND NOT LIKE '00%'
                  AND v.DESC_PRODUTO_EST NOT LIKE '%REFEITORIO%'
                  AND v.COD_LINHA NOT IN ('R00')
                GROUP BY
                    v.COD_PRODUTO, LTRIM(RTRIM(v.DESC_PRODUTO_EST)),
                    v.COD_GRUPO_REND, v.NOME_GRUPO_REND, UPPER(v.COD_DOCTO),
                    CASE 
                        WHEN v.COD_LINHA = 'D01' THEN 'TRASEIRO'
                        WHEN v.COD_LINHA = 'D02' THEN 'DIANTEIRO'
                        WHEN v.COD_LINHA = 'B01' THEN 'MIUDOS'
                        ELSE 'OUTROS'
                    END
            ),
            BaseRPS AS (
                SELECT 
                    CASE 
                        WHEN v.COD_LINHA = 'D01' THEN 'TRASEIRO'
                        WHEN v.COD_LINHA = 'D02' THEN 'DIANTEIRO'
                        WHEN v.COD_LINHA = 'B01' THEN 'MIUDOS'
                        ELSE 'OUTROS'
                    END AS TIPO_CORTE,
                    SUM(v.QTDE_AUX) AS TOTAL_RPS
                FROM vwAtak4Net_RendimentoDaDesossa v
                WHERE v.COD_FILIAL = ?
                  AND UPPER(v.COD_DOCTO) = 'RPS'
                  AND v.DATA_ESTOQUE BETWEEN ? AND ?
                  AND v.COD_GRUPO_REND NOT IN ('TC','D1','TQ')
                  AND v.QTDE_AUX > 0
                  AND v.COD_LINHA NOT IN ('R00')
                GROUP BY
                    CASE 
                        WHEN v.COD_LINHA = 'D01' THEN 'TRASEIRO'
                        WHEN v.COD_LINHA = 'D02' THEN 'DIANTEIRO'
                        WHEN v.COD_LINHA = 'B01' THEN 'MIUDOS'
                        ELSE 'OUTROS'
                    END
            ),
            TotalAbate AS (
                SELECT SUM(a.QTDE) AS TOTAL_ABATE
                FROM vwAtak4Net_RomaneioAbateSisbov a
                WHERE a.DATA_ABATE BETWEEN ? AND ?
            )
            SELECT
                p.TIPO_CORTE,
                p.COD_PRODUTO,
                p.DESC_PRODUTO_EST,
                p.COD_GRUPO_REND,
                p.NOME_GRUPO_REND,
                p.COD_DOCTO,
                p.TOTAL_QTDE_PRI,
                ISNULL(e.estoque_atual, 0) AS estoque_congelado,
                CASE 
                    WHEN p.COD_DOCTO = 'RPM' THEN t.TOTAL_ABATE
                    WHEN p.COD_DOCTO = 'RPE' THEN r.TOTAL_RPS
                END AS TOTAL_BASE,
                CASE 
                    WHEN p.COD_DOCTO = 'RPM' 
                        THEN CAST(p.TOTAL_QTDE_PRI / NULLIF(t.TOTAL_ABATE,0) AS DECIMAL(18,6))
                    WHEN p.COD_DOCTO = 'RPE'
                        THEN CAST(p.TOTAL_QTDE_PRI / NULLIF(r.TOTAL_RPS,0) AS DECIMAL(18,6))
                END AS KG_POR_CAB,
                CASE 
                    WHEN p.COD_DOCTO = 'RPM'
                        THEN CAST((p.TOTAL_QTDE_PRI / NULLIF(t.TOTAL_ABATE,0)) * ? AS DECIMAL(18,6))
                    WHEN p.COD_DOCTO = 'RPE' AND p.TIPO_CORTE = 'TRASEIRO'
                        THEN CAST((p.TOTAL_QTDE_PRI / NULLIF(r.TOTAL_RPS,0)) * ? AS DECIMAL(18,6))
                    WHEN p.COD_DOCTO = 'RPE' AND p.TIPO_CORTE = 'DIANTEIRO'
                        THEN CAST((p.TOTAL_QTDE_PRI / NULLIF(r.TOTAL_RPS,0)) * ? AS DECIMAL(18,6))
                    WHEN p.COD_DOCTO = 'RPE' AND p.TIPO_CORTE = 'MIUDOS'
                        THEN CAST((p.TOTAL_QTDE_PRI / NULLIF(r.TOTAL_RPS,0)) * ? AS DECIMAL(18,6))
                END AS PROJECAO_QTDE
            FROM ProducaoDesossa p
            LEFT JOIN BaseRPS r ON r.TIPO_CORTE = p.TIPO_CORTE
            LEFT JOIN EstoqueAtual e ON p.COD_PRODUTO = e.Cod_produto
            CROSS JOIN TotalAbate t
            ORDER BY p.COD_DOCTO, p.TIPO_CORTE, p.NOME_GRUPO_REND, p.DESC_PRODUTO_EST;
        ";

        $stmt = $pdoS->prepare($sql);
        $stmt->execute([
            $filial, // Para EstoqueAtual
            $filial, $dataIni, $dataFim, // Para ProducaoDesossa
            $filial, $dataIni, $dataFim, // Para BaseRPS
            $dataIni, $dataFim, // Para TotalAbate
            $cabecasHoje, $traseiroHoje, $dianteiroHoje, $paHoje // Para Projeção
        ]);
        $mssqlRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdoM->exec("TRUNCATE TABLE simulacao_desossa");

        if ($mssqlRows) {
            $pdoM->beginTransaction();
            $ins = $pdoM->prepare("INSERT INTO simulacao_desossa
                    (snapshot_id, filial, data_ini, data_fim, cabecas_hoje, traseiro_hoje, dianteiro_hoje, pa_hoje, data_geracao,
                     cod_produto, desc_produto_est, cod_grupo_rend, nome_grupo_rend,
                     total_qtde_per, total_abate_per, kg_por_cab, proj_qtde, estoque_congelado, tipo_corte)
                VALUES
                    (:snapshot_id, :filial, :data_ini, :data_fim, :cabecas_hoje, :traseiro_hoje, :dianteiro_hoje, :pa_hoje, NOW(),
                     :cod_produto, :desc_produto_est, :cod_grupo_rend, :nome_grupo_rend,
                     :total_qtde_per, :total_abate_per, :kg_por_cab, :proj_qtde, :estoque_congelado, :tipo_corte)");

            foreach ($mssqlRows as $r) {
                $ins->execute([
                    ':snapshot_id'      => $snapshotId,
                    ':filial'           => $filial,
                    ':data_ini'         => $dataIni,
                    ':data_fim'         => $dataFim,
                    ':cabecas_hoje'     => $cabecasHoje,
                    ':traseiro_hoje'    => $traseiroHoje,
                    ':dianteiro_hoje'   => $dianteiroHoje,
                    ':pa_hoje'          => $paHoje,
                    ':cod_produto'      => (string)$r['COD_PRODUTO'],
                    ':desc_produto_est' => (string)$r['DESC_PRODUTO_EST'],
                    ':cod_grupo_rend'   => (string)$r['COD_GRUPO_REND'],
                    ':nome_grupo_rend'  => (string)$r['NOME_GRUPO_REND'],
                    ':total_qtde_per'   => (float)$r['TOTAL_QTDE_PRI'],
                    ':total_abate_per'  => (float)$r['TOTAL_BASE'],
                    ':kg_por_cab'       => (float)$r['KG_POR_CAB'],
                    ':proj_qtde'        => (float)$r['PROJECAO_QTDE'],
                    ':estoque_congelado'=> (float)$r['estoque_congelado'],
                    ':tipo_corte'       => (string)$r['TIPO_CORTE'],
                ]);
            }
            $pdoM->commit();

            $_SESSION['last_snapshot'] = $snapshotId;
            $redirectUrl = "?relatorioDisponibilidade";
            if (!headers_sent()) {
                header("Location: {$redirectUrl}");
                exit;
            } else {
                echo "<script>location.href=" . json_encode($redirectUrl) . ";</script>";
                echo "<noscript><meta http-equiv='refresh' content='0;url={$redirectUrl}'></noscript>";
                exit;
            }
        } else {
            $msgErro = 'Nenhum dado retornado do MSSQL para o período e filtros selecionados.';
        }
    } catch (Throwable $e) {
        if ($pdoM->inTransaction()) $pdoM->rollBack();
        $msgErro = 'Erro ao gerar snapshot: ' . $e->getMessage();
    }
}

// --- CARREGAMENTO E PREPARAÇÃO DOS DADOS PARA EXIBIÇÃO ---
$rows = [];
if ($snapshotId) {
    $stmt = $pdoM->prepare("SELECT * FROM simulacao_desossa WHERE snapshot_id = :snapshot_id ORDER BY tipo_corte, nome_grupo_rend, desc_produto_est");
    $stmt->execute([':snapshot_id' => $snapshotId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pré-processamento dos dados para facilitar a renderização e calcular totais
$relatorio = [];
$ordemExibicao = ['TRASEIRO', 'DIANTEIRO', 'MIUDOS', 'OUTROS'];

if ($rows) {
    foreach($ordemExibicao as $tipo) {
        $relatorio[$tipo] = ['grupos' => [], 'total_qtde' => 0.0, 'total_proj' => 0.0, 'total_estoque' => 0.0, 'base' => 0];
    }

    foreach ($rows as $row) {
        $tipo  = $row['tipo_corte'];
        $grupo = $row['nome_grupo_rend'];

        if (!isset($relatorio[$tipo])) {
            $relatorio[$tipo] = ['grupos' => [], 'total_qtde' => 0.0, 'total_proj' => 0.0, 'total_estoque' => 0.0, 'base' => 0];
        }
        if (!isset($relatorio[$tipo]['grupos'][$grupo])) {
            $relatorio[$tipo]['grupos'][$grupo] = ['rows' => [], 'total_qtde' => 0.0, 'total_proj' => 0.0, 'total_estoque' => 0.0, 'base' => 0];
        }

        $relatorio[$tipo]['grupos'][$grupo]['rows'][] = $row;
        // Acumula totais
        $relatorio[$tipo]['grupos'][$grupo]['total_qtde'] += (float)$row['total_qtde_per'];
        $relatorio[$tipo]['grupos'][$grupo]['total_proj'] += (float)$row['proj_qtde'];
        $relatorio[$tipo]['grupos'][$grupo]['total_estoque'] += (float)$row['estoque_congelado'];
        $relatorio[$tipo]['grupos'][$grupo]['base'] = (float)$row['total_abate_per'];
        
        $relatorio[$tipo]['total_qtde'] += (float)$row['total_qtde_per'];
        $relatorio[$tipo]['total_proj'] += (float)$row['proj_qtde'];
        $relatorio[$tipo]['total_estoque'] += (float)$row['estoque_congelado'];
        $relatorio[$tipo]['base'] = (float)$row['total_abate_per'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Simulação da Desossa</title>
<style>
.card { border:1px solid #ddd; border-radius:8px; padding:15px; margin-bottom:20px; background:#fff; box-shadow:0 3px 6px rgba(0,0,0,.1);}
.card h4 { margin-top:0; margin-bottom:15px; font-weight:600; color:#174ea6; }
.table>thead { background:#007bff; color:#fff; }
tr.group-header td { background:#eef3ff; font-weight:bold; }
tr.group-total td { background:#f0f0f0; font-weight:bold; }
tr.type-total td { background:#d4edda; color:#155724; font-weight:bold; font-size:1.1em; }
</style>
</head>
<body>
<div class="container" style="margin-top:25px">
    <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title text-center">Simulação da Desossa</h3></div>
        <div class="panel-body">

            <?php if ($rows): $info = $rows[0]; ?>
            <div class="card" style="background:#f9f9f9">
                <h4>Resumo da Simulação</h4>
                <div class="row">
                    <div class="col-sm-3"><label><strong>Período Base</strong></label><p><?= htmlspecialchars(date("d/m/Y", strtotime($info['data_ini']))) ?> até <?= htmlspecialchars(date("d/m/Y", strtotime($info['data_fim']))) ?></p></div>
                    <div class="col-sm-2"><label><strong>Gerado em</strong></label><p><?= htmlspecialchars(date("d/m/Y H:i", strtotime($info['data_geracao']))) ?></p></div>
                    <div class="col-sm-2"><label><strong>Abate</strong></label><p><?= number_format($info['cabecas_hoje'],0,',','.') ?></p></div>
                    <div class="col-sm-2"><label><strong>Traseiro</strong></label><p><?= number_format($info['traseiro_hoje'],0,',','.') ?></p></div>
                    <div class="col-sm-2"><label><strong>Dianteiro</strong></label><p><?= number_format($info['dianteiro_hoje'],0,',','.') ?></p></div>
                    <div class="col-sm-1"><label><strong>PA</strong></label><p><?= number_format($info['pa_hoje'],0,',','.') ?></p></div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="form-horizontal">
                <input type="hidden" name="snapshot_id" value="<?= htmlspecialchars($snapshotId) ?>">
                <div class="card">
                    <h4>Período Base para Cálculo de Rendimento</h4>
                    <div class="row">
                        <div class="col-sm-3"><label>De</label><input type="date" name="dataIni" class="form-control" value="<?= htmlspecialchars($dataIni) ?>" required></div>
                        <div class="col-sm-3"><label>Até</label><input type="date" name="dataFim" class="form-control" value="<?= htmlspecialchars($dataFim) ?>" required></div>
                    </div>
                </div>
                <div class="card">
                    <h4>Parâmetros para Projeção</h4>
                    <div class="row">
                        <div class="col-sm-3"><label>Abate (Cabeças)</label><input type="number" name="cabecasHoje" class="form-control" value="<?= $cabecasHoje ?>" required></div>
                        <div class="col-sm-3"><label>Traseiro (Peças)</label><input type="number" name="traseiroHoje" class="form-control" value="<?= $traseiroHoje ?>" required></div>
                        <div class="col-sm-3"><label>Dianteiro (Peças)</label><input type="number" name="dianteiroHoje" class="form-control" value="<?= $dianteiroHoje ?>" required></div>
                        <div class="col-sm-3"><label>PA (Peças)</label><input type="number" name="paHoje" class="form-control" value="<?= $paHoje ?>" required></div>
                    </div>
                </div>
                <div class="text-center"><button type="submit" name="simular" class="btn btn-primary btn-lg"><span class="glyphicon glyphicon-flash"></span> Gerar Nova Simulação</button></div>
            </form>

            <?php if ($msgSucesso): ?><div class="alert alert-success" style="margin-top:20px;"><?= $msgSucesso ?></div><?php endif; ?>
            <?php if ($msgErro): ?><div class="alert alert-danger" style="margin-top:20px;"><?= $msgErro ?></div><?php endif; ?>
            
            <hr>

            <?php if ($rows): ?>
            <form method="POST">
                <input type="hidden" name="snapshot_id" value="<?= htmlspecialchars($snapshotId) ?>">

                <?php foreach ($ordemExibicao as $tipoNome): ?>
                    <?php if (isset($relatorio[$tipoNome]) && !empty($relatorio[$tipoNome]['grupos'])): ?>
                        <div class="card">
                            <h4><?= htmlspecialchars($tipoNome) ?></h4>
                            <table class="table table-bordered table-striped table-condensed">
                                <thead>
                                    <tr>
                                        <th>Grupo</th>
                                        <th>Cód.</th>
                                        <th>Descrição</th>
                                        <th class="text-right">Qtde (Período)</th>
                                        <th class="text-right">Base (Período)</th>
                                        <th class="text-right">Kg/Un.</th>
                                        <th class="text-right">Estoque Congelado</th> <th class="text-right" style="width: 150px;">Projeção Qtde</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($relatorio[$tipoNome]['grupos'] as $grupoNome => $grupoData): ?>
                                        <tr class="group-header">
                                            <td colspan="8">Grupo de Rendimento: <?= htmlspecialchars($grupoNome) ?></td>
                                        </tr>
                                        <?php foreach ($grupoData['rows'] as $r): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['nome_grupo_rend']) ?></td>
                                                <td><?= htmlspecialchars($r['cod_produto']) ?></td>
                                                <td><?= htmlspecialchars($r['desc_produto_est']) ?></td>
                                                <td class="text-right"><?= number_format((float)$r['total_qtde_per'], 3, ',', '.') ?></td>
                                                <td class="text-right"><?= number_format((float)$r['total_abate_per'], 0, ',', '.') ?></td>
                                                <td class="text-right"><?= number_format((float)$r['kg_por_cab'], 6, ',', '.') ?></td>
                                                <td class="text-right"><?= number_format((float)$r['estoque_congelado'], 3, ',', '.') ?></td>
                                                <td class="text-right">
                                                    <?php
                                                    $usuarioId = $_SESSION['user_id'] ?? null;
                                                    if ($usuarioId && (isAdmin($usuarioId) || isPcp($usuarioId))) {
                                                        echo "<input type='text' name='proj[".(int)$r['id']."]' 
                                                               value='".number_format((float)$r['proj_qtde'], 3, ',', '.') ."' 
                                                               class='form-control input-sm text-right'>";
                                                    } else {
                                                        echo number_format((float)$r['proj_qtde'], 3, ',', '.');
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="group-total">
                                            <td colspan="3" class="text-right">Total do Grupo: <?= htmlspecialchars($grupoNome) ?></td>
                                            <td class="text-right"><?= number_format($grupoData['total_qtde'], 3, ',', '.') ?></td>
                                            <td class="text-right"><?= number_format($grupoData['base'], 0, ',', '.') ?></td>
                                            <td class="text-right">
                                                <?php
                                                $mediaGrupo = ($grupoData['base'] > 0) ? $grupoData['total_qtde'] / $grupoData['base'] : 0;
                                                echo number_format($mediaGrupo, 6, ',', '.');
                                                ?>
                                            </td>
                                            <td class="text-right"><?= number_format($grupoData['total_estoque'], 3, ',', '.') ?></td>
                                            <td class="text-right"><?= number_format($grupoData['total_proj'], 3, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="type-total">
                                        <td colspan="3" class="text-right">TOTAL <?= htmlspecialchars($tipoNome) ?></td>
                                        <td class="text-right"><?= number_format($relatorio[$tipoNome]['total_qtde'], 3, ',', '.') ?></td>
                                        <td class="text-right"><?= number_format($relatorio[$tipoNome]['base'], 0, ',', '.') ?></td>
                                        <td class="text-right">
                                            <?php
                                            $mediaTipo = ($relatorio[$tipoNome]['base'] > 0) ? $relatorio[$tipoNome]['total_qtde'] / $relatorio[$tipoNome]['base'] : 0;
                                            echo number_format($mediaTipo, 6, ',', '.');
                                            ?>
                                        </td>
                                        <td class="text-right"><?= number_format($relatorio[$tipoNome]['total_estoque'], 3, ',', '.') ?></td>
                                        <td class="text-right"><?= number_format($relatorio[$tipoNome]['total_proj'], 3, ',', '.') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="text-right" style="margin-top:15px">
                    <button type="submit" name="salvar_edicoes" class="btn btn-success btn-lg">
                        <span class="glyphicon glyphicon-save"></span> Salvar Alterações
                    </button>
                </div>
            </form>
            <?php elseif (isset($_POST['simular'])): ?>
                <div class='alert alert-warning' style="margin-top:20px;">Nenhum dado encontrado para os filtros selecionados. Por favor, verifique o período e tente novamente.</div>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>