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
</style>

<div class="container-fluid">
<?php if (temPermissaoAcesso('pararAbate', $acessosPermitidos)): ?>
    <div class="text-center mb-3">
        <!-- Botão de Parada de Abate -->
        <button class="btn btn-danger" data-toggle="modal" data-target="#paradaModal">Parar Abate</button>
    </div>
<?php endif; ?>
    <div class="row">
        <!-- Coluna de Última Carcaça -->
        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="card-custom">
                <h5 class="section-title">Última Carcaça</h5>
                <div class="data-display">
                    <h5 class="text-uppercase text-primary">Lote Atual</h5>
                    <span id="loteAtual" class="text-muted">---</span>
                </div>
                <div class="row">
                    <div class="col-xs-6">
                        <div class="data-display">
                            <h6 class="text-success">Banda 1</h6>
                            <span id="banda1" class="text-dark">---</span>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="data-display">
                            <h6 class="text-primary">Banda 2</h6>
                            <span id="banda2" class="text-dark">---</span>
                        </div>
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
                            <tr>
                                <td colspan="6">Nenhuma carcaça encontrada.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Coluna de Informações do Abate -->
        <div class="col-xs-12 col-sm-12 col-md-8">
            <div class="card-custom">
                <h5 class="section-title">Informações do Abate</h5>
                <div class="row">
                    <div class="col-xs-6 col-sm-3">
                        <div class="info-label">Data Abate</div>
                        <div id="dataAbate" class="input-disabled">---</div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="info-label">Início</div>
                        <div id="dataInicial" class="input-disabled">---</div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="info-label">Fim</div>
                        <div id="dataFinal" class="input-disabled">---</div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="info-label">Total</div>
                        <div id="tempoTotal" class="input-disabled">---</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-6 col-sm-3">
                        <div class="info-label">Total Dia</div>
                        <div id="totalDia" class="input-disabled">---</div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="info-label">Abatido</div>
                        <div id="totalAbatidos" class="input-disabled">---</div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="info-label">Macho</div>
                        <div id="machoDia" class="input-disabled">---</div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div class="info-label">Fêmea</div>
                        <div id="femeaDia" class="input-disabled">---</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12">
                        <div class="info-label text-center">Restante</div>
                        <div id="abateRestante" class="input-disabled">---</div>
                    </div>
                </div>

                <h5 class="section-title">Informações do Lote</h5>
                <div class="row">
                    <div class="col-xs-6">
                        <div class="info-label text-center">Proprietário</div>
                        <div id="propietarioAbate" class="input-disabled">---</div>
                    </div>
                    <div class="col-xs-6">
                        <div class="info-label text-center">Fazenda</div>
                        <div id="fornecedorAbate" class="input-disabled">---</div>
                    </div>
                </div>

                <div class="table-section">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr class="text-center">
                                <th>Macho</th>
                                <th>Fêmea</th>
                                <th>Abatidos</th>
                                <th>Peso</th>
                                <th>Média @</th>
                                <th>Média KG</th>
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
                        <thead>
                            <tr class="text-center">
                                <th>Lote</th>
                                <th>Pecuarista</th>
                                <th>Fazenda</th>
                                <th>Macho</th>
                                <th>Fêmea</th>
                                <th>Total</th>
                                <th>Abatido</th>
                                <th>Peso</th>
                                <th>@</th>
                            </tr>
                        </thead>
                        <tbody id="programacaoAbate" class="text-center">
                            <tr>
                                <td colspan="9">Nenhuma programação encontrada.</td>
                            </tr>
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
    const socket = new WebSocket('ws://192.168.0.232:8080');

    function formatDate(dateString) {
        const date = new Date(dateString);
        const day = String(date.getUTCDate()).padStart(2, '0'); // ObtÃ©m o dia em UTC
        const month = String(date.getUTCMonth() + 1).padStart(2, '0'); // ObtÃ©m o mÃªs em UTC (mÃªs comeÃ§a em 0)
        const year = date.getUTCFullYear(); // ObtÃ©m o ano em UTC
        return `${day}/${month}/${year}`; // Retorna no formato DD/MM/YYYY
    }
    socket.onmessage = (message) => {

        const data = JSON.parse(message.data);
        if (data.event === 'abate_update') {
            const {
                lote,
                carcacas,
                infoabate,
                infoabatidos,
                infoabatidoslote,
                programacaoabate,
            } = data.data;

            // Atualizar Lote Atual
            document.getElementById('loteAtual').innerText = lote || '---';

            // Atualizar Ãšltima CarcaÃ§a
            if (carcacas.length > 0) {
                const ultimaCarcaca = carcacas[carcacas.length - 1];
                document.getElementById('banda1').innerText = ultimaCarcaca.Peso_carcaca1 || '---';
                document.getElementById('banda2').innerText = ultimaCarcaca.Peso_carcaca2 || '---';
            }

            if (infoabatidoslote.length > 0) {
                const infoAbatidosLote = infoabatidoslote[0];
                document.getElementById('animalMachoTable').innerText = infoAbatidosLote.MACHO_LOTE || '0';
                document.getElementById('animalFemeaTable').innerText = infoAbatidosLote.FEMEA_LOTE || '0';
                document.getElementById('animalAbateTable').innerText = infoAbatidosLote.ABATIDO_LOTE || '0';
                document.getElementById('animalPesoTable').innerText = (infoAbatidosLote.PESO_ABATIDO_LOTE ? infoAbatidosLote.PESO_ABATIDO_LOTE.toFixed(2) : '0');
                document.getElementById('animalKgTable').innerText = (infoAbatidosLote.MEDIA_ARROBA_LOTE ? infoAbatidosLote.MEDIA_ARROBA_LOTE.toFixed(2) : '0');
                document.getElementById('animalMediaTable').innerText = (infoAbatidosLote.MEDIA_KG_LOTE ? infoAbatidosLote.MEDIA_KG_LOTE.toFixed(2) : '0');
            }

            // Atualizar Tabela de CarcaÃ§as
            const carcacasTable = document.getElementById('carcacasTable');
            carcacasTable.innerHTML = carcacas.map((carcaca, index) => {
                const diferencaPeso = (carcaca.Peso_carcaca1 || 0) - (carcaca.Peso_carcaca2 || 0);
                const isRed = (
                    (carcaca.Sexo === 'F' && (diferencaPeso > 5 || diferencaPeso < -5)) ||
                    (carcaca.Sexo === 'M' && (diferencaPeso > 10 || diferencaPeso < -10))
                );
                const rowStyle = isRed ? 'style="background-color: red; color: white;"' : '';

                return `
        <tr ${rowStyle}>
            <td>${index + 1}</td>
            <td>${carcaca.Sexo || '---'}</td>
            <td>${carcaca.Peso_carcaca1 || '---'}</td>
            <td>${carcaca.Peso_carcaca2 || '---'}</td>
            <td>${diferencaPeso.toFixed(2)}</td>
            <td>${((carcaca.Peso_carcaca1 || 0) + (carcaca.Peso_carcaca2 || 0)).toFixed(2)}</td>
        </tr>
    `;
            }).join('');



            // Atualizar Tabela de CarcaÃ§as
            const programacaoAbate = document.getElementById('programacaoAbate');

            // Calcula os totais
            const totalizadores = programacaoabate.reduce((totais, item) => {
                return {
                    QTDE_CB_M_EAS: totais.QTDE_CB_M_EAS + (item.QTDE_CB_M_EAS || 0),
                    QTDE_CB_F_EAS: totais.QTDE_CB_F_EAS + (item.QTDE_CB_F_EAS || 0),
                    QTDE_CB_ESCALA: totais.QTDE_CB_ESCALA + (item.QTDE_CB_ESCALA || 0),
                    QTDE_CB_ABATIDA: totais.QTDE_CB_ABATIDA + (item.QTDE_CB_ABATIDA || 0),
                    PESO_ABATIDO: totais.PESO_ABATIDO + (item.PESO_ABATIDO || 0),
                };
            }, {
                QTDE_CB_M_EAS: 0,
                QTDE_CB_F_EAS: 0,
                QTDE_CB_ESCALA: 0,
                QTDE_CB_ABATIDA: 0,
                PESO_ABATIDO: 0
            });

            // Renderiza a tabela com os dados e o totalizador
            programacaoAbate.innerHTML = programacaoabate.map((programacaoabate, index) => {
                // Garante que a comparaÃ§Ã£o seja feita corretamente (string ou nÃºmero)
                const isMatched = programacaoabate.QTDE_CB_ABATIDA === programacaoabate.QTDE_CB_ESCALA;
                const isCurrentLote = String(programacaoabate.Num_lote_abate) === String(lote); // Garante que o lote atual seja comparado como string

                return `
    <tr class="${isCurrentLote ? 'row-current' : isMatched ? 'row-matched' : 'row-unmatched'}">
        <td>${programacaoabate.Num_lote_abate || '0'}</td>
        <td>${programacaoabate.PRODUTOR || '0'}</td>
        <td>${programacaoabate.NOME_FAZENDA || '0'}</td>
        <td>${programacaoabate.QTDE_CB_M_EAS || '0'}</td>
        <td>${programacaoabate.QTDE_CB_F_EAS || '0'}</td>
        <td>${programacaoabate.QTDE_CB_ESCALA || '0'}</td>
        <td>${programacaoabate.QTDE_CB_ABATIDA || '0'}</td>
        <td>${programacaoabate.PESO_ABATIDO || '0'}</td>
        <td>${((programacaoabate.PESO_ABATIDO / 15) / (programacaoabate.QTDE_CB_ABATIDA || 1)).toFixed(2)}</td>
    </tr>`;
            }).join('');


            // Adiciona a linha do totalizador
            programacaoAbate.innerHTML += `
    <tr>
        <td colspan="3"><strong>Total:</strong></td>
        <td><strong>${totalizadores.QTDE_CB_M_EAS}</strong></td>
        <td><strong>${totalizadores.QTDE_CB_F_EAS}</strong></td>
        <td><strong>${totalizadores.QTDE_CB_ESCALA}</strong></td>
        <td><strong>${totalizadores.QTDE_CB_ABATIDA}</strong></td>
        <td><strong>${totalizadores.PESO_ABATIDO.toFixed(2)}</strong></td>
        <td><strong>${(totalizadores.PESO_ABATIDO / 15 / totalizadores.QTDE_CB_ABATIDA).toFixed(2) || '0.00'}</strong></td>
    </tr>
`;

            // Atualizar InformaÃ§Ãµes do Abate
            if (infoabate.length > 0) {
                const abateInfo = infoabate[0];
                document.getElementById('dataAbate').innerText = abateInfo.DATA_ABATE ? formatDate(abateInfo.DATA_ABATE) : '---';
                document.getElementById('dataInicial').innerText = abateInfo.DATA_INICIAL || '---';
                document.getElementById('dataFinal').innerText = abateInfo.DATA_FINAL || '---';
                document.getElementById('tempoTotal').innerText = abateInfo.TEMPO_TOTAL || '---';
            }

            if (infoabatidos.length > 0) {
                const infoAbatidos = infoabatidos[0];
                document.getElementById('totalDia').innerText = infoAbatidos.TOTAL || '---';
                document.getElementById('totalAbatidos').innerText = infoAbatidos.ABATIDO || '---';
                document.getElementById('machoDia').innerText = infoAbatidos.MACHO || '---';
                document.getElementById('femeaDia').innerText = infoAbatidos.FEMEA || '---';
                document.getElementById('abateRestante').innerText = (infoAbatidos.TOTAL - infoAbatidos.ABATIDO) || '---';
            }

            if (carcacas.length > 0) {
                const carcacasAbate = carcacas[0];
                document.getElementById('propietarioAbate').innerText = carcacasAbate.Nome_cadastro || '---';
                document.getElementById('fornecedorAbate').innerText = carcacasAbate.Nome || '---';
            }
        }
    };

    socket.onerror = (error) => {
        console.error('WebSocket Error:', error);
    };

    socket.onclose = () => {
        console.warn('WebSocket connection closed.');
    };
</script>