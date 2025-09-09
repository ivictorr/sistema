<?php
try {
    // ---- Parâmetros (ajuste conforme seu formulário/filtros) ----
    $filial   = $GLOBALS['FILIAL_USUARIO'] ?? '100';
    $dataIni  = $_GET['data_ini'] ?? '2025-08-17'; // 'YYYY-MM-DD'

    // ---- SQL CTE (TODOS os produtos; placeholders ÚNICOS para ODBC) ----
    $sql = "
/* Parâmetros via PDO: :filial1..5, :data_ini1..2 */

/* --- Saldos e base do relatório (Volume x Saldo) --- */
WITH SALDOS AS (
    SELECT RTRIM(LTRIM(COD_PRODUTO)) AS COD_PRODUTO,
           MAX(SALDO_PRI) AS SALDO_PRI
    FROM TBPRODUTOSALDO
    WHERE Cod_filial = :filial1 AND
	Cod_local = '01'
    GROUP BY RTRIM(LTRIM(COD_PRODUTO))
),
VOLUME AS (
    SELECT RTRIM(LTRIM(COD_PRODUTO)) AS COD_PRODUTO,
           SUM(PESO_LIQUIDO) AS PESO_VOLUME
    FROM TBVOLUME
    WHERE STATUS = 'E'
      AND COD_FILIAL_ESTOQUE = :filial2
	  AND Cod_local_estoque = '01'
    GROUP BY RTRIM(LTRIM(COD_PRODUTO))
),
BASE_CTE AS (
    SELECT 
        P.COD_PRODUTO,
        MAX(P.DESC_PRODUTO_EST) AS NOMENCLATURA
    FROM TBPRODUTO P
    INNER JOIN TBVOLUME V 
        ON RTRIM(LTRIM(V.COD_PRODUTO)) = RTRIM(LTRIM(P.COD_PRODUTO))
    WHERE P.COD_DIVISAO1 <> '50'
      AND V.COD_FILIAL_ESTOQUE = :filial3
    GROUP BY P.COD_PRODUTO
),

/* --- Volumes bipados (para ENTRADA) --- */
VOLUMES_BIPADOS AS (
    SELECT DISTINCT
        RTRIM(LTRIM(V.COD_PRODUTO)) AS COD_PRODUTO,
        V.COD_FILIAL_ESTOQUE AS COD_FILIAL,
        V.SERIE_VOLUME,
        V.NUM_VOLUME
    FROM TBVOLUME V
    WHERE V.COD_FILIAL_ESTOQUE = :filial4
      AND V.STATUS = 'B'
),

/* ------------------- PENDÊNCIAS DE ENTRADA ------------------- */
PEND_ENTRADA_DETAIL AS (
    SELECT DISTINCT
        vb.COD_PRODUTO,
        ei.CHAVE_FATO,
        e.NUM_DOCTO,
        e.COD_DOCTO,
        e.DATA_MOVTO
    FROM VOLUMES_BIPADOS vb
    INNER JOIN TBENTRADASITEMROM eir
        ON eir.COD_FILIAL_VOLUME = vb.COD_FILIAL
       AND eir.SERIE_VOLUME      = vb.SERIE_VOLUME
       AND eir.NUM_VOLUME        = vb.NUM_VOLUME
    INNER JOIN TBENTRADASITEM ei
        ON ei.CHAVE_FATO  = eir.CHAVE_FATO
       AND RTRIM(LTRIM(ei.COD_PRODUTO)) = RTRIM(LTRIM(vb.COD_PRODUTO))
       AND ei.NUM_SUBITEM = 0
    INNER JOIN TBENTRADAS e
        ON e.Chave_fato_orig_un = ei.CHAVE_FATO
    WHERE e.DATA_MOVTO >= :data_ini1
      AND NOT EXISTS (
            SELECT 1
            FROM TBENTRADAS eok
            WHERE eok.Chave_fato_orig_un = ei.CHAVE_FATO
              AND RTRIM(LTRIM(eok.COD_DOCTO)) IN ('NE','NEE')
        )
),
PEND_ENTRADA_AGG AS (
    SELECT 
        d.COD_PRODUTO,
        COUNT(*) AS QTD_PEND_ENTRADA,
        STUFF((
            SELECT '; ' + CAST(d2.NUM_DOCTO AS varchar(50))
                         + '/' + RTRIM(LTRIM(d2.COD_DOCTO))
                         + ' - ' + CONVERT(varchar(10), d2.DATA_MOVTO, 103)
            FROM PEND_ENTRADA_DETAIL d2
            WHERE RTRIM(LTRIM(d2.COD_PRODUTO)) = RTRIM(LTRIM(d.COD_PRODUTO))
            FOR XML PATH(''), TYPE
        ).value('.', 'nvarchar(max)'),1,2,'') AS DOCS_PEND_ENTRADA
    FROM PEND_ENTRADA_DETAIL d
    GROUP BY d.COD_PRODUTO
),

/* ------------------- PENDÊNCIAS DE SAÍDA ------------------- */
PEND_SAIDA_DETAIL AS (
    SELECT DISTINCT
        RTRIM(LTRIM(si.COD_PRODUTO)) AS COD_PRODUTO,
        si.CHAVE_FATO,
        sROS.NUM_DOCTO,
        sROS.COD_DOCTO,
        sROS.DATA_MOVTO,
        sPTC.CHAVE_FATO AS CHAVE_FATO_PTC
    FROM TBSAIDASITEMROM sir
    INNER JOIN TBSAIDASITEM si
        ON si.CHAVE_FATO = sir.CHAVE_FATO
    INNER JOIN TBSAIDAS sROS
        ON sROS.CHAVE_FATO = si.CHAVE_FATO
    LEFT JOIN TBSAIDASITEM siPTC
        ON siPTC.COD_PRODUTO = si.COD_PRODUTO
       AND siPTC.CHAVE_FATO_ORIG = sROS.CHAVE_FATO
    LEFT JOIN TBSAIDAS sPTC
        ON sPTC.CHAVE_FATO = siPTC.CHAVE_FATO
       AND RTRIM(LTRIM(sPTC.COD_DOCTO)) = 'PTC'
    WHERE sROS.COD_FILIAL = :filial5
      AND RTRIM(LTRIM(sROS.COD_DOCTO)) = 'ROS'
      AND sROS.DATA_MOVTO >= :data_ini2
      AND NOT EXISTS (
            -- Verificação 1: se existir PTC, a NE precisa vir dele
            SELECT 1
            FROM TBSAIDASITEM siNE
            INNER JOIN TBSAIDAS sNE
                ON sNE.CHAVE_FATO = siNE.CHAVE_FATO
               AND RTRIM(LTRIM(sNE.COD_DOCTO)) IN ('NE','NEE')
            WHERE sPTC.CHAVE_FATO IS NOT NULL
              AND siNE.CHAVE_FATO_ORIG = sPTC.CHAVE_FATO
            UNION ALL
            -- Verificação 2: se não existir PTC, a NE deve vir direto do ROS
            SELECT 1
            FROM TBSAIDASITEM siNE
            INNER JOIN TBSAIDAS sNE
                ON sNE.CHAVE_FATO = siNE.CHAVE_FATO
               AND RTRIM(LTRIM(sNE.COD_DOCTO)) IN ('NE','NEE')
            WHERE sPTC.CHAVE_FATO IS NULL
              AND sNE.CHAVE_FATO_ORIG_UN = sROS.CHAVE_FATO
        )
),


PEND_SAIDA_AGG AS (
    SELECT 
        d.COD_PRODUTO,
        COUNT(*) AS QTD_PEND_SAIDA,
        STUFF((
            SELECT '; ' + CAST(d2.NUM_DOCTO AS varchar(50))
                         + '/' + RTRIM(LTRIM(d2.COD_DOCTO))
                         + ' - ' + CONVERT(varchar(10), d2.DATA_MOVTO, 103)
            FROM PEND_SAIDA_DETAIL d2
            WHERE RTRIM(LTRIM(d2.COD_PRODUTO)) = RTRIM(LTRIM(d.COD_PRODUTO))
            FOR XML PATH(''), TYPE
        ).value('.', 'nvarchar(max)'),1,2,'') AS DOCS_PEND_SAIDA
    FROM PEND_SAIDA_DETAIL d
    GROUP BY d.COD_PRODUTO
)

/* -------- SELECT FINAL (resumo por produto + docs pendentes) -------- */
SELECT 
    b.COD_PRODUTO AS CODIGO,
    b.NOMENCLATURA,
    ISNULL(v.PESO_VOLUME, 0) AS PESO_VOLUME,
    ISNULL(s.SALDO_PRI, 0)   AS PESO_SALDO,
    (CASE WHEN ea.QTD_PEND_ENTRADA > 0 THEN 1 ELSE 0 END) AS FLAG_PEND_ENTRADA,
    (CASE WHEN sa.QTD_PEND_SAIDA   > 0 THEN 1 ELSE 0 END) AS FLAG_PEND_SAIDA,
    ISNULL(ea.QTD_PEND_ENTRADA, 0) AS QTD_PEND_ENTRADA,
    ISNULL(sa.QTD_PEND_SAIDA  , 0) AS QTD_PEND_SAIDA,
    ISNULL(ea.DOCS_PEND_ENTRADA, '') AS DOCS_PEND_ENTRADA,
    ISNULL(sa.DOCS_PEND_SAIDA  , '') AS DOCS_PEND_SAIDA
FROM BASE_CTE b
LEFT JOIN SALDOS s
    ON RTRIM(LTRIM(s.COD_PRODUTO)) = RTRIM(LTRIM(b.COD_PRODUTO))
LEFT JOIN VOLUME v
    ON RTRIM(LTRIM(v.COD_PRODUTO)) = RTRIM(LTRIM(b.COD_PRODUTO))
LEFT JOIN PEND_ENTRADA_AGG ea
    ON RTRIM(LTRIM(ea.COD_PRODUTO)) = RTRIM(LTRIM(b.COD_PRODUTO))
LEFT JOIN PEND_SAIDA_AGG sa
    ON RTRIM(LTRIM(sa.COD_PRODUTO)) = RTRIM(LTRIM(b.COD_PRODUTO))
ORDER BY b.COD_PRODUTO;
    ";

    // $pdoS = sua conexão PDO (ODBC Driver 17 / pdo_odbc ou pdo_sqlsrv)
    $stmt = $pdoS->prepare($sql);
    $stmt->execute([
        ':filial1'   => $filial,
        ':filial2'   => $filial,
        ':filial3'   => $filial,
        ':filial4'   => $filial,
        ':data_ini1' => $dataIni,
        ':filial5'   => $filial,
        ':data_ini2' => $dataIni,
    ]);

    echo "<h4>Volume x Saldo (com pendências) — Filial ".htmlspecialchars($filial)."</h4>";
?>
    <style>
        .maior-saldo { background-color:#f8d7da !important; color:#721c24; font-weight:bold; }
        .maior-volume{ background-color:#fff3cd !important; color:#856404; font-weight:bold; }
        .tem-pendencia { outline:2px dashed #dc3545; }
        .badge-flag { display:inline-block; min-width:22px; padding:2px 6px; border-radius:12px; font-size:12px; text-align:center; background:#e9ecef; }
        .badge-ok { background:#d4edda; color:#155724; }
        .badge-pend { background:#f8d7da; color:#721c24; }
        .wrap { white-space:normal !important; max-width:480px; }
    </style>

    <table id="tabelaProdutos" class="table table-striped table-bordered table-sm">
        <thead class="thead-light">
            <tr>
                <th>Código</th>
                <th>Nomenclatura</th>
                <th>Peso Volume</th>
                <th>Peso Saldo</th>
				<th>Dif</th>
				<th>Docs Pend. Entrada</th>
                <th>Docs Pend. Saída</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalVolume = 0.0;
            $totalSaldo  = 0.0;

            $temLinhas = false;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                $temLinhas = true;
				$pv = (float)$row['PESO_VOLUME'];
				$ps = (float)$row['PESO_SALDO'];
				$dif = $pv - $ps;
                $totalVolume += $pv;
                $totalSaldo  += $ps;

                $flagEnt = ((int)$row['FLAG_PEND_ENTRADA'] === 1);
                $flagSai = ((int)$row['FLAG_PEND_SAIDA']   === 1);

                if ($ps > $pv + 0.001)      $classeDiff = "maior-saldo";
                elseif ($pv > $ps + 0.001)  $classeDiff = "maior-volume";
                else                        $classeDiff = "";

                $classePend = ($flagEnt || $flagSai) ? "tem-pendencia" : "";
                $classe     = trim($classeDiff . ' ' . $classePend);
            ?>
            <tr class="<?= $classe ?>">
                <td><?= htmlspecialchars($row['CODIGO']) ?></td>
                <td class="wrap"><?= htmlspecialchars($row['NOMENCLATURA']) ?></td>
                <td data-order="<?= $pv ?>"><?= number_format($pv, 3, ',', '.') ?></td>
                <td data-order="<?= $ps ?>"><?= number_format($ps, 3, ',', '.') ?></td>
				<td data-order="<?= $ps ?>"><?= number_format($dif, 3, ',', '.') ?></td>
				<td class="wrap"><?= htmlspecialchars($row['DOCS_PEND_ENTRADA']) ?></td>
                <td class="wrap"><?= htmlspecialchars($row['DOCS_PEND_SAIDA']) ?></td>
            </tr>
            <?php endwhile; ?>

            <?php if (!$temLinhas): ?>
            <tr>
                <td colspan="10" class="text-center text-muted">Nenhum registro encontrado para os filtros informados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td><strong><?= number_format($totalVolume, 3, ',', '.') ?></strong></td>
                <td><strong><?= number_format($totalSaldo , 3, ',', '.') ?></strong></td>
                <td colspan="6"></td>
            </tr>
        </tfoot>
    </table>

    <script>
        $(document).ready(function() {
            $('#tabelaProdutos').DataTable({
                dom: 'Bfrtip',
                paging: true,
                buttons: ['print','csv','excel','pdf'],
                order: [[4,'desc'], [8,'desc'], [2,'desc']],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' }
            });
        });
    </script>
<?php
} catch (PDOException $e) {
    echo "<p>Erro ao consultar: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
