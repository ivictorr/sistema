<?php $filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; // fallback para 100 ?>
<div class="container">
    <!-- Formulário de Filtros -->
    <div class="panel panel-primary" style="margin-top: 50px">
        <div class="panel-heading text-center">
            <h4>CONFERENCIA DE PEÇAS RECEBIDAS</h4>
        </div>
        <div class="panel-body">
            <form method="POST" action="" class="form-horizontal">
                <div class="form-group">
                    <label for="codigoRomJatai" class="col-sm-3 control-label">Romaneio Jataí</label>
                    <div class="col-sm-6">
                        <input type="text" id="codigoRomJatai" name="pedEntrada" class="form-control" placeholder="Romaneio Jataí">
                    </div>
                </div>
                <div class="form-group">
                    <label for="codigoPedXinguara" class="col-sm-3 control-label">Romaneio Xinguara</label>
                    <div class="col-sm-6">
                        <input type="text" id="codigoPedXinguara" name="pedSaida" class="form-control" placeholder="Romaneio Xinguara">
                    </div>
                </div>
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary btn-lg">Gerar Relatório</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
<?php
// Processar os filtros
$pedSaida = $_POST['pedSaida'] ?? '';
$pedEntrada = $_POST['pedEntrada'] ?? '';

// Montar a consulta SQL dinamicamente
$sql = "
WITH VolumesExclusivos AS (
    SELECT 
        C.Cod_filial_volume,
        C.Serie_volume,
        C.Num_volume
    FROM 
        tbSaidas A
    INNER JOIN tbSaidasItemRom C ON A.Chave_fato = C.Chave_fato
    WHERE A.Cod_docto = 'RTS' AND A.Num_docto = ?
    
    EXCEPT
    
    SELECT 
        C.Cod_filial_volume,
        C.Serie_volume,
        C.Num_volume
    FROM 
        SERVIDOR.SATKVALENCIO_JATAI.DBO.tbEntradas A
    INNER JOIN SERVIDOR.SATKVALENCIO_JATAI.DBO.tbEntradasItemRom C ON A.Chave_fato = C.Chave_fato
    WHERE A.Cod_docto = 'RTE' AND A.Num_docto = ?
)

SELECT 
    B.Cod_produto as Produto,
    D.Desc_produto_est as Nome_produto,
    C.Cod_filial_volume as filial,
    C.Serie_volume as serie,
    C.Num_volume as volume,
	C.Qtde_pri as peso
FROM 
    tbSaidas A
INNER JOIN tbSaidasItemRom C ON A.Chave_fato = C.Chave_fato
INNER JOIN tbSaidasItem B ON A.Chave_fato = B.Chave_fato AND B.Num_item = C.Num_item
INNER JOIN tbProduto D ON B.Cod_produto = D.Cod_produto
INNER JOIN VolumesExclusivos V ON 
    V.Cod_filial_volume = C.Cod_filial_volume AND 
    V.Serie_volume = C.Serie_volume AND 
    V.Num_volume = C.Num_volume
WHERE A.Cod_docto = 'RTS' AND A.Num_docto = ?
";
$stmt = $pdoS->prepare($sql);
$stmt->execute([$pedSaida, $pedEntrada, $pedSaida]);

?>

            <div class="panel panel-success" style="margin-top: 20px;">
                <div class="panel-heading text-center">
                    <h4>Resultados do Relatório</h4>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="estoque" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produto</th>
                                    <th>Nome Produto</th>
                                    <th>Código de Barras</th>
                                    <th>Peso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while($r = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $r['Produto'] ?></td>
                                    <td><?= $r['Nome_produto'] ?></td>
                                    <td><?= htmlspecialchars($r['filial']); ?><?= htmlspecialchars($r['serie']); ?><?= htmlspecialchars($r['volume']); ?></td>
                                    <td><?= $r['peso'] ?></td>
                                  </tr>
                                <?php $i++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            $('#estoque').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json'
                },
                pageLength: 10,
                lengthChange: true,
                searching: true
            });
        });
    </script>