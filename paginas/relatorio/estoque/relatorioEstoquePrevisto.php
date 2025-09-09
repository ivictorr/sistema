<?php
// Inicie a sessão se ainda não estiver iniciada (necessário para $_SESSION)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Conexões PDO assumidas como existentes: $pdoM (MySQL), $pdoS (MSSQL)
if (!isset($pdoM) || !isset($pdoS)) {
    die('Conexões não disponíveis. Inclua primeiro o script de conexão ($pdoM e $pdoS).');
}

// Caminho para salvar os filtros do usuário
$userId = $_SESSION['user_id'] ?? 'default';
$filterFile = __DIR__ . "/filters/user_{$userId}_estoque_v2.txt";

// Funções para salvar, carregar e limpar filtros
function saveFilters($filters, $filePath)
{
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    file_put_contents($filePath, json_encode($filters));
}

function loadFilters($filePath)
{
    if (file_exists($filePath)) {
        return json_decode(file_get_contents($filePath), true);
    }
    return [];
}

function clearFilters($filePath)
{
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Carregar os filtros salvos
$savedFilters = loadFilters($filterFile);

// Lógica de manipulação de filtros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentFilters = [
        'condicao' => $_POST['condicaoProduto'] ?? '',
        'localestoque' => $_POST['localEstoque'] ?? '',
        'linhaGrupo' => $_POST['linhaGrupo'] ?? '',
    ];
    $savedFilters = $currentFilters;

    if (isset($_POST['salvarFiltro'])) {
        saveFilters($currentFilters, $filterFile);
    }

    if (isset($_POST['limparFiltro'])) {
        clearFilters($filterFile);
        $savedFilters = [];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$filial = $_SESSION['FILIAL_USUARIO'] ?? '100';

// Definição dos locais de estoque permitidos por filial
$locaisPermitidosPorFilial = [
    '100' => ['01'],
    '200' => ['01', '04'],
    '400' => ['01'],
];
$locaisPermitidos = $locaisPermitidosPorFilial[$filial] ?? [];

?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/3.4.1/css/bootstrap.min.css">
<style>
    /* Estilos permanecem os mesmos */
    .table-container { padding: 20px; }
    .table { margin-top: 20px; background-color: #fff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
    .table thead { background-color: #007bff; color: white; }
    .table tbody tr:nth-child(even) { background-color: #f2f2f2; }
    .table tbody tr:hover { background-color: #e9ecef; }
    .text-right-align { text-align: right !important; }
    /* NOVO: Estilo para indicar que o cabeçalho do grupo é clicável */
    .group-header { cursor: pointer; }

    /* Estilos ATUALIZADOS para o cabeçalho do grupo */
    .group-header > td {
        background-color: #e0f2f7 !important; /* Um azul claro muito suave */
        color: #212529 !important; /* Cor de texto mais escura para contraste */
        font-weight: bold;
        padding: 10px 8px;
        font-size: 1.1em;
        text-align: left;
        border-bottom: 1px solid #cceeff; /* Uma linha sutil para separar */
        border-top: 1px solid #cceeff; /* Uma linha sutil para separar */
    }

    /* Ajuste para o ícone */
    .group-header .glyphicon {
        margin-right: 8px;
        font-size: 0.9em;
        vertical-align: middle;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<div class="container mt-5">
    <br>
    <div class="panel panel-primary no-print mt-5" style="margin-top: 30px">
        <div class="panel-heading text-center">
            <h3>RELATORIO DE ESTOQUE x RESERVADO</h3>
        </div>
        <br><br>
        <form method="POST" action="" class="form-horizontal">
            
            <div class="form-group row">
                <label for="localEstoque" class="col-sm-2 col-form-label text-right">Local de Estoque:</label>
                <div class="col-sm-10">
                    <select id="localEstoque" name="localEstoque" class="form-control">
                        <?php
                        $stmtLocais = $pdoS->prepare("SELECT DISTINCT Desc_local, Cod_local FROM tbLocalEstoque WHERE Cod_filial = ? ORDER BY Desc_local");
                        $stmtLocais->execute([$filial]);
                        while ($local = $stmtLocais->fetch(PDO::FETCH_ASSOC)) {
                            if (in_array($local['Cod_local'], $locaisPermitidos)) {
                                $selected = ($savedFilters['localestoque'] ?? '') === $local['Cod_local'] ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($local['Cod_local'], ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($local['Desc_local'], ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="condicaoProduto" class="col-sm-2 col-form-label text-right">Condição do Produto:</label>
                <div class="col-sm-10">
                    <select id="condicaoProduto" name="condicaoProduto" class="form-control">
                        <option value="">TODOS</option>
                        <option value="RESFRIADO" <?= ($savedFilters['condicao'] ?? '') === 'RESFRIADO' ? 'selected' : '' ?>>RESFRIADO</option>
                        <option value="CONGELADO" <?= ($savedFilters['condicao'] ?? '') === 'CONGELADO' ? 'selected' : '' ?>>CONGELADO</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="linhaGrupo" class="col-sm-2 col-form-label text-right">Linha de Grupo:</label>
                <div class="col-sm-10">
                    <select id="linhaGrupo" name="linhaGrupo" class="form-control">
                        <option value="" <?= ($savedFilters['linhaGrupo'] ?? '') === '' ? 'selected' : '' ?>>TODOS</option>
                        <option value="TRASEIRO" <?= ($savedFilters['linhaGrupo'] ?? '') === 'TRASEIRO' ? 'selected' : '' ?>>PRODUÇÃO TRASEIRO</option>
                        <option value="DIANTEIRO" <?= ($savedFilters['linhaGrupo'] ?? '') === 'DIANTEIRO' ? 'selected' : '' ?>>PRODUÇÃO DIANTEIRO</option>
                        <option value="MIUDOS" <?= ($savedFilters['linhaGrupo'] ?? '') === 'MIUDOS' ? 'selected' : '' ?>>PRODUÇÃO MIUDOS</option>
                        <option value="OUTROS" <?= ($savedFilters['linhaGrupo'] ?? '') === 'OUTROS' ? 'selected' : '' ?>>OUTROS</option>
                    </select>
                </div>
            </div>

            <div class="form-group text-center">
                <button type="submit" name="gerarRelatorio" class="btn btn-primary">Gerar Relatório</button>
                <button type="submit" name="salvarFiltro" class="btn btn-secondary">Salvar Filtro</button>
                <button type="submit" name="limparFiltro" class="btn btn-danger">Limpar Filtro</button>
            </div>
        </form>
    </div>

    <?php if (isset($_POST['gerarRelatorio'])) :

        $condicaoProduto = $_POST['condicaoProduto'] ?? '';
        $localEstoque = $_POST['localEstoque'] ?? '';
        $linhaGrupo = $_POST['linhaGrupo'] ?? '';

        try {
            // --- PASSO 1: Obter dados da última simulação (MySQL) ---
            $snapshotData = [];
            $stmt_snapshot = $pdoM->prepare("SELECT snapshot_id FROM simulacao_desossa WHERE filial = :filial ORDER BY data_geracao DESC LIMIT 1");
            $stmt_snapshot->execute([':filial' => $filial]);
            $latestSnapshotId = $stmt_snapshot->fetchColumn();

            if ($latestSnapshotId) {
                $stmt_data = $pdoM->prepare("SELECT * FROM simulacao_desossa WHERE snapshot_id = :snapshot_id");
                $stmt_data->execute([':snapshot_id' => $latestSnapshotId]);
                $snapshotData = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
            }

            // --- PASSO 2: Obter dados de reserva (MSSQL) ---
            $reservadoMap = [];
            $sqlReservado = "SELECT TBI.Cod_produto, SUM(TBI.Qtde_pri) AS PESO_RESERV
                             FROM tbSaidas PVE
                             LEFT JOIN tbSaidas ROS ON ROS.Chave_fato_orig_un = PVE.Chave_fato AND ROS.COD_DOCTO IN ('ROS','RTS','RAE') AND ROS.Cod_filial = PVE.Cod_filial
                             INNER JOIN tbSaidasItem TBI ON TBI.Chave_fato = PVE.Chave_fato
                             WHERE PVE.COD_DOCTO IN ('PVE','PAE')
                               AND PVE.Cod_filial = ?
                               AND PVE.Status <> 'C'
                               AND TBI.Cod_local = ?
                               AND PVE.Cod_tipo_mv IN ('T500', 'T700', 'E500','M500','M501','M502','M503','T800')
                               AND PVE.Data_v2 BETWEEN DATEADD(DAY, -15, GETDATE()) AND DATEADD(YEAR, 2, GETDATE())
                               AND NOT EXISTS (SELECT 1 FROM tbSaidasItem TBI_PTC INNER JOIN tbSaidas PTC ON PTC.Chave_fato = TBI_PTC.Chave_fato AND PTC.COD_DOCTO = 'PTC' AND PTC.Cod_filial = PVE.Cod_filial WHERE TBI_PTC.Chave_fato_orig = ROS.Chave_fato)
                               AND NOT EXISTS (SELECT 1 FROM tbSaidasItem TBI_PTO INNER JOIN tbSaidas PTO ON PTO.Chave_fato = TBI_PTO.Chave_fato AND PTO.COD_DOCTO = 'PTO' AND PTO.Cod_filial = PVE.Cod_filial WHERE TBI_PTO.Chave_fato_orig = ROS.Chave_fato)
                               AND NOT EXISTS (SELECT 1 FROM tbSaidas NE_ROS WHERE NE_ROS.Chave_fato_orig_un = ROS.Chave_fato AND NE_ROS.COD_DOCTO IN ('NE', 'NEE') AND NE_ROS.Cod_filial = PVE.Cod_filial)
                               AND NOT EXISTS (SELECT 1 FROM tbSaidas PAV WHERE PAV.Chave_fato_orig_un = PVE.Chave_fato AND PAV.COD_DOCTO = 'PAV' AND PAV.Cod_filial = PVE.Cod_filial)
                               AND NOT EXISTS (SELECT 1 FROM tbSaidas NE_PVE WHERE NE_PVE.Chave_fato_orig_un = PVE.Chave_fato AND NE_PVE.COD_DOCTO IN ('NE', 'NEE') AND NE_PVE.Cod_filial = PVE.Cod_filial)
                             GROUP BY TBI.Cod_produto";
            
            $stmtReservado = $pdoS->prepare($sqlReservado);
            $stmtReservado->execute([$filial, $localEstoque]);
            while ($res = $stmtReservado->fetch(PDO::FETCH_ASSOC)) {
                $reservadoMap[$res['Cod_produto']] = (float)$res['PESO_RESERV'];
            }

            // --- PASSO 3: Unir dados e aplicar filtros em PHP ---
            $productsData = [];
            foreach ($snapshotData as $row) {
                if (!empty($linhaGrupo) && $row['tipo_corte'] !== $linhaGrupo) { continue; }
                if (!empty($condicaoProduto) && stripos($row['desc_produto_est'], $condicaoProduto) === false) { continue; }

                $produtoCod = $row['cod_produto'];
                $reservado  = $reservadoMap[$produtoCod] ?? 0.0;
                $estoque    = (float)$row['estoque_congelado'];
                $previsto   = (float)$row['proj_qtde'];

                $productsData[] = [
                    'PRODUTO'         => $produtoCod,
                    'DESCRICAO'       => $row['desc_produto_est'],
                    'NOME_GRUPO_REND' => $row['nome_grupo_rend'],
                    'PESO_BRUTO'      => $estoque,
                    'PREVISTO'        => $previsto,
                    'RESERVADO'       => $reservado,
                    'DISPONIVEL'      => $estoque + $previsto - $reservado
                ];
            }

            // --- PASSO 4: Agrupar para exibição ---
            $groupedData = [];
            foreach ($productsData as $row) {
                $groupName = $row['NOME_GRUPO_REND'];
                $groupedData[$groupName][] = $row;
            }
            ksort($groupedData);
    ?>
            <table id="tabela" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Produto</th>
                        <th class="text-right-align">Estoque Congelado</th>
                        <th class="text-right-align">Previsto</th>
                        <th class="text-right-align">Reservado</th>
                        <th class="text-right-align">Disponível</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalGeralPesoBruto = 0;
                    $totalGeralReservado = 0;
                    $totalGeralDisponivel = 0;
                    $totalGeralPrevisto = 0;

                    foreach ($groupedData as $groupName => $products) :
                        $totalGrupoPesoBruto = array_sum(array_column($products, 'PESO_BRUTO'));
                        $totalGrupoReservado = array_sum(array_column($products, 'RESERVADO'));
                        $totalGrupoDisponivel = array_sum(array_column($products, 'DISPONIVEL'));
                        $totalGrupoPrevisto = array_sum(array_column($products, 'PREVISTO'));

                        $totalGeralPesoBruto += $totalGrupoPesoBruto;
                        $totalGeralReservado += $totalGrupoReservado;
                        $totalGeralDisponivel += $totalGrupoDisponivel;
                        $totalGeralPrevisto += $totalGrupoPrevisto;
                        
                        $groupId = 'group-' . preg_replace('/[^a-zA-Z0-9]/', '-', $groupName);

                        // --- Cabeçalho do Grupo com Totais e Ícone ---
                        echo "<tr class='group-header' data-group-toggle='{$groupId}'>";
                        echo "<td colspan='2'> <span class='glyphicon glyphicon-plus-sign'></span> " . htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') . "</td>";
                        echo "<td class='text-right-align'><strong>" . number_format($totalGrupoPesoBruto, 3, ',', '.') . "</strong></td>";
                        echo "<td class='text-right-align'><strong>" . number_format($totalGrupoPrevisto, 3, ',', '.') . "</strong></td>";
                        echo "<td class='text-right-align'><strong>" . number_format($totalGrupoReservado, 3, ',', '.') . "</strong></td>";
                        echo "<td class='text-right-align'><strong>" . number_format($totalGrupoDisponivel, 3, ',', '.') . "</strong></td>";
                        echo "</tr>";

                        // --- Linhas de produtos com classes para controle do JQuery ---
                        foreach ($products as $row) :
                            echo "<tr class='group-details {$groupId}'>
                                    <td>" . htmlspecialchars($row['PRODUTO'], ENT_QUOTES, 'UTF-8') . "</td>
                                    <td>" . htmlspecialchars($row['DESCRICAO'], ENT_QUOTES, 'UTF-8') . "</td>
                                    <td class='text-right-align' data-order='{$row['PESO_BRUTO']}'>" . number_format($row['PESO_BRUTO'] ?? 0, 3, ',', '.') . "</td>
                                    <td class='text-right-align' data-order='{$row['PREVISTO']}'>" . number_format($row['PREVISTO'], 3, ',', '.') . "</td>
                                    <td class='text-right-align' data-order='{$row['RESERVADO']}'>" . number_format($row['RESERVADO'] ?? 0, 3, ',', '.') . "</td>
                                    <td class='text-right-align' data-order='{$row['DISPONIVEL']}'>" . number_format($row['DISPONIVEL'] ?? 0, 3, ',', '.') . "</td>
                                  </tr>";
                        endforeach;
                    endforeach;
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Total Geral</strong></td>
                        <td class="text-right-align"><strong><?php echo number_format($totalGeralPesoBruto, 3, ',', '.'); ?></strong></td>
                        <td class="text-right-align"><strong><?php echo number_format($totalGeralPrevisto, 3, ',', '.'); ?></strong></td>
                        <td class="text-right-align"><strong><?php echo number_format($totalGeralReservado, 3, ',', '.'); ?></strong></td>
                        <td class="text-right-align"><strong><?php echo number_format($totalGeralDisponivel, 3, ',', '.'); ?></strong></td>
                    </tr>
                </tfoot>
            </table>

            <script>
                $(document).ready(function() {
                    // Inicia as linhas de produtos escondidas
                    $('tr.group-details').hide();

                    // Adiciona o evento de clique nos cabeçalhos de grupo
                    $('tr.group-header').on('click', function() {
                        var groupId = $(this).data('group-toggle');
                        $('tr.' + groupId).slideToggle('fast'); // Alterna a visibilidade com animação
                        $(this).find('span.glyphicon').toggleClass('glyphicon-plus-sign glyphicon-minus-sign'); // Troca o ícone
                    });

                    // Inicia o DataTable (sem paginação e ordenação via JS, pois o agrupamento já define a ordem)
                    $('#tabela').DataTable({
                        dom: 'Bfrtip',
                        paging: false,
                        ordering: false, // Desabilitado para manter a ordem dos grupos
                        info: false,
                        buttons: [
                            { extend: 'print', text: 'Imprimir', footer: true, exportOptions: { columns: ':visible' } },
                            'csv', 'excel', 'pdf'
                        ],
                        language: {
                            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese-Brasil.json"
                        }
                    });
                });
            </script>

    <?php
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'><strong>Erro ao gerar relatório:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
        }
    endif;
    ?>
</div>