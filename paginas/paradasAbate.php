<?php
// Inicialização de variáveis
$dataSelecionada = isset($_POST['data']) ? $_POST['data'] : date('Y-m-d');

// Consulta SQL para buscar os dados com filtro por data
$sql = "SELECT B.motivo, SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, tempo1, tempo2))) AS total_tempo
        FROM paradaabate A
		INNER JOIN motivosparada B ON A.motivo = B.id
        WHERE DATE(data) = :data
        GROUP BY motivo
        ORDER BY total_tempo DESC";
$stmt = $pdoM->prepare($sql);
$stmt->execute(['data' => $dataSelecionada]);

// Calcular o total geral de tempo
$totalGeralSegundos = 0;
$dados = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dados[] = $row;
    list($horas, $minutos, $segundos) = explode(':', $row['total_tempo']);
    $totalGeralSegundos += $horas * 3600 + $minutos * 60 + $segundos;
}
$totalGeral = sprintf('%02d:%02d:%02d', floor($totalGeralSegundos / 3600), ($totalGeralSegundos / 60) % 60, $totalGeralSegundos % 60);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório de Paradas</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <style>
    .report-header {
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 3px solid #007bff;
      text-align: center;
    }
    .report-header h4 {
      font-weight: bold;
      color: #007bff;
    }
    .form-group {
      margin-bottom: 15px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }
    th {
      background-color: #007bff;
      color: #fff;
      text-align: center;
      padding: 10px;
    }
    td {
      text-align: center;
      padding: 10px;
    }
    .totalizador {
      font-weight: bold;
      background-color: #f1f1f1;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Cabeçalho -->
    <div class="report-header">
      <h4>Relatório de Paradas</h4>
      <p>Data selecionada: <strong><?= htmlspecialchars($dataSelecionada) ?></strong></p>
    </div>

    <!-- Formulário para selecionar a data -->
    <form method="POST" style="margin-bottom: 20px;">
      <div class="form-group">
        <label for="data">Selecione a Data:</label>
        <input type="date" id="data" name="data" class="form-control" value="<?= htmlspecialchars($dataSelecionada) ?>" required>
      </div>
      <div class="form-group">
        <button type="submit" class="btn btn-primary">Filtrar</button>
      </div>
    </form>

    <!-- Tabela -->
    <table id="relatorio" class="display nowrap stripe" style="width:100%">
      <thead>
        <tr>
          <th>Motivo de Parada</th>
          <th>Tempo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dados as $row): ?>
        <tr>
          <td><?= mb_convert_encoding($row['motivo'], 'UTF-8', 'ISO-8859-1'); ?></td>
          <td><?= htmlspecialchars($row['total_tempo']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="totalizador">
          <td>Total Geral</td>
          <td><?= $totalGeral ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

<script>
  $(document).ready(function() {
    $('#relatorio').DataTable({
      dom: 'Bfrtip',
      buttons: [
        {
          extend: 'pdfHtml5',
          title: 'Relatório de Paradas',
          footer: true,
          orientation: 'portrait', // Modo retrato (vertical). Altere para 'landscape' se precisar horizontal.
          pageSize: 'A4',
          customize: function(doc) {
            // Configurar margens mínimas para maximizar o espaço
            doc.pageMargins = [10, 10, 10, 10]; // Margens: [esquerda, topo, direita, base]

            // Configurar fontes e estilos gerais
            doc.defaultStyle = {
              fontSize: 12, // Tamanho padrão da fonte
              alignment: 'center' // Centralizar conteúdo
            };

            // Configurar cabeçalho da tabela
            doc.styles.tableHeader = {
              fillColor: '#007bff',
              color: 'white',
              alignment: 'center',
              fontSize: 14, // Tamanho da fonte do cabeçalho
              bold: true
            };

            // Ajustar largura das colunas para ocupar todo o espaço
            doc.content[1].table.widths = ['50%', '50%']; // Divisão proporcional das colunas

            // Expandir conteúdo para ocupar todo o espaço disponível
            doc.content[1].layout = {
              hLineWidth: function(i, node) {
                return (i === 0 || i === node.table.body.length) ? 2 : 1; // Linhas mais grossas para bordas externas
              },
              vLineWidth: function(i, node) {
                return (i === 0 || i === node.table.widths.length) ? 2 : 1; // Linhas mais grossas para bordas externas
              },
              hLineColor: function(i, node) {
                return (i === 0 || i === node.table.body.length) ? '#007bff' : '#ccc';
              },
              vLineColor: function(i, node) {
                return (i === 0 || i === node.table.widths.length) ? '#007bff' : '#ccc';
              },
              paddingLeft: function(i) { return 10; },
              paddingRight: function(i) { return 10; },
              paddingTop: function(i) { return 5; },
              paddingBottom: function(i) { return 5; }
            };
          }
        },
        {
          extend: 'excelHtml5',
          title: 'Relatório de Paradas',
          footer: true
        },
        {
          extend: 'print',
          title: 'Relatório de Paradas',
          footer: true
        }
      ],
      responsive: true,
      pageLength: 25
    });
  });
</script>

