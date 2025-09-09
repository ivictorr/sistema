<style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f4f6f9;
        font-size: 14px;
        color: #333;
    }

    .card-custom {
        background-color: #ffffff;
        border-radius: 4px;
        border: 1px solid #ddd;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        padding: 15px;
    }

    .header {
        padding: 15px;
        font-weight: 700;
        color: #fff;
        background-color: #007bff;
        text-align: center;
        font-size: 22px;
        border-radius: 4px;
    }

    .section-title {
        padding: 10px;
        color: #495057;
        font-weight: 500;
        background-color: #e9ecef;
        text-align: center;
        border-radius: 4px;
        font-size: 16px;
        margin-bottom: 15px;
    }

    .data-display {
        border: 2px solid #007bff;
        border-radius: 4px;
        padding: 10px;
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        color: #007bff;
        margin-bottom: 15px;
    }

    .input-disabled {
        background-color: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 5px;
        text-align: center;
        font-weight: bold;
        color: #495057;
        margin-bottom: 10px;
    }

    .table-section {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #ddd;
        overflow-x: auto;
    }

    .table {
        font-size: 12px;
        margin-bottom: 0;
    }

    .info-label {
        font-size: 12px;
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }

    .row-matched {
        background-color: #d4edda;
    }

    .row-unmatched {
        background-color: #f8d7da;
    }

    .row-current {
        background-color: #fff3cd;
    }

    .badge {
        width: 20px;
        height: 20px;
        display: inline-block;
        margin-right: 10px;
    }

    .banda-horizontal {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }
	.bandas-wrapper {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    gap: 10px;
}

.banda-box {
    flex: 1;
    border: 2px solid #007bff;
    border-radius: 4px;
    padding: 10px;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    color: #007bff;
    background-color: #fff;
}

.banda-label {
    font-size: 12px;
    font-weight: 600;
    color: #333;
    display: block;
    margin-bottom: 5px;
}
</style>
<body>
<div class="row mb-3">
    <div class="col-md-4 offset-md-4 text-center">
        <input type="date" id="dataSelecionada" class="form-control" value="<?=date('Y-m-d')?>">
    </div>
</div>
    <div class="container-fluid">
        <?php if (temPermissaoAcesso('pararAbate', $acessosPermitidos)): ?>
        <div class="text-center mb-3">
            <button class="btn btn-danger" data-toggle="modal" data-target="#paradaModal">Parar Abate</button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card-custom">
<h5 class="section-title">Última Carcaça</h5>
<div class="data-display">
    <h5 class="text-uppercase text-primary">Lote Atual</h5>
    <span id="loteAtual" class="text-muted">---</span>
</div>

<div class="bandas-wrapper">
    <div class="banda-box">
        <span class="banda-label">Banda 1</span>
        <span id="banda1">---</span>
    </div>
    <div class="banda-box">
        <span class="banda-label">Banda 2</span>
        <span id="banda2">---</span>
    </div>
</div>
                    <h5 class="section-title">Listagem de Carcaças</h5>
                    <div class="table-section">
                        <table class="table table-bordered table-condensed">
                            <thead>
                                <tr class="text-center">
                                    <th>Seq.</th>
                                    <th>Animal</th>
                                    <th>Banda 1</th>
                                    <th>Banda 2</th>
                                    <th>Dif.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="carcacasTable" class="text-center">
                                <tr><td colspan="6">Nenhuma carcaça encontrada.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card-custom">
                    <h5 class="section-title">Informações do Abate</h5>
                    <div class="row">
                        <div class="col-sm-3"><div class="info-label">Data Abate</div><div id="dataAbate" class="input-disabled">---</div></div>
                        <div class="col-sm-3"><div class="info-label">Início</div><div id="dataInicial" class="input-disabled">---</div></div>
                        <div class="col-sm-3"><div class="info-label">Fim</div><div id="dataFinal" class="input-disabled">---</div></div>
                        <div class="col-sm-3"><div class="info-label">Total</div><div id="tempoTotal" class="input-disabled">---</div></div>
                    </div>

                    <div class="row">
                        <div class="col-sm-3"><div class="info-label">Total Dia</div><div id="totalDia" class="input-disabled">---</div></div>
                        <div class="col-sm-3"><div class="info-label">Abatido</div><div id="totalAbatidos" class="input-disabled">---</div></div>
                        <div class="col-sm-3"><div class="info-label">Macho</div><div id="machoDia" class="input-disabled">---</div></div>
                        <div class="col-sm-3"><div class="info-label">Fêmea</div><div id="femeaDia" class="input-disabled">---</div></div>
                    </div>

                    <div class="row">
                        <div class="col-12"><div class="info-label text-center">Restante</div><div id="abateRestante" class="input-disabled">---</div></div>
                    </div>

                    <h5 class="section-title">Informações do Lote</h5>
                    <div class="row">
                        <div class="col-6"><div class="info-label text-center">Proprietário</div><div id="propietarioAbate" class="input-disabled">---</div></div>
                        <div class="col-6"><div class="info-label text-center">Fazenda</div><div id="fornecedorAbate" class="input-disabled">---</div></div>
                    </div>

                    <div class="table-section mt-3">
                        <table class="table table-bordered table-condensed">
                            <thead class="text-center">
                                <tr>
                                    <th>Macho</th><th>Fêmea</th><th>Abatidos</th><th>Peso</th><th>Média @</th><th>Média KG</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <tr>
                                    <td id="animalMachoTable">---</td>
                                    <td id="animalFemeaTable">---</td>
                                    <td id="animalAbateTable">---</td>
                                    <td id="animalPesoTable">---</td>
                                    <td id="animalKgTable">---</td>
                                    <td id="animalMediaTable">---</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="section-title">Programação de Abate</h5>
                    <div class="table-section">
                        <table class="table table-bordered table-condensed">
                            <thead class="text-center">
                                <tr>
                                    <th>Lote</th><th>Pecuarista</th><th>Fazenda</th><th>Macho</th><th>Fêmea</th>
                                    <th>Total</th><th>Abatido</th><th>Peso</th><th>@</th>
                                </tr>
                            </thead>
                            <tbody id="programacaoAbate" class="text-center">
                                <tr><td colspan="9">Nenhuma programação encontrada.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Parada -->
<div class="modal fade" id="paradaModal" tabindex="-1" role="dialog" aria-labelledby="paradaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paradaModalLabel">Registrar Parada de Abate</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="paradaForm">
                    <div class="form-group">
                        <label for="motivoParadaInput">Motivo da Parada</label>
                        <!-- Input que filtra o datalist -->
                        <input list="motivosParada" id="motivoParadaInput" name="motivoParada" class="form-control" placeholder="Digite ou selecione..." required>
                        <datalist id="motivosParada">
                            <?php 
                            $res = $pdoM->query("SELECT * FROM motivosparada"); 
                            while ($r = $res->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?=$r['id']?>"><?=mb_convert_encoding($r['motivo'], 'UTF-8', 'ISO-8859-1');?></option>
                            <?php endwhile; ?>
                        </datalist>
                    </div>
                    <div class="text-center">
                        <div id="cronometro">00:00:00</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="iniciarParada" class="btn btn-success">Iniciar</button>
                <button type="button" id="pararParada" class="btn btn-warning" disabled>Parar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    let cronometroInterval;
    let segundos = 0;
    let horaInicio = null;

    function formatarTempo(segundos) {
        const horas = Math.floor(segundos / 3600).toString().padStart(2, '0');
        const minutos = Math.floor((segundos % 3600) / 60).toString().padStart(2, '0');
        const segs = (segundos % 60).toString().padStart(2, '0');
        return `${horas}:${minutos}:${segs}`;
    }

    document.getElementById('iniciarParada').addEventListener('click', () => {
        const motivoInput = document.getElementById('motivoParadaInput');
        const motivo = motivoInput.value;

        if (!motivo) {
            alert('Por favor, escolha ou digite um motivo válido.');
            return;
        }

        // Captura a hora atual como hora de início
        const agora = new Date();
        const dataAtual = agora.toISOString().split('T')[0]; // Formato YYYY-MM-DD
        const horaAtual = agora.toTimeString().split(' ')[0]; // Formato HH:MM:SS
        horaInicio = `${dataAtual} ${horaAtual}`; // Formato DATETIME: YYYY-MM-DD HH:MM:SS

        document.getElementById('pararParada').disabled = false;
        document.getElementById('iniciarParada').disabled = true;

        // Iniciar o cronômetro
        cronometroInterval = setInterval(() => {
            segundos++;
            document.getElementById('cronometro').innerText = formatarTempo(segundos);
        }, 1000);
    });

    document.getElementById('pararParada').addEventListener('click', () => {
        clearInterval(cronometroInterval);

        const motivoInput = document.getElementById('motivoParadaInput');
        const motivo = motivoInput.value;

        if (!horaInicio) {
            alert('Erro: Hora de início não foi registrada.');
            return;
        }

        if (!motivo) {
            alert('Selecione ou digite um motivo para a parada antes de finalizar.');
            return;
        }

        // Captura a hora atual como hora de parada
        const agora = new Date();
        const dataAtual = agora.toISOString().split('T')[0]; // Formato YYYY-MM-DD
        const horaAtual = agora.toTimeString().split(' ')[0]; // Formato HH:MM:SS
        const horaParada = `${dataAtual} ${horaAtual}`; // Formato DATETIME: YYYY-MM-DD HH:MM:SS

        // Enviar os dados para o servidor
        $.ajax({
            url: 'registrar_parada.php', // URL do script PHP
            method: 'POST',
            data: {
                data: dataAtual, // Data do dia
                horaInicio: horaInicio, // Hora inicial registrada ao clicar em "Iniciar"
                horaParada: horaParada, // Hora de parada registrada agora
                motivo: motivo,
            },
            success: function(response) {
                alert('Parada registrada com sucesso no banco de dados!');
            },
            error: function(xhr, status, error) {
                console.error('Erro ao registrar parada:', error);
                alert('Erro ao registrar a parada. Tente novamente.');
            }
        });

        // Resetar os valores e o cronômetro
        segundos = 0;
        horaInicio = null;
        document.getElementById('cronometro').innerText = "00:00:00";
        document.getElementById('paradaForm').reset();
        document.getElementById('iniciarParada').disabled = false;
        document.getElementById('pararParada').disabled = true;

        // Fechar o modal
        $('#paradaModal').modal('hide');
    });
</script>


   <script>
    function formatarNumero(valor) {
        valor = parseFloat(valor);
        if (isNaN(valor) || valor === 0) return '0';
        return valor.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
    }

    function formatarNumeroCom2Casas(valor) {
        valor = parseFloat(valor);
        if (isNaN(valor) || valor === 0) return '0.00';
        return valor.toFixed(2);
    }

    function atualizarDados() {
		 const dataSelecionada = document.getElementById('dataSelecionada').value;
    $.getJSON('./consultas/consulta_abate.php', {
        data_inicio: dataSelecionada,
        data_fim: dataSelecionada
    }, function(response) {
        const { lote, carcacas, infoabate, infoabatidos, infoabatidoslote, programacaoabate } = response;

			console.log(response);

            $('#loteAtual').text(lote || '---');

            if (carcacas.length > 0) {
                const ultima = carcacas[carcacas.length - 1];
                $('#banda1').text(formatarNumero(ultima.Peso_carcaca1));
                $('#banda2').text(formatarNumero(ultima.Peso_carcaca2));
            }

            if (infoabatidoslote.length > 0) {
                const d = infoabatidoslote[0];
                $('#animalMachoTable').text(formatarNumero(d.MACHO_LOTE));
                $('#animalFemeaTable').text(formatarNumero(d.FEMEA_LOTE));
                $('#animalAbateTable').text(formatarNumero(d.ABATIDO_LOTE));
                $('#animalPesoTable').text(formatarNumeroCom2Casas(d.PESO_ABATIDO_LOTE));
                $('#animalKgTable').text(formatarNumeroCom2Casas(d.MEDIA_ARROBA_LOTE));
                $('#animalMediaTable').text(formatarNumeroCom2Casas(d.MEDIA_KG_LOTE));
            }

            if (infoabate.length > 0) {
                const d = infoabate[0];
                $('#dataAbate').text(d.DATA_ABATE ? formatDateBr(d.DATA_ABATE) : '---');
                $('#dataInicial').text(d.DATA_INICIAL || '---');
                $('#dataFinal').text(d.DATA_FINAL || '---');
                $('#tempoTotal').text(d.TEMPO_TOTAL || '---');
            }

            if (infoabatidos.length > 0) {
                const d = infoabatidos[0];
                $('#totalDia').text(formatarNumero(d.TOTAL));
                $('#totalAbatidos').text(formatarNumero(d.ABATIDO));
                $('#machoDia').text(formatarNumero(d.MACHO));
                $('#femeaDia').text(formatarNumero(d.FEMEA));
                $('#abateRestante').text(formatarNumero(d.TOTAL - d.ABATIDO));
            }

            if (carcacas.length > 0) {
                $('#propietarioAbate').text(carcacas[0].Nome_cadastro || '---');
                $('#fornecedorAbate').text(carcacas[0].Nome || '---');
            }

            const carcacasHTML = carcacas.map((carcaca, i) => {
                const dif = (parseFloat(carcaca.Peso_carcaca1) || 0) - (parseFloat(carcaca.Peso_carcaca2) || 0);
                const total = (parseFloat(carcaca.Peso_carcaca1) || 0) + (parseFloat(carcaca.Peso_carcaca2) || 0);
                const isRed = (carcaca.Sexo === 'F' && Math.abs(dif) > 5) || (carcaca.Sexo === 'M' && Math.abs(dif) > 10);
                return `<tr ${isRed ? 'style="background:red; color:white;"' : ''}>
                    <td>${i + 1}</td>
                    <td>${carcaca.Sexo || '---'}</td>
                    <td>${formatarNumero(carcaca.Peso_carcaca1)}</td>
                    <td>${formatarNumero(carcaca.Peso_carcaca2)}</td>
                    <td>${dif.toFixed(2)}</td>
                    <td>${total.toFixed(2)}</td>
                </tr>`;
            }).join('');
            $('#carcacasTable').html(carcacasHTML || '<tr><td colspan="6">Nenhuma carcaça encontrada.</td></tr>');

            const totalizadores = {
                QTDE_CB_M_EAS: 0,
                QTDE_CB_F_EAS: 0,
                QTDE_CB_ESCALA: 0,
                QTDE_CB_ABATIDA: 0,
                PESO_ABATIDO: 0
            };

            const programacaoHTML = programacaoabate.map(row => {
                const macho = parseFloat(row.QTDE_CB_M_EAS) || 0;
                const femea = parseFloat(row.QTDE_CB_F_EAS) || 0;
                const escala = parseFloat(row.QTDE_CB_ESCALA) || 0;
                const abatido = parseFloat(row.QTDE_CB_ABATIDA) || 0;
                const peso = parseFloat(row.PESO_ABATIDO) || 0;
                const arroba = (peso / 15 / (abatido || 1)).toFixed(2);
                totalizadores.QTDE_CB_M_EAS += macho;
                totalizadores.QTDE_CB_F_EAS += femea;
                totalizadores.QTDE_CB_ESCALA += escala;
                totalizadores.QTDE_CB_ABATIDA += abatido;
                totalizadores.PESO_ABATIDO += peso;
                const classe = abatido === escala ? 'row-matched' : 'row-unmatched';
                return `
                    <tr class="${classe}">
                        <td>${row.Num_lote_abate}</td>
                        <td>${row.PRODUTOR}</td>
                        <td>${row.NOME_FAZENDA}</td>
                        <td>${formatarNumero(macho)}</td>
                        <td>${formatarNumero(femea)}</td>
                        <td>${formatarNumero(escala)}</td>
                        <td>${formatarNumero(abatido)}</td>
                        <td>${peso.toFixed(2)}</td>
                        <td>${arroba}</td>
                    </tr>`;
            }).join('');

            const totalLinha = `
                <tr>
                    <td colspan="3"><strong>Total:</strong></td>
                    <td><strong>${formatarNumero(totalizadores.QTDE_CB_M_EAS)}</strong></td>
                    <td><strong>${formatarNumero(totalizadores.QTDE_CB_F_EAS)}</strong></td>
                    <td><strong>${formatarNumero(totalizadores.QTDE_CB_ESCALA)}</strong></td>
                    <td><strong>${formatarNumero(totalizadores.QTDE_CB_ABATIDA)}</strong></td>
                    <td><strong>${totalizadores.PESO_ABATIDO.toFixed(2)}</strong></td>
                    <td><strong>${(totalizadores.PESO_ABATIDO / 15 / (totalizadores.QTDE_CB_ABATIDA || 1)).toFixed(2)}</strong></td>
                </tr>`;
            $('#programacaoAbate').html(programacaoHTML + totalLinha);
        });
    }

function formatDateBr(data) {
    const [ano, mes, dia] = data.split('-');
    return `${dia}/${mes}/${ano}`;
}


    setInterval(atualizarDados, 5000);
    atualizarDados();
</script>
