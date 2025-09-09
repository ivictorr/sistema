<?php

// Locais permitidos por filial
$locaisPermitidosPorFilial = [
    '100' => ['01'],
    '200' => ['01', '04'],
    '400' => ['01'], // <- Altamira
];

// Obtem os locais disponíveis no estoque
$locaisEstoque = [];
foreach ($locaisPermitidosPorFilial as $filial => $locais) {
    $placeholders = implode("','", $locais);
    $res = $pdoS->query("SELECT Cod_filial, Cod_local, Desc_local 
        FROM tbLocalEstoque 
        WHERE Estoque_disponivel = 'S' 
        AND Cod_filial = '$filial' 
        AND Cod_local IN ('$placeholders') 
        ORDER BY Cod_filial, Cod_local");

    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $chave = $r['Cod_filial'] . '-' . $r['Cod_local'];
        $locaisEstoque[$chave] = $r['Desc_local'] . ' (Filial ' . $r['Cod_filial'] . ')';
    }
}

$emissao_de = $_POST['emissao_de'] ?? '';
$emissao_ate = $_POST['emissao_ate'] ?? '';
$tipo_venda = $_POST['tipo_venda'] ?? 'TODOS';

// Consulta principal com grupo de rendimento
$sql = "SELECT 
    B.Cod_produto, 
    MAX(tbp.Desc_produto_est) AS NOME,
    SUM(B.Qtde_aux) AS CX, 
    SUM(B.Qtde_pri) AS KG,
    SUM(B.Valor_total) AS VALOR,
    B.Cod_local,
    A.Cod_filial,
    CASE 
        WHEN tbp.Cod_produto IN ('20034', '20051', '20052', '20059', '20040','20053') THEN 'COSTELA'
        WHEN tbp.Cod_produto IN ('20056', '20058', '20036', '20060', '20055', '20057', '30143') THEN 'DIANTEIRO'
        WHEN tbp.Cod_produto IN ('30326','30167','30172','30168','30173','30148') THEN 'MIÚDOS'
        WHEN tbp.Cod_produto BETWEEN '20000' AND '29999' THEN 'MATERIA PRIMA'
        WHEN tbp.Cod_grupo_rend LIKE 'D%'  THEN 'DIANTEIRO'
        WHEN tbp.Cod_grupo_rend LIKE 'T%'  THEN 'TRASEIRO'
        WHEN tbp.Cod_grupo_rend LIKE 'C%'  THEN 'COSTELA'
        WHEN tbp.Cod_grupo_rend LIKE 'M%' AND tbp.Cod_produto NOT IN ('35064', '35069')  THEN 'MIÚDOS'
        ELSE 'OUTROS'
    END AS Grupo
FROM tbSaidas A
INNER JOIN tbSaidasItem B ON A.CHAVE_FATO = B.CHAVE_FATO AND B.Num_subItem = 0
INNER JOIN tbProduto tbp ON B.Cod_produto = tbp.Cod_produto
INNER JOIN TBTIPOMVESTOQUE D ON A.COD_TIPO_MV = D.COD_TIPO_MV AND A.COD_DOCTO = D.COD_DOCTO
WHERE A.COD_DOCTO = 'NE' 
    AND B.Cod_produto BETWEEN '20000' AND '39999' 
    AND B.Qtde_pri > 0 
    AND A.Status <> 'C'";
if ($tipo_venda === 'MI') {
    $sql .= " AND (D.Perfil_tmv IN ('VDA0301') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
} elseif ($tipo_venda === 'ME') {
    $sql .= " AND D.Perfil_tmv IN ('VDA0302')";
} elseif ($tipo_venda === 'TR') {
    $sql .= " AND A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520')";
} else {
    $sql .= " AND (D.Perfil_tmv IN ('VDA0301','VDA0302') OR A.Cod_tipo_mv IN ('T186', 'T570', 'T571', 'X520'))";
}
if (!empty($emissao_de)) $sql .= " AND A.Data_movto >= '$emissao_de'";
if (!empty($emissao_ate)) $sql .= " AND A.Data_movto <= '$emissao_ate'";

$sql .= " GROUP BY B.Cod_produto, A.Cod_filial, B.Cod_local, tbp.Cod_grupo_rend, tbp.Cod_produto";

$stmt = $pdoS->prepare($sql);
$stmt->execute();
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupamento por grupo
$produtosPorGrupo = [];
foreach ($dados as $row) {
    $grupo = $row['Grupo'];
    $produto = $row['Cod_produto'];
    $filialLocal = $row['Cod_filial'] . '-' . $row['Cod_local'];
    $media = $row['VALOR'] / max($row['KG'], 1);
    $peso = $row['KG'];
    $nome = $row['NOME'];

    if (!isset($produtosPorGrupo[$grupo])) {
        $produtosPorGrupo[$grupo] = [];
    }

    if (!isset($produtosPorGrupo[$grupo][$produto])) {
        $produtosPorGrupo[$grupo][$produto] = [
            'NOME' => $nome,
            'DADOS' => []
        ];
    }

    $produtosPorGrupo[$grupo][$produto]['DADOS'][$filialLocal] = [
        'media' => $media,
        'peso' => $peso
    ];
}

// Abreviação dos nomes dos locais
$abreviacoes = [
    'Xinguara' => 'Xinguara',
    'Jatai' => 'Jataí',
    'José dos Campos' => 'S. Paulo',
    'Altamira' => 'Altamira',
];
function abreviarNomeLocal($desc_local) {
    global $abreviacoes;

    // Garante que $abreviacoes seja array
    if (!is_array($abreviacoes)) {
        $abreviacoes = [];
    }

    // Garante que desc_local seja string
    $desc_local = (string)$desc_local;

    foreach ($abreviacoes as $completo => $abreviado) {
        if (stripos($desc_local, $completo) !== false) {
            return $abreviado;
        }
    }
    return $desc_local;
}


$tipoVendaSelecionado = $savedFilters['tipo_venda'] ?? 'TODOS';

?>

<style>
    body { font-family: 'Segoe UI', sans-serif; }
    .container { max-width: 98%; margin: auto; }
    h3 { font-weight: bold; margin-bottom: 30px; }
    table.dataTable thead th { background-color: #388e3c !important; color: white; }
    .table tfoot { font-weight: bold; background: #e8f5e9; }
    .grupo-header { background: #e0f2f1; padding: 10px; font-weight: bold; font-size: 1.1em; border-radius: 5px; margin-top: 40px; }
@media print {
    /* Oculta elementos não necessários */
    .hidden-print,
    .dataTables_wrapper,
    form,
    .btn,
    .buttons-excel,
    .buttons-csv,
    .buttons-pdf,
    .buttons-print {
        display: none !important;
    }

    /* Força cada grupo começar em nova página, exceto o primeiro */
    .grupo-header {
        page-break-before: always;
        margin-top: 0;
    }

    .grupo-header:first-of-type {
        page-break-before: auto;
    }

    /* Tamanho da página e margem de impressão */
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        font-size: 11pt;
        margin: 0;
    }

    table {
        page-break-inside: avoid;
        width: 100% !important;
    }

    .container {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .grupo-header {
        background: #e0f2f1 !important;
        color: #000;
        padding: 10px;
        font-size: 1.2em;
        font-weight: bold;
    }

    tfoot td {
        background-color: #f1f8e9 !important;
    }
}
</style>
<br>
<br>
<br>
<div class="container">
    <div class="panel panel-success">
        <div class="panel-heading text-center">
            <h3><i class="glyphicon glyphicon-stats"></i> RELATÓRIO DE PREÇO MÉDIO POR LOCAL DE VENDA</h3>
        </div>
    </div>

<form method="POST" class="well well-sm" style="background-color: #f9f9f9; border: 1px solid #ccc; border-radius: 6px; padding: 20px; margin-bottom: 30px;">
    <div class="row">
        <div class="col-sm-5 col-sm-offset-1">
            <label>Data de Emissão (De):</label>
            <input type="date" name="emissao_de" class="form-control input-lg" value="<?= $emissao_de ?>">
        </div>
        <div class="col-sm-5">
            <label>Data de Emissão (Até):</label>
            <input type="date" name="emissao_ate" class="form-control input-lg" value="<?= $emissao_ate ?>">
        </div>
    </div>

    <div class="row" style="margin-top: 20px;">
        <div class="col-sm-10 col-sm-offset-1">
            <label>Tipo de Venda:</label>
<select name="tipo_venda" class="selectpicker form-control input-lg" title="Selecione uma ou mais opções">
    <option value="TODOS" <?= $tipoVendaSelecionado == 'TODOS' ? 'selected' : '' ?>>Todos</option>
    <option value="MI" <?= $tipoVendaSelecionado == 'MI' ? 'selected' : '' ?>>Venda Mercado Interno</option>
    <option value="ME" <?= $tipoVendaSelecionado == 'ME' ? 'selected' : '' ?>>Venda Mercado Externo</option>
</select>
        </div>
    </div>

    <div class="text-center" style="margin-top: 25px;">
        <button type="submit" name="gerarRelatorio" class="btn btn-success btn-lg">
            <i class="glyphicon glyphicon-search"></i> Gerar Relatório
        </button>
    </div>
</form>

<?php if (isset($_POST['gerarRelatorio'])): ?>

    <!-- Ações globais: Buscar + Exportar -->
    <div class="no-print" style="margin: 15px 0 25px; display:flex; gap:10px; align-items:center;">
      <div style="flex:1">
        <input id="globalSearch" class="form-control" placeholder="Buscar em todos os resultados...">
      </div>
      <button id="exportExcel" class="btn btn-success">
        <span class="glyphicon glyphicon-download"></span> Exportar (Excel)
      </button>
    </div>

    <?php foreach ($produtosPorGrupo as $grupo => $produtos): 
        $totalPesoGrupo = 0;
        $totalValorGrupo = 0;
    ?>

        <div class="grupo-header text-center"><?= strtoupper($grupo) ?></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped display datatable" style="width:100%">
                <thead>
                    <tr class="text-center">
                        <th>PRODUTO</th>
                        <th>NOME</th>
                        <?php foreach ($locaisEstoque as $codLocal => $descLocal): ?>
                            <th><?= abreviarNomeLocal($descLocal) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="text-center" style="font-size:13px">
                    <?php foreach ($produtos as $codProduto => $info): 
                        $pesoProduto = 0;
                        $valorProduto = 0;
                    ?>
                        <tr>
                            <td><strong><?= $codProduto ?></strong></td>
                            <td><?= $info['NOME'] ?></td>
                            <?php foreach ($locaisEstoque as $codLocal => $descLocal): ?>
                                <?php
                                $media = $info['DADOS'][$codLocal]['media'] ?? 0;
                                $peso = $info['DADOS'][$codLocal]['peso'] ?? 0;
                                $valor = $media * $peso;
                                $pesoProduto += $peso;
                                $valorProduto += $valor;
                                ?>
                                <td>
                                    <div><strong>Média:</strong> R$ <?= number_format($media, 2, ',', '.') ?></div>
                                    <div><strong>Peso:</strong> <?= number_format($peso, 2, ',', '.') ?></div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php
                            $totalPesoGrupo += $pesoProduto;
                            $totalValorGrupo += $valorProduto;
                        ?>
                    <?php endforeach; ?>

                    <!-- Linha de totais por local -->
                    <tr style="background-color: #e3f2fd; font-weight: bold;">
                        <td colspan="2" class="text-right">MÉDIA FINAL POR LOCAL:</td>
                        <?php
                        $totaisPorLocal = [];
                        foreach ($produtos as $codProduto => $info) {
                            foreach ($locaisEstoque as $codLocal => $descLocal) {
                                if (!isset($info['DADOS'][$codLocal])) continue;
                                $peso = $info['DADOS'][$codLocal]['peso'];
                                $media = $info['DADOS'][$codLocal]['media'];
                                $valor = $media * $peso;

                                if (!isset($totaisPorLocal[$codLocal])) {
                                    $totaisPorLocal[$codLocal] = ['peso' => 0, 'valor' => 0];
                                }

                                $totaisPorLocal[$codLocal]['peso'] += $peso;
                                $totaisPorLocal[$codLocal]['valor'] += $valor;
                            }
                        }

                        foreach ($locaisEstoque as $codLocal => $descLocal):
                            $peso = $totaisPorLocal[$codLocal]['peso'] ?? 0;
                            $valor = $totaisPorLocal[$codLocal]['valor'] ?? 0;
                            $mediaFinal = ($peso > 0) ? $valor / $peso : 0;
                        ?>
                            <td>
                                <div><strong>Média:</strong> R$  <?= number_format($mediaFinal, 2, ',', '.') ?></div>
                                <div><strong>Peso:</strong> <?= number_format($peso, 2, ',', '.') ?></div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>

                <tfoot>
                    <?php
                    // Inicializa acumuladores por filial
                    $totaisPorFilial = [];

                    foreach ($produtos as $codProduto => $info) {
                        foreach ($info['DADOS'] as $codLocalCompleto => $dados) {
                            list($filial, $local) = explode('-', $codLocalCompleto);

                            // Verifica se esse local está autorizado no array $locaisPermitidosPorFilial
                            if (!isset($locaisPermitidosPorFilial[$filial]) || !in_array($local, $locaisPermitidosPorFilial[$filial])) {
                                continue;
                            }

                            if (!isset($totaisPorFilial[$filial])) {
                                $totaisPorFilial[$filial] = ['peso' => 0, 'valor' => 0];
                            }

                            $peso = $dados['peso'];
                            $media = $dados['media'];
                            $valor = $media * $peso;

                            $totaisPorFilial[$filial]['peso'] += $peso;
                            $totaisPorFilial[$filial]['valor'] += $valor;
                        }
                    }

                    foreach ($locaisPermitidosPorFilial as $filial => $locais):
                        $pesoFilial = $totaisPorFilial[$filial]['peso'] ?? 0;
                        $valorFilial = $totaisPorFilial[$filial]['valor'] ?? 0;
                        $mediaFilial = ($pesoFilial > 0) ? $valorFilial / $pesoFilial : 0;
                    ?>
                    <tr style="background-color: #fff3e0; font-weight: bold;">
                        <td colspan="2" class="text-right">TOTAL FILIAL <?= $filial ?> (Locais <?= implode(', ', $locais) ?>):</td>
                        <td colspan="<?= count($locaisEstoque) ?>" class="text-left">
                            <?= number_format($pesoFilial, 2, ',', '.') ?> Kg — 
                            R$ <?= number_format($valorFilial, 2, ',', '.') ?> — 
                            Média: R$ <?= number_format($mediaFilial, 2, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tfoot>

            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- ===== Vanilla JS: Busca global + Export CSV (Excel) ===== -->
<script>
// Normaliza texto (remove acentos e baixa caixa)
const norm = s => (s ?? '').toString().toLowerCase()
  .normalize('NFD').replace(/\p{Diacritic}/gu,'');

// Aplica busca global em todas as tabelas
function applyGlobalSearch(termRaw) {
  const term = norm(termRaw.trim());
  const tables = document.querySelectorAll('table.display.datatable');

  tables.forEach(tbl => {
    const tbody = tbl.tBodies[0];
    if (!tbody) return;

    Array.from(tbody.rows).forEach(tr => {
      // Mantém linhas de totais (as que têm colspan)
      const isTotalRow = !!tr.querySelector('td[colspan]');
      if (isTotalRow) { tr.style.display = ''; return; }

      const txt = norm(tr.textContent);
      const match = term ? txt.includes(term) : true;
      tr.style.display = match ? '' : 'none';
    });
  });
}

// Extrai o nome do grupo acima da tabela
function getGroupNameForTable(tbl) {
  // Busca o .grupo-header mais próximo acima da tabela
  let el = tbl;
  while (el && el.previousElementSibling) {
    el = el.previousElementSibling;
    if (el.classList && el.classList.contains('grupo-header')) {
      return el.textContent.trim();
    }
  }
  // fallback
  return '';
}

// Monta CSV com todas as linhas VISÍVEIS (exclui totais)
function buildCSV() {
  const rows = [];
  // Cabeçalho básico
  rows.push(['Grupo','Produto','Nome', 'Colunas por Local (texto da célula)']);

  const tables = document.querySelectorAll('table.display.datatable');

  tables.forEach(tbl => {
    const group = getGroupNameForTable(tbl);
    const tbody = tbl.tBodies[0];
    if (!tbody) return;

    Array.from(tbody.rows).forEach(tr => {
      if (tr.style.display === 'none') return; // filtrada
      if (tr.querySelector('td[colspan]')) return; // linha de "MÉDIA FINAL POR LOCAL"

      const tds = tr.cells;
      if (tds.length < 2) return;

      const produto = (tds[0]?.innerText || '').trim();
      const nome    = (tds[1]?.innerText || '').trim();

      // Junta o conteúdo das colunas de locais em um único campo (mantendo quebras de linha dentro da célula)
      // Se preferir 1 coluna por local, dá pra expandir aqui.
      const locaisTxt = Array.from(tds)
        .slice(2) // só locais
        .map(td => (td.innerText || '').replace(/\r?\n/g, ' | ').trim())
        .join(' || ');

      rows.push([group, produto, nome, locaisTxt]);
    });
  });

  // Gera CSV com ; (bom para Excel pt-BR) e BOM para acentuação
  const sep = ';';
  const csv = '\uFEFF' + rows.map(r =>
    r.map(field => {
      const f = String(field ?? '').replace(/"/g,'""');
      return /[;"\n\r]/.test(f) ? `"${f}"` : f;
    }).join(sep)
  ).join('\r\n');

  return csv;
}

// Dispara download do CSV
function downloadCSV(filename, content) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  setTimeout(() => {
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }, 0);
}

// Timestamp simples
function timestamp() {
  const d = new Date();
  const pad = n => String(n).padStart(2,'0');
  return d.getFullYear().toString() + pad(d.getMonth()+1) + pad(d.getDate()) + '_' + pad(d.getHours()) + pad(d.getMinutes());
}

// Liga eventos (se existir resultado na página)
(function initVanilla(){
  const globalInput = document.getElementById('globalSearch');
  const exportBtn   = document.getElementById('exportExcel');

  if (globalInput) {
    globalInput.addEventListener('input', () => applyGlobalSearch(globalInput.value));
  }
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      const csv = buildCSV();
      downloadCSV('relatorio_preco_medio_' + timestamp() + '.csv', csv);
    });
  }
})();
</script>
