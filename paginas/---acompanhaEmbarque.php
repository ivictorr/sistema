<div class="container-fluid">
    <div class="row">
        <!-- Seção de Monitoramento -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading text-center">
                    <h3 class="panel-title">Monitoramento de Embarques</h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>O.C</th>
                                    <th>Rotas</th>
                                    <th>Motorista</th>
                                    <th>Qtde Pedida</th>
                                    <th>Qtde Carregada</th>
                                    <th>% Carregada</th>
                                    <th>Ultima Pesagem</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaMonitorEmbarque">
                                <!-- Dados serão adicionados dinamicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <small>Atualizado em tempo real via WebSocket</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const socket = new WebSocket('ws://192.168.0.232:8080');

    // Função para formatar pesos no estilo brasileiro
    function formatarPeso(peso) {
        return peso.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Função para formatar a data
    function formatarData(dataISO) {
        const data = new Date(dataISO);
        const dia = String(data.getUTCDate()).padStart(2, '0'); // Dia UTC
        const mes = String(data.getUTCMonth() + 1).padStart(2, '0'); // Mês UTC (0 indexado)
        const ano = data.getUTCFullYear(); // Ano UTC
        return `${dia}/${mes}/${ano}`;
    }
	
function formatarDataHora(dataISO) {
    const data = new Date(dataISO);
    const dia = String(data.getUTCDate()).padStart(2, '0'); // Dia UTC
    const mes = String(data.getUTCMonth() + 1).padStart(2, '0'); // Mês UTC (0 indexado)
    const ano = data.getUTCFullYear(); // Ano UTC
    const horas = String(data.getUTCHours()).padStart(2, '0'); // Hora UTC
    const minutos = String(data.getUTCMinutes()).padStart(2, '0'); // Minutos UTC
    return `${dia}/${mes}/${ano} - ${horas}:${minutos}`;
}
    // Função para calcular a porcentagem carregada
    function calcularPorcentagem(carregada, pedida) {
        if (!pedida || pedida === 0) return 0;
        return ((carregada / pedida) * 100).toFixed(2);
    }

    // Função para atualizar a tabela com os dados do JSON
    function atualizarTabela(dados) {
        const tabela = document.getElementById('tabelaMonitorEmbarque');
        tabela.innerHTML = ''; // Limpa a tabela antes de adicionar os dados

        dados.forEach((item) => {
            const porcentagemCarregada = calcularPorcentagem(item.QTDE_PRIMARIA_ROMANEIO, item.QTDE_PRIMARIA_PEDIDO);
            const linha = document.createElement('tr');

            linha.innerHTML = `
                <td>${item.NUM_CARGA}</td>
                <td>${item.PLACA || '<span class="text-muted">--- Sem Placa ---</span>'}</td>
                <td>${item.ROTAS || '<span class="text-muted">--- Sem Rota ---</span>'}</td>
                <td>${formatarPeso(item.QTDE_PRIMARIA_PEDIDO)} ${item.UNIDADE_MEDIDA_PRIMARIA.trim()}</td>
                <td>${item.QTDE_PRIMARIA_ROMANEIO ? formatarPeso(item.QTDE_PRIMARIA_ROMANEIO) : '<span class="text-muted">--- Não Carregado ---</span>'}</td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar progress-bar-success" role="progressbar" 
                             aria-valuenow="${porcentagemCarregada}" 
                             aria-valuemin="0" aria-valuemax="100" 
                             style="width: ${porcentagemCarregada}%; font-size: 14px; line-height: 20px;">
                            ${porcentagemCarregada}%
                        </div>
                    </div>
                </td>
                <td>${item.HORA_FINAL ? formatarDataHora(item.HORA_FINAL) : '<span class="text-muted">--- Sem Data ---</span>'}</td>
            `;
            tabela.appendChild(linha);
        });
    }

    socket.onmessage = (message) => {
        try {
            const data = JSON.parse(message.data);

            // Verifica o evento recebido
            if (data.event === 'monitor_embarque') {
                console.log("Dados recebidos:", data);

                // Acessa diretamente `data.data` como um array
                const dadosMonitor = data.data;

                if (Array.isArray(dadosMonitor)) {
                    atualizarTabela(dadosMonitor);
                } else {
                    console.error("Os dados recebidos não são um array:", dadosMonitor);
                }
            } else {
                console.warn("Evento desconhecido recebido:", data.event);
            }
        } catch (error) {
            console.error("Erro ao processar a mensagem do WebSocket:", error);
        }
    };

    socket.onerror = (error) => {
        console.error('WebSocket Error:', error);
    };

    socket.onclose = () => {
        console.warn('WebSocket connection closed.');
    };
</script>
