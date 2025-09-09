<?php
$sql = "SELECT * FROM noticias ORDER BY data DESC";
$stmt = $pdoM->query($sql);

$primeira = true; // flag para detectar a primeira notícia

while ($noticia = $stmt->fetch(PDO::FETCH_ASSOC)):
?>
  <div class="panel panel-default" style="<?= $primeira ? 'margin-top: 55px;' : '' ?>">
    <div class="panel-heading" style="background-color: #f5f5f5; padding: 15px 20px;">
      <div class="row">
        <div class="col-xs-8">
          <h4 style="margin: 0; font-weight: bold;"><?= htmlspecialchars($noticia['titulo']) ?></h4>
        </div>
        <div class="col-xs-4 text-right">
          <small style="color: #777;"><?= date('d/m/Y', strtotime($noticia['data'])) ?></small>
        </div>
      </div>
    </div>
    <div class="panel-body" style="padding: 20px; font-size: 14px; color: #333;">
      <p><p><?= nl2br($noticia['conteudo']) ?></p>
</p>
    </div>
    <div class="panel-footer" style="background-color: #f9f9f9; padding: 10px 20px;">
      <div class="row">
        <div class="col-xs-6">
          <small><strong>Autor:</strong> <?= $noticia['autor'] == 1 ? 'Victor' : 'Desconhecido' ?></small>
        </div>
      </div>
    </div>
  </div>
<?php 
$primeira = false; // desativa após a primeira
endwhile; 
?>
