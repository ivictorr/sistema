<?php
require_once('./configuracao/conexao.php');

// ?? Sessão e Logout
if (!isset($_SESSION['user_id'])) {
    header("Location: paginas/login.php");
    exit();
}

if (isset($_GET['sair'])) {
    session_destroy();
    header('Location: ./');
    exit();
}

// ?? Funções de Permissões
function getUserPermissions($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT modulos, acessos FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function temPermissaoModulo($moduloId, $modulosPermitidos)
{
    return is_array($modulosPermitidos) && in_array($moduloId, $modulosPermitidos);
}

function temPermissaoAcesso($acessoNome, $acessosPermitidos)
{
    return is_array($acessosPermitidos) && in_array($acessoNome, $acessosPermitidos);
}

// ?? Sessão ativa
verificarSessao($_SESSION['user_id']);

// ?? Permissões do usuário
$userId = $_SESSION['user_id'];
$userPermissions = getUserPermissions($pdoM, $userId);

// ?? Garante arrays válidos SEMPRE
$modulosPermitidos = json_decode($userPermissions['modulos'], true);
$acessosPermitidos = json_decode($userPermissions['acessos'], true);

$modulosPermitidos = is_array($modulosPermitidos) ? $modulosPermitidos : [];
$acessosPermitidos = is_array($acessosPermitidos) ? $acessosPermitidos : [];

// ?? Helper de includes
function loadPage($file, $access, $acessosPermitidos)
{
    // ?? Disponibiliza variáveis de conexão dentro dos includes
    global $pdoM, $pdoS;

    if (temPermissaoAcesso($access, $acessosPermitidos) && file_exists($file)) {
        include $file;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <title>Sistema Valencio</title>

  <!-- CSS Principais -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/css/bootstrap-select.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="./vendors/nprogress/nprogress.css">
  <link rel="stylesheet" href="./vendors/bootstrap-daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="./build/css/custom.min.css">
</head>

<body class="nav-md">
  <div class="container body">
    <div class="main_container">

      <!-- ?? Menu Lateral -->
      <?php include('./components/menu.php'); ?>

      <!-- ?? Top Nav -->
      <?php include('./components/topnav.php'); ?>

      <!-- ?? Conteúdo Principal -->
      <div class="right_col" role="main">
        <?php include('./router.php'); ?>
      </div>

      <!-- ?? Rodapé -->
      <footer>
      <div class="pull-right">FRIGORIFICO VALENCIO &copy; <?= date("Y"); ?> By: Victor</div>

        <div class="clearfix"></div>
      </footer>

    </div>
  </div>

  <!-- JS Vendors -->
  <script src="./vendors/jquery/dist/jquery.min.js"></script>
  <script src="./vendors/bootstrap/dist/js/bootstrap.min.js"></script>
  <script src="./vendors/fastclick/lib/fastclick.js"></script>
  <script src="./vendors/nprogress/nprogress.js"></script>
  <script src="./vendors/Chart.js/dist/Chart.min.js"></script>
  <script src="./vendors/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
  <script src="./vendors/Flot/jquery.flot.js"></script>
  <script src="./vendors/Flot/jquery.flot.pie.js"></script>
  <script src="./vendors/Flot/jquery.flot.time.js"></script>
  <script src="./vendors/Flot/jquery.flot.stack.js"></script>
  <script src="./vendors/Flot/jquery.flot.resize.js"></script>
  <script src="./vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
  <script src="./vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
  <script src="./vendors/flot.curvedlines/curvedLines.js"></script>
  <script src="./vendors/DateJS/build/date.js"></script>
  <script src="./vendors/moment/min/moment.min.js"></script>
  <script src="./vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
  <script src="./vendors/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="./vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
  <script src="./vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
  <script src="./vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
  <script src="./vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
  <script src="./vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
  <script src="./vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
  <script src="./vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
  <script src="./vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
  <script src="./vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
  <script src="./vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/js/bootstrap-select.min.js"></script>
  <script src="./build/js/custom.min.js"></script>
</body>

</html>
