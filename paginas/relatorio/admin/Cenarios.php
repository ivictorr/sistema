<?php
// Buscar valores atuais no banco
try {
    $sql = "SELECT filial, producao_cab, contrib_miudos FROM producao WHERE filial IN ('Xinguara','Altamira')";
    $stmt = $pdoM->query($sql);
    $valX_producao = $valX_miudos = $valA_producao = $valA_miudos = "";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['filial'] === 'Xinguara') {
            $valX_producao = $row['producao_cab'];
            $valX_miudos = $row['contrib_miudos'];
        } elseif ($row['filial'] === 'Altamira') {
            $valA_producao = $row['producao_cab'];
            $valA_miudos = $row['contrib_miudos'];
        }
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger text-center'>Erro ao buscar valores: " . $e->getMessage() . "</div>";
}

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producaoCabXinguara = trim($_POST['producao_cab_xinguara']);
    $contribMiudosXinguara = trim($_POST['contrib_miudos_xinguara']);
    $producaoCabAltamira = trim($_POST['producao_cab_altamira']);
    $contribMiudosAltamira = trim($_POST['contrib_miudos_altamira']);

    try {
        // Atualizar valores para Xinguara
        $sqlXinguara = "UPDATE producao 
                        SET producao_cab = :producao_cab, contrib_miudos = :contrib_miudos 
                        WHERE filial = 'Xinguara'";
        $stmtX = $pdoM->prepare($sqlXinguara);
        $stmtX->bindParam(':producao_cab', $producaoCabXinguara, PDO::PARAM_STR);
        $stmtX->bindParam(':contrib_miudos', $contribMiudosXinguara, PDO::PARAM_STR);
        $stmtX->execute();

        // Atualizar valores para Altamira
        $sqlAltamira = "UPDATE producao 
                        SET producao_cab = :producao_cab, contrib_miudos = :contrib_miudos 
                        WHERE filial = 'Altamira'";
        $stmtA = $pdoM->prepare($sqlAltamira);
        $stmtA->bindParam(':producao_cab', $producaoCabAltamira, PDO::PARAM_STR);
        $stmtA->bindParam(':contrib_miudos', $contribMiudosAltamira, PDO::PARAM_STR);
        $stmtA->execute();

        echo "<div class='alert alert-success text-center'>✔ Valores atualizados com sucesso para Xinguara e Altamira!</div>";

        // Atualiza variáveis para manter os inputs preenchidos
        $valX_producao = $producaoCabXinguara;
        $valX_miudos = $contribMiudosXinguara;
        $valA_producao = $producaoCabAltamira;
        $valA_miudos = $contribMiudosAltamira;

    } catch (PDOException $e) {
        echo "<div class='alert alert-danger text-center'>Erro: " . $e->getMessage() . "</div>";
    }
}
?>
<br><br>
<div class="container" style="margin-top:30px; max-width: 800px;">
  <div class="panel panel-default">
    <div class="panel-heading text-center" style="font-size:16px; font-weight:bold;">
      Atualizar Produção
    </div>
    <div class="panel-body">
      <div class="alert alert-warning small text-center" style="margin-bottom:20px;">
        <strong>Aviso:</strong> Após alterar os valores, eles serão atualizados para as filiais correspondentes.
      </div>
      <form class="form-horizontal" action="" method="POST">

        <!-- Xinguara -->
        <fieldset>
          <legend style="font-size:14px; font-weight:bold;">Filial Xinguara</legend>
          <div class="form-group">
            <label for="producao_cab_xinguara" class="col-sm-5 control-label">C. Produção por Cab</label>
            <div class="col-sm-7">
              <input type="number" step="0.01" class="form-control input-sm" 
                     id="producao_cab_xinguara" name="producao_cab_xinguara" 
                     value="<?= htmlspecialchars($valX_producao) ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label for="contrib_miudos_xinguara" class="col-sm-5 control-label">Contrib. Miúdos/Subprodutos</label>
            <div class="col-sm-7">
              <input type="number" step="0.01" class="form-control input-sm" 
                     id="contrib_miudos_xinguara" name="contrib_miudos_xinguara" 
                     value="<?= htmlspecialchars($valX_miudos) ?>" required>
            </div>
          </div>
        </fieldset>

        <hr style="margin:15px 0;">

        <!-- Altamira -->
        <fieldset>
          <legend style="font-size:14px; font-weight:bold;">Filial Altamira</legend>
          <div class="form-group">
            <label for="producao_cab_altamira" class="col-sm-5 control-label">C. Produção por Cab</label>
            <div class="col-sm-7">
              <input type="number" step="0.01" class="form-control input-sm" 
                     id="producao_cab_altamira" name="producao_cab_altamira" 
                     value="<?= htmlspecialchars($valA_producao) ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label for="contrib_miudos_altamira" class="col-sm-5 control-label">Contrib. Miúdos/Subprodutos</label>
            <div class="col-sm-7">
              <input type="number" step="0.01" class="form-control input-sm" 
                     id="contrib_miudos_altamira" name="contrib_miudos_altamira" 
                     value="<?= htmlspecialchars($valA_miudos) ?>" required>
            </div>
          </div>
        </fieldset>

        <!-- Botões -->
        <div class="form-group">
          <div class="col-sm-offset-5 col-sm-7 text-right">
            <button type="submit" class="btn btn-success btn-sm">
              <span class="glyphicon glyphicon-floppy-disk"></span> Alterar
            </button>
            <button type="reset" class="btn btn-default btn-sm">
              <span class="glyphicon glyphicon-refresh"></span> Limpar
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
