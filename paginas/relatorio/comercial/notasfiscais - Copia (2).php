<?php
// Consulta para obter todos os locais de estoque com a filial
$locaisEstoque = [];
$res = $pdoS->query("SELECT DISTINCT Cod_filial, Cod_local, Desc_local FROM tbLocalEstoque WHERE Estoque_disponivel = 'S' AND Cod_filial IN ('100', '200') AND Cod_local IN ('01','02','03','04','05') ORDER BY Cod_filial, Cod_local ASC");
while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
    $chave = $r['Cod_filial'] . '-' . $r['Cod_local'];
    $locaisEstoque[$chave] = $r['Desc_local'] . ' (Filial ' . $r['Cod_filial'] . ')';
}

// Obter filtros de data
$emissao_de = $_POST['emissao_de'] ?? '';
$emissao_ate = $_POST['emissao_ate'] ?? '';

// Consulta principal
$sql = "SELECT 
            B.Cod_produto, 
            MAX(tbp.Desc_produto_est) AS NOME,
            SUM(B.Qtde_aux) AS CX, 
            SUM(B.Qtde_pri) AS KG,
            SUM(B.Valor_total) AS VALOR,
            MAX(A.Cod_filial) AS COD_FILIAL,
            B.Cod_local,
            A.Cod_filial
        FROM tbSaidas A
        INNER JOIN tbSaidasItem B ON A.CHAVE_FATO = B.CHAVE_FATO AND B.Num_subItem = 0
        INNER JOIN tbProduto tbp ON B.Cod_produto = tbp.Cod_produto
        WHERE 
            A.COD_DOCTO IN ('NE') 
            AND A.COD_TIPO_MV IN ('T520','T186','T524','T527','T529','T570','T571','X520','T544','T535','T723')
            AND B.Cod_produto BETWEEN '20000' AND '39999' 
            AND B.Qtde_pri > 0 
            AND A.Status <> 'C' 
            AND A.Cod_filial IN ('100', '200')
            AND B.Cod_local IN ('01','02','03','04','05','12','13','14')";

if (!empty($emissao_de)) {
    $sql .= " AND A.Data_v1 >= '$emissao_de'";
}
if (!empty($emissao_ate)) {
    $sql .= " AND A.Data_v1 <= '$emissao_ate'";
}

$sql .= " GROUP BY B.Cod_produto, A.Cod_filial, B.Cod_local";

$stmt = $pdoS->prepare($sql);
$stmt->execute();

// Organizar os resultados
$resultados = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $produto = $row['Cod_produto'];
    $local = $row['Cod_filial'] . '-' . $row['Cod_local'];
    
    if (!isset($resultados[$produto])) {
        $resultados[$produto] = [
            'NOME' => $row['NOME'],
            'CX' => 0,
            'KG' => 0,
            'VALOR' => 0,
            'MEDIAS' => array_fill_keys(array_keys($locaisEstoque), 0)
        ];
    }
    
    $resultados[$produto]['CX'] += $row['CX'];
    $resultados[$produto]['KG'] += $row['KG'];
    $resultados[$produto]['VALOR'] += $row['VALOR'];
    $resultados[$produto]['MEDIAS'][$local] = $row['VALOR'] / max($row['KG'], 1); // Evitar divisão por zero
}
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<div class="container mt-5">
    <h3 class="text-center" style="background: #2e7d32; color: white; padding: 10px; border-radius: 8px;">
        RELATÓRIO NOTAS FISCAIS POR PRODUTOS
    </h3>

    <form method="POST" action="" class="well" style="border-radius: 8px; padding: 20px;">
        <div class="form-group row">
            <label class="col-sm-3 col-form-label text-right" style="font-weight: bold;">Data de Emissão:</label>
            <div class="col-sm-4">
                <input type="date" name="emissao_de" class="form-control" value="<?= htmlspecialchars($emissao_de) ?>" style="border: 2px solid #2e7d32; border-radius: 5px;">
            </div>
            <div class="col-sm-4">
                <input type="date" name="emissao_ate" class="form-control" value="<?= htmlspecialchars($emissao_ate) ?>" style="border: 2px solid #2e7d32; border-radius: 5px;">
            </div>
        </div>
        <div class="form-group text-center">
            <button type="submit" name="gerarRelatorio" class="btn btn-success btn-lg" style="background: #2e7d32; border-radius: 5px; border: none; padding: 10px 20px;">
                <i class="glyphicon glyphicon-list-alt"></i> Gerar Relatório
            </button>
        </div>
    </form>

    <?php
    // Criando um array para abreviar os nomes dos locais
    $abreviacoes = [
        'Xinguara' => 'Xinguara',
        'Jatai' => 'Jataí',
        'Brasília' => 'Brasília',
        'Trindade' => 'Trindade',
        'José dos Campos' => 'São Paulo',
		'Avarias de São Jose' => 'Avarias São Paulo'
    ];

    // Função para abreviar os nomes
    function abreviarNomeLocal($desc_local) {
        global $abreviacoes;
        foreach ($abreviacoes as $completo => $abreviado) {
            if (strpos($desc_local, $completo) !== false) {
                return $abreviado;
            }
        }
        return $desc_local; // Retorna o original se não houver correspondência
    }
    ?>

    <?php if (isset($_POST['gerarRelatorio'])): ?>
    <div class="table-responsive">
        <table id="tabela" class="table table-striped table-bordered" style="border-radius: 8px; overflow: hidden;">
            <thead style="background: #2e7d32; color: white; text-align: center;">
                <tr>
                    <th>PRODUTO</th>
                    <th style="width: 40%">NOME</th>
                    <?php foreach ($locaisEstoque as $codLocal => $descLocal): ?>
                        <th>MÉDIA <?= htmlspecialchars(abreviarNomeLocal($descLocal)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $produto => $dados): ?>
                <tr style="text-align: center;">
                    <td style="font-weight: bold;"><?= htmlspecialchars($produto) ?></td>
                    <td><?= htmlspecialchars($dados['NOME']) ?></td>
                    </td>
                    <?php foreach ($locaisEstoque as $codLocal => $descLocal): ?>
                        <td><?= number_format($dados['MEDIAS'][$codLocal], 2, ',', '.') ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>


            <script>
                $(document).ready(function() {
                    $('#tabela').DataTable({
                        dom: 'Bfrtip',
                        paging: false,
                        buttons: [{
                                extend: 'print',
                                text: 'Imprimir',
                                footer: true, // Inclui o rodapé na impressão
                                exportOptions: {
                                    columns: ':not(:last-child)' // Exclui a última coluna (Ações) da impressão
                                },
                                customize: function(win) {
                                    // Adiciona estilo ao rodapé para que ele apareça na impressão
                                    $(win.document.body).find('tfoot').css('display', 'table-footer-group');
                                }
                            },
                            'csv',
                            'excel',
                            'pdf'
                        ],
                        order: [
                            [3, 'desc']
                        ],
                    });
                });
            </script>