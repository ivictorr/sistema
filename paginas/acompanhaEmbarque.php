<style>
.linha-verde {
    background-color: #d4edda !important; /* Verde claro */
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <label for="dataFiltro"><strong>Data:</strong></label>
            <input type="date" id="dataFiltro" class="form-control input-sm">
        </div>
        <div class="col-md-8 text-right">
            <button class="btn btn-info" data-toggle="modal" data-target="#modalDetalhamento" id="btnDetalhamentoGeral">
                Detalhamento de Produtos
            </button>
        </div>
    </div>
    <br>

    <!-- Tabela de Cargas Iniciadas -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-success">
                <div class="panel-heading text-center"><strong>Cargas Iniciadas</strong></div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead>
                                <tr>
                                    <th class="text-center">O.C</th>
                                    <th class="text-center">Placa</th>
                                    <th class="text-center">Rotas</th>
                                    <th class="text-center">Pedidos</th>
                                    <th class="text-center">Faturados</th>
                                    <th class="text-center">Qtde Pedida</th>
                                    <th class="text-center">Qtde Carregada</th>
                                    <th class="text-center">% Carregada</th>
                                    <th class="text-center">Última Pesagem</th>
                                    <th class="text-center">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaIniciadas"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Cargas Não Iniciadas -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-warning">
                <div class="panel-heading text-center"><strong>Cargas Não Iniciadas</strong></div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead>
                                <tr>
                                    <th class="text-center">O.C</th>
                                    <th class="text-center">Placa</th>
                                    <th class="text-center">Rotas</th>
                                    <th class="text-center">Pedidos</th>
                                    <th class="text-center">Faturados</th>
                                    <th class="text-center">Qtde Pedida</th>
                                    <th class="text-center">Qtde Carregada</th>
                                    <th class="text-center">% Carregada</th>
                                    <th class="text-center">Última Pesagem</th>
                                    <th class="text-center">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaNaoIniciadas"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalhamento de Produtos -->
<div class="modal fade" id="modalDetalhamento" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Detalhamento de Produtos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead>
                            <tr>
                                <th class="text-center">Código do Produto</th>
                                <th class="text-center">Descrição</th>
                                <th class="text-center">Qtd. Pedidos</th>
                                <th class="text-center">Estoque</th>
								<th class="text-center">C.Peças</th>
                                <th class="text-center">Carregado</th>
                                <th class="text-center">Diferença</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaDetalhamento"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setarDataAtual() {
    document.getElementById('dataFiltro').value = new Date().toISOString().split('T')[0];
}

const formatarNumero = (valor) => {
    return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(valor);
};

function formatarDataHora(dataISO) {
    if (!dataISO) return '<span class="text-muted">--- Sem Data ---</span>';
    const partes = dataISO.split(/[-T :.]/);
    return `${partes[2]}/${partes[1]}/${partes[0]} - ${partes[3]}:${partes[4]}`;
}

function calcularPorcentagem(carregada, pedida) {
    return pedida ? ((carregada / pedida) * 100).toFixed(2) : 0;
}

function carregarDetalhamento(numCarga = null) {
    let cargasExibidas = [];

    if (numCarga) {
        cargasExibidas.push(numCarga);
    } else {
        document.querySelectorAll('#tabelaIniciadas tr, #tabelaNaoIniciadas tr').forEach(row => {
            const num = row.cells[0]?.textContent.trim();
            if (num) cargasExibidas.push(num);
        });
    }

    fetch('consulta_detalhamento.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cargas: cargasExibidas })
    })
    .then(response => response.json())
    .then(dados => {
        const tabela = document.getElementById('tabelaDetalhamento');
        tabela.innerHTML = '';

        dados.forEach(produto => {
            const estoque = parseFloat(produto.ESTOQUE_KG) || 0;
            const carregado = parseFloat(produto.CARREGADO_KG) || 0;
            const total = parseFloat(produto.QTDE_TOTAL) || 0;
            const saldo = (estoque + carregado) - total;
            const cor = saldo >= 0 ? 'green' : 'red';

            tabela.innerHTML += `
                <tr>
                    <td>${produto.COD_PRODUTO}</td>
                    <td>${produto.Produto}</td>
                    <td>${produto.QTDE_TOTAL ? formatarNumero(produto.QTDE_TOTAL) : '0,00'}</td>
                    <td>${produto.ESTOQUE_KG ? formatarNumero(produto.ESTOQUE_KG) : '0,00'}</td>
					<td>${produto.CARREGADO_KG ? formatarNumero(produto.CARREGADO_AUX_KG) : '0,00'}</td>
                    <td>${produto.CARREGADO_KG ? formatarNumero(produto.CARREGADO_KG) : '0,00'}</td>
                    <td style="color:${cor}; font-weight:bold;">${formatarNumero(saldo)}</td>
                </tr>`;
        });
    })
    .catch(error => console.error('Erro ao carregar detalhamento:', error));
}

function atualizarTabela() {
    const dataSelecionada = document.getElementById('dataFiltro').value;

    fetch(`consulta_monitoramento.php?data=${dataSelecionada}`)
        .then(response => response.json())
        .then(dados => {
            const tabelaIniciadas = document.getElementById('tabelaIniciadas');
            const tabelaNaoIniciadas = document.getElementById('tabelaNaoIniciadas');

            tabelaIniciadas.innerHTML = '';
            tabelaNaoIniciadas.innerHTML = '';

            dados.forEach(item => {
                const porcentagemCarregada = calcularPorcentagem(item.QTDE_PRIMARIA_ROMANEIO, item.QTDE_PRIMARIA_PEDIDO);
                const linhaClasse = item.TOTAL_PEDIDOS === item.TOTAL_NF ? 'linha-verde' : '';

                const linha = `
                    <tr class="${linhaClasse}">
                        <td>${item.NUM_CARGA}</td>
                        <td>${item.PLACA || '<span class="text-muted">--- Sem Placa ---</span>'}</td>
                        <td>${item.ROTAS || '<span class="text-muted">--- Sem Rota ---</span>'}</td>
                        <td>${item.TOTAL_PEDIDOS}</td>
                        <td>${item.TOTAL_NF}</td>
                        <td>${item.QTDE_PRIMARIA_PEDIDO ? formatarNumero(item.QTDE_PRIMARIA_PEDIDO) : '0,00'}</td>
                        <td>${item.QTDE_PRIMARIA_ROMANEIO ? formatarNumero(item.QTDE_PRIMARIA_ROMANEIO) : '<span class="text-muted">--- Não Carregado ---</span>'}</td>
                        <td>
                            <div class="progress" style="height: 15px;">
                                <div class="progress-bar progress-bar-success" role="progressbar" 
                                     aria-valuenow="${porcentagemCarregada}" 
                                     aria-valuemin="0" aria-valuemax="100" 
                                     style="width: ${porcentagemCarregada}%; font-size: 12px;">
                                    ${porcentagemCarregada}%
                                </div>
                            </div>
                        </td>
                        <td>${formatarDataHora(item.HORA_FINAL)}</td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-detalhe" data-carga="${item.NUM_CARGA}" data-toggle="modal" data-target="#modalDetalhamento">
                                <i class="glyphicon glyphicon-search"></i>
                            </button>
                        </td>
                    </tr>`;

                if (item.QTDE_PRIMARIA_ROMANEIO > 0) {
                    tabelaIniciadas.innerHTML += linha;
                } else {
                    tabelaNaoIniciadas.innerHTML += linha;
                }
            });
        })
        .catch(error => console.error('Erro ao carregar os dados:', error));
}

document.getElementById('dataFiltro').addEventListener('change', atualizarTabela);

// Botão geral
document.getElementById('btnDetalhamentoGeral').addEventListener('click', () => carregarDetalhamento());

// Botão por carga
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-detalhe')) {
        const btn = e.target.closest('.btn-detalhe');
        const carga = btn.getAttribute('data-carga');
        carregarDetalhamento(carga);
    }
});

setarDataAtual();
atualizarTabela();
setInterval(atualizarTabela, 30000);
</script>
