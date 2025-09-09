<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Comercial</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- DataTables CSS -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

  <style>
    body {
      background-color: #f8f9fa;
    }
    .panel {
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    .filter-box {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    .kpi {
      padding: 20px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      text-align: center;
    }
    .kpi h6 {
      margin: 0;
      font-size: 14px;
      color: #666;
    }
    .kpi h3 {
      margin: 5px 0 0;
      font-size: 24px;
    }
  </style>

<div class="container">
  <!-- Filtro com slider -->
  <div class="filter-box">
    <label for="rangeDays">Período (dias)</label>
    <input type="range" min="7" max="365" step="7" value="30" id="rangeDays">
    <p>Intervalo selecionado: <span id="rangeValue">30 dias</span></p>
  </div>

  <!-- KPIs -->
  <div class="row">
    <div class="col-sm-3">
      <div class="kpi">
        <h6>Total Vendas</h6>
        <h3>R$ 500.000</h3>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="kpi">
        <h6>Clientes Ativos</h6>
        <h3>120</h3>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="kpi">
        <h6>Ticket Médio</h6>
        <h3>R$ 4.200</h3>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="kpi">
        <h6>Última Venda</h6>
        <h3>09/07/2025</h3>
      </div>
    </div>
  </div>

  <!-- Tabelas -->
  <div class="row" style="margin-top:30px;">
    <div class="col-sm-6">
      <div class="panel panel-default">
        <div class="panel-heading">Vendas por Estado</div>
        <div class="panel-body">
          <table id="stateTable" class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Estado</th>
                <th>Vendas (R$)</th>
                <th>%</th>
              </tr>
            </thead>
            <tbody>
              <tr><td>SP</td><td>200000</td><td>40%</td></tr>
              <tr><td>RJ</td><td>150000</td><td>30%</td></tr>
              <tr><td>MG</td><td>100000</td><td>20%</td></tr>
              <tr><td>RS</td><td>50000</td><td>10%</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-sm-6">
      <div class="panel panel-default">
        <div class="panel-heading">Clientes</div>
        <div class="panel-body">
          <table id="clientsTable" class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Total Comprado</th>
                <th>Última Compra</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr><td>Cliente A</td><td>50000</td><td>01/07/2025</td><td>Ativo</td></tr>
              <tr><td>Cliente B</td><td>30000</td><td>05/07/2025</td><td>Ativo</td></tr>
              <tr><td>Cliente C</td><td>20000</td><td>08/07/2025</td><td>Inativo</td></tr>
              <tr><td>Cliente D</td><td>15000</td><td>10/07/2025</td><td>Ativo</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráficos -->
  <div class="row" style="margin-top:30px;">
    <div class="col-sm-4">
      <div class="panel panel-default">
        <div class="panel-heading">Vendas por Estado</div>
        <div class="panel-body">
          <canvas id="salesByState" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="panel panel-default">
        <div class="panel-heading">Vendas Mensais</div>
        <div class="panel-body">
          <canvas id="monthlySales" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="panel panel-default">
        <div class="panel-heading">Top Produtos</div>
        <div class="panel-body">
          <canvas id="topProducts" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Scripts -->
<script>
  // Slider
  var range = document.getElementById('rangeDays');
  var rangeValue = document.getElementById('rangeValue');
  range.addEventListener('input', function(){
    rangeValue.innerHTML = range.value + " dias";
    // Aqui você aplicaria filtros reais
  });

  // DataTables
  $(document).ready(function () {
    $('#stateTable').DataTable();
    $('#clientsTable').DataTable();
  });

  // Gráficos
  new Chart(document.getElementById('salesByState'), {
    type: 'pie',
    data: {
      labels: ['SP', 'RJ', 'MG', 'RS'],
      datasets: [{
        data: [200000, 150000, 100000, 50000],
        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
      }]
    }
  });

  new Chart(document.getElementById('monthlySales'), {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
      datasets: [{
        label: 'Vendas',
        data: [50000, 70000, 60000, 80000, 90000, 75000, 95000],
        fill: true,
        backgroundColor: 'rgba(78, 115, 223, 0.05)',
        borderColor: '#4e73df',
        tension: 0.4
      }]
    }
  });

  new Chart(document.getElementById('topProducts'), {
    type: 'bar',
    data: {
      labels: ['Produto A', 'Produto B', 'Produto C'],
      datasets: [{
        label: 'Vendas',
        data: [120000, 90000, 60000],
        backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e']
      }]
    }
  });
</script>

<!-- Bootstrap JS -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

</body>
</html>
