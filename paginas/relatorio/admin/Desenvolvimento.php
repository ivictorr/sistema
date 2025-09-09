<?php

// Filtro de status
$filtroStatus = $_GET['status'] ?? 'todos';

$sql = "SELECT * FROM relatorios";
$params = [];
if ($filtroStatus !== 'todos') {
    $sql .= " WHERE status = :status";
    $params[':status'] = $filtroStatus;
}

$stmt = $pdoM->prepare($sql);
$stmt->execute($params);
$relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Status dos Relatórios</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <style>
        body { background-color: #f4f4f4; font-family: Arial, sans-serif; }
        .page-header { background-color: #343a40; color: #fff; padding: 15px 20px; margin: 0 -15px 20px -15px; }
        .filtros { margin: 15px 0; }
        .filtro-btn { margin-right: 10px; font-weight: bold; }
        .linha-finalizado { background-color: #d4edda !important; }
        .linha-atualizacao { background-color: #fff3cd !important; }
        .linha-desenvolvimento { background-color: #f8d7da !important; }
        .status-finalizado { color: #155724; font-weight: bold; }
        .status-atualizacao { color: #856404; font-weight: bold; }
        .status-desenvolvimento { color: #721c24; font-weight: bold; }
        table th, table td { white-space: nowrap; vertical-align: middle !important; }
        .modal-content { border-radius: 10px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="page-header">
        <h2>Status dos Relatórios</h2>
    </div>

    <!-- Botões de filtro -->
    <div class="filtros">
        <a href="?Desenvolvimento&status=todos" class="btn btn-default filtro-btn"><i class="fa fa-list"></i> Todos</a>
        <a href="?Desenvolvimento&status=Finalizado" class="btn btn-success filtro-btn"><i class="fa fa-check-square-o"></i> Finalizados</a>
        <a href="?Desenvolvimento&status=Em atualização" class="btn btn-warning filtro-btn"><i class="fa fa-refresh"></i> Atualizações</a>
        <a href="?Desenvolvimento&status=Em desenvolvimento" class="btn btn-danger filtro-btn"><i class="fa fa-wrench"></i> Desenvolvimento</a>
    </div>

    <!-- Tabela -->
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th>Relatório</th>
                <th>Solicitante</th>
                <th>Status</th>
                <th>Data Início</th>
                <th>Data Finalização</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($relatorios as $rel): ?>
            <?php
                $classeLinha = '';
                if ($rel["status"] == "Finalizado") $classeLinha = 'linha-finalizado';
                elseif ($rel["status"] == "Em atualização") $classeLinha = 'linha-atualizacao';
                else $classeLinha = 'linha-desenvolvimento';
            ?>
            <tr class="<?php echo $classeLinha; ?>" data-toggle="modal" data-target="#modalRelatorio<?php echo $rel['id']; ?>" style="cursor:pointer;">
                <td><?php echo htmlspecialchars($rel['nome']); ?></td>
                <td><?php echo htmlspecialchars($rel['solicitante'] ?? '-'); ?></td>
                <td>
                    <?php if ($rel["status"] == "Finalizado"): ?>
                        <span class="status-finalizado">✅ Finalizado</span>
                    <?php elseif ($rel["status"] == "Em atualização"): ?>
                        <span class="status-atualizacao">🔄 Em atualização</span>
                    <?php else: ?>
                        <span class="status-desenvolvimento">🚧 Em desenvolvimento</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $rel['data_inicio'] ? date("d/m/Y", strtotime($rel['data_inicio'])) : '-'; ?></td>
                <td><?php echo $rel['data_finalizacao'] ? date("d/m/Y", strtotime($rel['data_finalizacao'])) : '-'; ?></td>
            </tr>

            <!-- Modal de Detalhes -->
            <div class="modal fade" id="modalRelatorio<?php echo $rel['id']; ?>" tabindex="-1" role="dialog">
              <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                  <div class="modal-header bg-info">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?php echo htmlspecialchars($rel['nome']); ?></h4>
                  </div>
                  <div class="modal-body">
                    <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($rel['descricao'] ?? '-')); ?></p>
                    <p><strong>Objetivo:</strong> <?php echo nl2br(htmlspecialchars($rel['objetivo'] ?? '-')); ?></p>
                    <p><strong>Pendências:</strong> <?php echo nl2br(htmlspecialchars($rel['pendencias'] ?? '-')); ?></p>
                    <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($rel['solicitante'] ?? '-'); ?></p>
                    <p><strong>Data Início:</strong> <?php echo $rel['data_inicio'] ? date("d/m/Y", strtotime($rel['data_inicio'])) : '-'; ?></p>
                    <p><strong>Data Finalização:</strong> <?php echo $rel['data_finalizacao'] ? date("d/m/Y", strtotime($rel['data_finalizacao'])) : '-'; ?></p>
                  </div>
                  <div class="modal-footer">
                    <a href="<?php echo htmlspecialchars($rel['arquivo']); ?>" class="btn btn-success" target="_blank">
                        <i class="fa fa-file"></i> Abrir Relatório
                    </a>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                  </div>
                </div>
              </div>
            </div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>
