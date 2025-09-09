<?php
// ***************************************************************
// CONEXÃO PDO: CERTIFIQUE-SE QUE $pdoS ESTÁ DEFINIDO E ATIVO AQUI.
// ***************************************************************

$filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; 

$cliente_info = null;
$error_message = '';
$submitted_cliente_cod = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultar'])) {
    
    $cod_cliente = $_POST['cod_cliente'] ?? '';
    $submitted_cliente_cod = $cod_cliente;

    if (!empty($filial) && !empty($cod_cliente)) {
        $sql = "
            SELECT TOP 1
                ISNULL(SGL.SALDO_ABERTO, SC.SALDO_ABERTO) AS SALDO_ABERTO,
                ISNULL(GL.LIMITE_CREDITO, C.LIMITE_CREDITO) AS LIMITE_CREDITO,
                R.Razao_social_cli_for AS NOME_CLIENTE,
                RTRIM(LTRIM(UPPER(CG.Cod_situacao))) AS STATUS_CLIENTE
            FROM vwAtak4Net_titulos_a_receber R
            INNER JOIN TBCLIENTE C 
                ON C.COD_CADASTRO = R.COD_CLI_FOR
            INNER JOIN tbCadastroGeral CG
                ON CG.Cod_cadastro = C.COD_CADASTRO
            LEFT JOIN TBGRUPOLIMITE GL 
                ON GL.COD_GRUPO_LIMITE = C.COD_GRUPO_LIMITE

            OUTER APPLY (
                SELECT SUM(
                        CASE SUBSTRING(COD_DOCTO,1,2) 
                            WHEN 'AD' THEN -VALOR_SALDO
                            ELSE VALOR_SALDO
                        END
                    ) AS SALDO_ABERTO
                FROM TBTITULOREC t
                WHERE t.COD_FILIAL = R.COD_FILIAL
                  AND t.COD_CLIENTE = R.COD_CLI_FOR
            ) SC

            OUTER APPLY (
                SELECT SUM(
                        CASE SUBSTRING(t.COD_DOCTO,1,2) 
                            WHEN 'AD' THEN -VALOR_SALDO
                            ELSE VALOR_SALDO
                        END
                    ) AS SALDO_ABERTO
                FROM TBTITULOREC t
                INNER JOIN TBCLIENTE C2 
                    ON C2.COD_CADASTRO = t.COD_CLIENTE
                WHERE t.COD_FILIAL = R.COD_FILIAL
                  AND t.COD_CLIENTE = R.COD_CLI_FOR
                  AND C2.COD_GRUPO_LIMITE = GL.COD_GRUPO_LIMITE
            ) SGL

            WHERE R.COD_FILIAL = :filial
              AND R.COD_CLI_FOR = :cliente
        ";

        try {
            $stmt = $pdoS->prepare($sql);
            $stmt->bindParam(':filial', $filial, PDO::PARAM_STR);
            $stmt->bindParam(':cliente', $cod_cliente, PDO::PARAM_STR);
            $stmt->execute();
            $cliente_info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cliente_info) {
                 $error_message = "Nenhuma informação de crédito encontrada para o cliente selecionado nesta filial.";
            }

        } catch (PDOException $e) {
            $error_message = "Erro ao consultar o banco de dados.";
            error_log("Erro SQL: " . $e->getMessage());
        }
    } else {
        $error_message = "Por favor, selecione o cliente.";
    }
}
?>
<style>
    .credit-summary {
        border-radius: 5px;
        padding: 20px;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        margin-top: 20px;
    }
    .credit-summary h4 {
        margin-top: 0;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .credit-summary .info-row {
        display: flex;
        justify-content: space-between;
        font-size: 16px;
        margin-bottom: 15px;
    }
    .credit-summary .info-row strong {
        color: #337ab7;
    }
    .progress {
        height: 25px;
        margin-top: 10px;
        font-size: 14px;
        font-weight: bold;
    }
</style>

<div class="container mt-5">
    <br>
    <div class="panel panel-primary no-print" style="margin-top: 30px">
        <div class="panel-heading text-center">
            <h3>CONSULTA DE CRÉDITO DE CLIENTES</h3>
        </div>
        <div class="panel-body">
            <form method="POST" action="" class="form-horizontal">
                <div class="form-group">
                    <label for="cliente-select" class="col-sm-2 control-label">Cliente:</label>
                    <div class="col-sm-10">
                        <select id="cliente-select" name="cod_cliente" 
                                class="selectpicker form-control" 
                                data-live-search="true" 
                                title="Selecione um cliente">
                            <option value="">Nenhum cliente selecionado</option>
                            <?php
                                $stmtClientes = $pdoS->query("
                                    SELECT A.Cod_cadastro, 
                                           A.Nome_cadastro, 
                                           RTRIM(LTRIM(UPPER(A.Cod_situacao))) AS Cod_situacao,
                                           A.CPF_CGC
                                    FROM tbCadastroGeral A 
                                    WHERE A.Tipo_cadastro = 'C' 
                                    ORDER BY A.Nome_cadastro
                                ");
                                
                                while ($cliente = $stmtClientes->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = ($cliente['Cod_cadastro'] == $submitted_cliente_cod) ? 'selected' : '';
                                    $status = trim(strtoupper($cliente['Cod_situacao']));
                                    switch ($status) {
                                        case 'A': $status_label = 'Ativo'; break;
                                        case 'B': $status_label = 'Bloqueado'; break;
                                        case 'I': $status_label = 'Inativo'; break;
                                        default:  $status_label = 'Desconhecido'; break;
                                    }
                                    echo '<option value="' . htmlspecialchars($cliente['Cod_cadastro']) . '" ' . $selected . '>'
                                       . htmlspecialchars($cliente['Cod_cadastro'] . ' - ' 
                                                          . $cliente['Nome_cadastro'] 
                                                          . ' - ' . $cliente['CPF_CGC'] 
                                                          . " (" . $status_label . ")")
                                       . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group text-center">
                    <button type="submit" name="consultar" class="btn btn-primary">
                        <span class="glyphicon glyphicon-search"></span> Consultar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultar'])): ?>
        <?php if ($error_message): ?>
            <div class="alert alert-warning text-center">
                <strong>Atenção:</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php elseif ($cliente_info): 
            $limite = floatval($cliente_info['LIMITE_CREDITO']);
            $saldo_devedor = floatval($cliente_info['SALDO_ABERTO']);
            $disponivel = $limite - $saldo_devedor;
            
            $percentual_usado = 0;
            if ($limite > 0) {
                $percentual_usado = ($saldo_devedor / $limite) * 100;
            } elseif ($saldo_devedor > 0) {
                $percentual_usado = 100;
            }
            $percentual_usado = max(0, min(100, $percentual_usado));

            $progress_class = 'progress-bar-success';
            if ($percentual_usado >= 95) {
                $progress_class = 'progress-bar-danger';
            } elseif ($percentual_usado >= 75) {
                $progress_class = 'progress-bar-warning';
            }

            $status_cliente = trim(strtoupper($cliente_info['STATUS_CLIENTE']));
            switch ($status_cliente) {
                case 'A': $status_label = 'Ativo'; $status_color = '#5cb85c'; break;
                case 'B': $status_label = 'Bloqueado'; $status_color = '#d9534f'; break;
                case 'I': $status_label = 'Inativo'; $status_color = '#f0ad4e'; break;
                default:  $status_label = 'Desconhecido'; $status_color = '#999'; break;
            }
        ?>
            <div class="credit-summary">
                <h4>Resumo de Crédito - <?= htmlspecialchars($cliente_info['NOME_CLIENTE']) ?></h4>
                <div class="info-row">
                    <span>Status do Cliente:</span>
                    <strong style="color: <?= $status_color ?>;"><?= $status_label ?></strong>
                </div>
                <div class="info-row">
                    <span><span class="glyphicon glyphicon-thumbs-up"></span> Limite de Crédito:</span>
                    <strong>R$ <?= number_format($limite, 2, ',', '.') ?></strong>
                </div>
                <div class="info-row">
                    <span><span class="glyphicon glyphicon-shopping-cart"></span> Saldo Devedor:</span>
                    <strong>R$ <?= number_format($saldo_devedor, 2, ',', '.') ?></strong>
                </div>
                <hr>
                <div class="info-row" style="font-size: 18px;">
                    <span><span class="glyphicon glyphicon-ok-circle"></span> <strong>Crédito Disponível:</strong></span>
                    <strong style="color: <?= $disponivel < 0 ? '#d9534f' : '#5cb85c' ?>;">R$ <?= number_format($disponivel, 2, ',', '.') ?></strong>
                </div>
                <div class="progress">
                    <div class="progress-bar <?= $progress_class ?>" role="progressbar" 
                         aria-valuenow="<?= $percentual_usado ?>" aria-valuemin="0" aria-valuemax="100" 
                         style="width: <?= $percentual_usado ?>%;">
                        <?= number_format($percentual_usado, 2, ',', '.') ?>% Utilizado
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    if ($.fn.selectpicker) {
        $('.selectpicker').selectpicker({
            liveSearchNormalize: true,
            maxOptions: 10
        });
        $('.selectpicker').selectpicker('refresh');
    }
});
</script>
