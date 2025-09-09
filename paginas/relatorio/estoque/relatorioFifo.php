<?php $filial = $GLOBALS['FILIAL_USUARIO'] ?? '100'; // fallback para 100 ?>
       <style>
        /* Estilo para ocultar elementos não imprimíveis */
        @media print {
            .no-print {
                display: none;
            }
            .panel-info {
                page-break-before: always;
            }
        }
        .panel-info {
            margin-top: 20px;
            border: 1px solid #d9edf7;
            padding: 15px;
            background-color: #f5f5f5;
        }
    </style>
	<div class="container">
        <!-- Botão Imprimir -->
        <div class="text-right no-print" style="margin-top: 20px;">
            <button onclick="window.print();" class="btn btn-success btn-lg">
                <span class="glyphicon glyphicon-print"></span> Imprimir Tudo
            </button>
        </div>

        <!-- Formulário de Filtros (não será impresso) -->
        <div class="panel panel-primary no-print" style="margin-top: 20px;">
            <div class="panel-heading text-center">
                <h4>Filtros de Estoque</h4>
            </div>
            <div class="panel-body">
                <form method="POST" action="" class="form-horizontal">
                    <div class="form-group">
                        <label for="tipoProduto" class="col-sm-3 control-label">Tipo de Produto</label>
                        <div class="col-sm-6">
                            <select id="tipoProduto" name="tipoProduto" class="form-control">
                                <option value="">TODOS</option>
                                <option value="C/OSSO">C/OSSO</option>
                                <option value="CAIXARIA">CAIXARIA</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="condicaoProduto" class="col-sm-3 control-label">Condição do Produto</label>
                        <div class="col-sm-6">
                            <select id="condicaoProduto" name="condicaoProduto" class="form-control">
                                <option value="">TODOS</option>
                                <option value="R">RESFRIADO</option>
                                <option value="C">CONGELADO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="localEstoque" class="col-sm-3 control-label">Local de Estoque</label>
                        <div class="col-sm-6">
                            <select id="localEstoque" name="localEstoque" class="form-control">
                                <option value="">TODOS</option>
                                <?php
                                // Recuperar os locais de estoque do banco de dados
                                $stmtLocais = $pdoS->query("SELECT DISTINCT Desc_local FROM tbLocalEstoque WHERE Cod_filial = {$filial} ORDER BY Desc_local");
                                while ($local = $stmtLocais->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($local['Desc_local']) . '">' . htmlspecialchars($local['Desc_local']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="diasRestantes" class="col-sm-3 control-label">Dias Restantes (até)</label>
                        <div class="col-sm-6">
                            <input type="number" id="diasRestantes" name="diasRestantes" class="form-control" placeholder="Digite o número de dias" min="0">
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span class="glyphicon glyphicon-search"></span> Gerar Relatório
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela de Resultados -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <?php
        // Processar os filtros
        $tipoProduto = $_POST['tipoProduto'] ?? '';
        $condicaoProduto = $_POST['condicaoProduto'] ?? '';
        $localEstoque = $_POST['localEstoque'] ?? '';
        $diasRestantes = $_POST['diasRestantes'] ?? '';
        // Montar a consulta SQL dinamicamente
        $sql = "
        SELECT 
            A.Cod_produto, 
            B.Desc_produto_est, 
            A.Data_producao, 
            C.Tipo_temperatura,
            MAX(A.Data_validade) AS Data_validade,
            D.Desc_local AS LOCAL,
            COUNT(*) AS Quantidade,
			FORMAT(SUM(A.Peso_liquido), 'N2', 'pt-BR') AS LIQUIDO,
            DATEDIFF(DAY, CAST(GETDATE() AS DATE), MAX(A.Data_validade)) AS Dias_restantes
        FROM tbVolume A
        INNER JOIN tbProduto B ON A.Cod_produto = B.Cod_produto
        INNER JOIN tbProdutoRef C ON B.Cod_Produto = C.Cod_Produto
        INNER JOIN tbLocalEstoque D ON A.Cod_local_estoque = D.Cod_local AND D.Cod_filial = {$filial}
        WHERE A.Status = 'E' AND A.Cod_filial_estoque = {$filial}";

        if ($tipoProduto === 'CAIXARIA') {
            $sql .= " AND A.Cod_produto BETWEEN 30000 AND 39999";
        } elseif ($tipoProduto === 'C/OSSO') {
            $sql .= " AND A.Cod_produto BETWEEN 20000 AND 29999";
        }

        if ($condicaoProduto) {
            $sql .= " AND C.Tipo_temperatura = :condicaoProduto";
        }

        if ($localEstoque) {
            $sql .= " AND D.Desc_local = :localEstoque";
        }

        $sql .= " GROUP BY A.Cod_produto, B.Desc_produto_est, A.Data_producao, C.Tipo_temperatura, D.Desc_local";

        if ($diasRestantes !== '') {
            $sql .= " HAVING DATEDIFF(DAY, CAST(GETDATE() AS DATE), MAX(A.Data_validade)) <= :diasRestantes";
        }

        $sql .= " ORDER BY LOCAL, Dias_restantes ASC";

        $stmt = $pdoS->prepare($sql);

        if ($condicaoProduto) {
            $stmt->bindParam(':condicaoProduto', $condicaoProduto);
        }
        if ($localEstoque) {
            $stmt->bindParam(':localEstoque', $localEstoque);
        }
        if ($diasRestantes !== '') {
            $stmt->bindParam(':diasRestantes', $diasRestantes, PDO::PARAM_INT);
        }

        $stmt->execute();
        $dadosPorLocal = [];

        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dadosPorLocal[$r['LOCAL']][] = $r;
        }
        ?>

        <div>
            <?php foreach ($dadosPorLocal as $local => $produtos): ?>
            <div class="panel panel-info">
                <div class="panel-heading text-center">
                    <h5><strong>Local de Estoque: <?= htmlspecialchars($local); ?></strong></h5>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Produto</th>
                                    <th>Data Inicio</th>
                                    <th>Data Final</th>
                                    <th>Quantidade</th>
									<th>Peso</th>
                                    <th>Dias</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalLocal = 0;
                                foreach ($produtos as $produto): 
                                    $totalLocal += $produto['Quantidade']; ?>
                                <tr>
                                    <td><?= htmlspecialchars($produto['Cod_produto']); ?></td>
                                    <td><?= htmlspecialchars($produto['Desc_produto_est']); ?></td>
                                    <td><?= htmlspecialchars((new DateTime($produto['Data_producao']))->format('d/m/Y')); ?></td>
                                    <td><?= htmlspecialchars((new DateTime($produto['Data_validade']))->format('d/m/Y')); ?></td>
                                    <td><?= htmlspecialchars($produto['Quantidade']); ?></td>
									<td><?= htmlspecialchars($produto['LIQUIDO']); ?></td>
                                    <td><?= htmlspecialchars($produto['Dias_restantes']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Total de Caixas no Local:</strong></td>
                                    <td colspan="2"><strong><?= $totalLocal; ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
