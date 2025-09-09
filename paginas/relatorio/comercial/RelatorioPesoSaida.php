<?php

$dataInicio = $_POST['inicio'] ?? null;
$dataFim = $_POST['fim'] ?? null;

// FILIAL GLOBAL
$filial = $GLOBALS['FILIAL_USUARIO'] ?? '100';

$dadosAbate = [];
$bonusPorDia = [];
$pesosPorParte = [];
$pecasPorParte = [];
$destacadosPorDia = [];

$mapaPartes = [
  'Boi' => [
    'Traseiro' => ['20001','20009','20010','20017','20019','20020','20024','20031','20032'],
    'Dianteiro' => ['20002','20026','20027','20030'],
    'Ponta' => ['20003','20012','20013'],
  ],
  'Vaca' => [
    'Traseiro' => ['20006','20008','20011','20016','20018','20021','20022','20023','20033'],
    'Dianteiro' => ['20005','20025','20028','20029'],
    'Ponta' => ['20004','20014','20015'],
  ]
];

// Mapeia código do produto para tipo/parte
$produtoParaParte = [];
foreach ($mapaPartes as $tipo => $grupos) {
  foreach ($grupos as $parte => $codigos) {
    foreach ($codigos as $codigo) {
      $produtoParaParte[$codigo] = [$tipo, $parte];
    }
  }
}

// SOMENTE SE DATA INFORMADA
if ($dataInicio && $dataFim) {

  // ====================
  // CONSULTA DE ABATES
  // ====================
  $query = $pdoS->prepare("
    SELECT CAST(Datahora AS DATE) AS data_abate, peso_carcaca1, peso_carcaca2 
    FROM tbromaneioabate 
    WHERE CAST(Datahora AS DATE) BETWEEN :inicio AND :fim
      AND Cod_filial = :filial
  ");
  $query->execute([
    ':inicio' => $dataInicio,
    ':fim' => $dataFim,
    ':filial' => $filial
  ]);
  $abates = $query->fetchAll(PDO::FETCH_ASSOC);

  foreach ($abates as $linha) {
    $data = $linha['data_abate'];
    $peso1 = floatval($linha['peso_carcaca1']);
    $peso2 = floatval($linha['peso_carcaca2']);
    $bonus = 0.002;
    $pesoBonificado = ($peso1 * (1 + $bonus)) + ($peso2 * (1 + $bonus));
    $dadosAbate[$data]['animais'] = ($dadosAbate[$data]['animais'] ?? 0) + 1;
    $dadosAbate[$data]['peso_quente'] = ($dadosAbate[$data]['peso_quente'] ?? 0) + ($peso1 + $peso2);
    $bonusPorDia[$data] = ($bonusPorDia[$data] ?? 0) + $pesoBonificado;
  }

  // ====================
  // CONSULTA SAÍDAS POR PARTE
  // ====================
  $querySaida = $pdoS->prepare("
    SELECT 
      CAST(A.Data_abate AS DATE) AS data_saida, 
      TRIM(B.Cod_produto) AS Cod_produto, 
      SUM(B.Peso_Liquido) AS peso_saida,
      SUM(B.Qtde_sacos) AS qtd_saida
    FROM tbRomaneioAbate A
    INNER JOIN TBVOLUME B 
      ON A.Chave_fato = B.Chave_fato_abate 
     AND A.Seq_cabeca = B.Seq_cabeca
    WHERE A.Data_abate BETWEEN :inicio AND :fim
      AND A.Cod_filial = :filial
      AND B.Status <> 'C'
      AND TRIM(B.Cod_produto) IN (" . implode(",", array_map(fn($x) => "'$x'", array_keys($produtoParaParte))) . ")
    GROUP BY CAST(A.Data_abate AS DATE), TRIM(B.Cod_produto)
  ");
  $querySaida->execute([
    ':inicio' => $dataInicio,
    ':fim' => $dataFim,
    ':filial' => $filial
  ]);

  foreach ($querySaida as $linha) {
    $data = $linha['data_saida'];
    $cod = $linha['Cod_produto'];
    $peso = floatval($linha['peso_saida']);
    $qtd = intval($linha['qtd_saida']);
    [$tipo, $parte] = $produtoParaParte[$cod];
    $pesosPorParte[$data][$tipo][$parte] = ($pesosPorParte[$data][$tipo][$parte] ?? 0) + $peso;
    $pecasPorParte[$data][$tipo][$parte] = ($pecasPorParte[$data][$tipo][$parte] ?? 0) + $qtd;
  }

  // ====================
  // CONSULTA DESTACADOS (30408, 30410, 30411)
  // ====================
  $periodo = new DatePeriod(new DateTime($dataInicio), new DateInterval('P1D'), (new DateTime($dataFim))->modify('+1 day'));
  foreach ($periodo as $dia) {
    $dataSelecionada = $dia->format('Y-m-d');
    $stmt = $pdoS->prepare("
      SELECT
        SUM(CASE WHEN B.COD_PRODUTO = '30408' THEN B.Qtde_pri ELSE 0 END) AS peso_30408,
        SUM(CASE WHEN B.COD_PRODUTO = '30410' THEN B.Qtde_pri ELSE 0 END) AS peso_30410,
        SUM(CASE WHEN B.COD_PRODUTO = '30411' THEN B.Qtde_pri ELSE 0 END) AS peso_30411
      FROM TBENTRADAS A
      INNER JOIN TBENTRADASITEM B 
        ON A.CHAVE_FATO = B.CHAVE_FATO 
       AND B.NUM_SUBITEM = '0'
      WHERE A.COD_DOCTO = 'QTE' 
        AND A.SERIE_SEQ = 'QB1' 
        AND A.COD_FILIAL = :filial
        AND B.COD_PRODUTO IN ('30408','30410','30411') 
        AND A.Data_movto = :data
    ");
    $stmt->execute([
      ':data' => $dataSelecionada,
      ':filial' => $filial
    ]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $destacadosPorDia[$dataSelecionada] = array_sum(array_map('floatval', $res));
  }

}
?>



<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Resumo de Saída de Animais</title>
  <style>
    h3 { font-weight: bold; margin-bottom: 30px; }
    .table th { background: #388e3c; color: white; }
    .porc { font-weight: bold; color: #2e6da4; }
    #chartContainer { margin-top: 40px; max-width: 900px; margin-left: auto; margin-right: auto; }
	.total-percentual {
  background-color: #f5f5f5;
  font-weight: bold;
  color: #333;
  text-align: left;
  padding: 10px;
}

  </style>
<br><br><br>
<div class="container">
  <div class="panel panel-success">
    <div class="panel-heading text-center">
      <h3><i class="glyphicon glyphicon-stats"></i> RELATÓRIO DE SAÍDA DE ANIMAIS</h3>
    </div>
  </div>

  <form method="post" class="well well-sm">
    <div class="row">
      <div class="col-sm-5 col-sm-offset-1">
        <label>Data Início:</label>
        <input type="date" name="inicio" class="form-control input-lg" value="<?= $dataInicio ?>">
      </div>
      <div class="col-sm-5">
        <label>Data Fim:</label>
        <input type="date" name="fim" class="form-control input-lg" value="<?= $dataFim ?>">
      </div>
    </div>
    <div class="text-center" style="margin-top: 20px;">
      <button type="submit" class="btn btn-success btn-lg"><i class="glyphicon glyphicon-search"></i> Gerar</button>
    </div>
  </form>

<?php if (!empty($dadosAbate)): ?>
  <div class="table-responsive">
    <table class="table table-bordered text-center">
      <thead>
        <tr>
          <th>Data</th>
          <th>Animais</th>
          <th>T. Boi</th>
          <th>D. Boi</th>
          <th>P. Boi</th>
          <th>T. Vaca</th>
          <th>D. Vaca</th>
          <th>P. Vaca</th>
          <th>Destacado</th>
          <th>Total</th>
          <th class="total-percentual">Total %</th>
        </tr>
      </thead>
      <tbody>
<?php
$totalMes = ['peso' => 0, 'partes' => ['Traseiro' => 0, 'Dianteiro' => 0, 'Ponta' => 0]];

foreach (array_keys($dadosAbate) as $data):
  $animais = $dadosAbate[$data]['animais'];

  // Inicialização segura
  $partes = $pesosPorParte[$data] ?? [];
  $pecas = $pecasPorParte[$data] ?? [];

  foreach (['Boi', 'Vaca'] as $tipo) {
    foreach (['Traseiro', 'Dianteiro', 'Ponta'] as $parte) {
      if (!isset($partes[$tipo][$parte])) {
        $partes[$tipo][$parte] = 0;
      }
      if (!isset($pecas[$tipo][$parte])) {
        $pecas[$tipo][$parte] = 0;
      }
    }
  }

  $dest = $destacadosPorDia[$data] ?? 0;
  $pesoBoi = array_sum($partes['Boi']);
  $pesoVaca = array_sum($partes['Vaca']);
  $totalGeral = $pesoBoi + $pesoVaca + $dest;
  $totalMes['peso'] += $totalGeral;

  foreach (['Traseiro', 'Dianteiro', 'Ponta'] as $parte) {
    $totalMes['partes'][$parte] += ($partes['Boi'][$parte] + $partes['Vaca'][$parte]);
  }
?>

        <tr>
          <td><?= date('d/m/Y', strtotime($data)) ?></td>
          <td><?= $animais ?></td>
          <?php foreach (['Traseiro','Dianteiro','Ponta'] as $parte): ?>
            <td>
              <?= number_format($partes['Boi'][$parte], 2, ',', '.') ?><br>
              <small><?= $pecas['Boi'][$parte] ?? 0 ?> pcs</small><br>
              <span class="porc"><?= $pesoBoi > 0 ? number_format($partes['Boi'][$parte] / $pesoBoi * 100, 2, ',', '.') : '0,00' ?>%</span>
            </td>
          <?php endforeach; ?>
          <?php foreach (['Traseiro','Dianteiro','Ponta'] as $parte): ?>
            <td>
              <?= number_format($partes['Vaca'][$parte], 2, ',', '.') ?><br>
              <small><?= $pecas['Vaca'][$parte] ?? 0 ?> pcs</small><br>
              <span class="porc"><?= $pesoVaca > 0 ? number_format($partes['Vaca'][$parte] / $pesoVaca * 100, 2, ',', '.') : '0,00' ?>%</span>
            </td>
          <?php endforeach; ?>
          <td><?= number_format($dest, 2, ',', '.') ?></td>
          <td><strong><?= number_format($totalGeral, 2, ',', '.') ?></strong></td>
          <td class="info">
            <?php foreach (['Traseiro','Dianteiro','Ponta'] as $parte): ?>
              <div><strong><?= $parte ?>:</strong> <?= number_format(($partes['Boi'][$parte]+$partes['Vaca'][$parte])/$totalGeral*100, 2, ',', '.') ?>%</div>
            <?php endforeach; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="9" class="text-right">Total do Mês:</th>
          <th><?= number_format($totalMes['peso'], 2, ',', '.') ?></th>
          <th class="total-percentual">
            <?php foreach (['Traseiro','Dianteiro','Ponta'] as $parte): ?>
              <div><strong><?= $parte ?>:</strong> <?= number_format($totalMes['partes'][$parte]/$totalMes['peso']*100, 2, ',', '.') ?>%</div>
            <?php endforeach; ?>
          </th>
        </tr>
      </tfoot>
    </table>
  </div>

  <div id="chartContainer">
    <canvas id="grafico"></canvas>
  </div>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('grafico').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Traseiro', 'Dianteiro', 'Ponta'],
      datasets: [{
        label: 'Percentual no mês (%)',
        data: [
          <?= number_format($totalMes['partes']['Traseiro'] / $totalMes['peso'] * 100, 2, '.', '') ?>,
          <?= number_format($totalMes['partes']['Dianteiro'] / $totalMes['peso'] * 100, 2, '.', '') ?>,
          <?= number_format($totalMes['partes']['Ponta'] / $totalMes['peso'] * 100, 2, '.', '') ?>
        ],
        backgroundColor: ['#4caf50', '#2196f3', '#ff9800']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ctx.raw + '%' } }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: value => value + '%' }
        }
      }
    }
  });
</script>

