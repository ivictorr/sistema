<?php
// 🔹 Roteamento centralizado das páginas do sistema
// Usa helper loadPage($file, $acesso, $acessosPermitidos)

switch (true) {
    // --- ACOMPANHAMENTOS ---
    case isset($_GET['acompanhaAbate']):
        loadPage('./paginas/acompanhaAbate.php', 'acompanhaAbate', $acessosPermitidos);
        break;

    case isset($_GET['paradasAbate']):
        loadPage('./paginas/paradasAbate.php', 'relatorioParadasAbate', $acessosPermitidos);
        break;

    case isset($_GET['acompanhaEmbarque']):
        loadPage('./paginas/acompanhaEmbarque.php', 'acompanhaEmbarque', $acessosPermitidos);
        break;

    // --- ESTOQUE ---
    case isset($_GET['relatorioFifo']):
        loadPage('./paginas/relatorio/estoque/relatorioFifo.php', 'relatorioFifo', $acessosPermitidos);
        break;

    case isset($_GET['relatorioEstoqueValorizado']):
        loadPage('./paginas/relatorio/estoque/relatorioEstoqueGeral.php', 'relatorioEstoqueGeral', $acessosPermitidos);
        break;

    case isset($_GET['relatorioProducaoDesossa']):
        loadPage('./paginas/relatorio/estoque/relatorioProducaoDesossa.php', 'relatorioEstoqueGeral', $acessosPermitidos);
        break;

    case isset($_GET['EstoqueSIF']):
        loadPage('./paginas/relatorio/estoque/EstoqueSIF.php', 'relatorioEstoqueSIF', $acessosPermitidos);
        break;

    case isset($_GET['relatorioConferenciaSxV']):
        loadPage('./paginas/relatorio/estoque/relatorioConferenciaSxV.php', 'relatorioConferenciaSxV', $acessosPermitidos);
        break;

    case isset($_GET['relatorioEstoquePrevisto']):
        loadPage('./paginas/relatorio/estoque/relatorioEstoquePrevisto.php', 'relatorioEstoquePrevisto', $acessosPermitidos);
        break;

    case isset($_GET['estoqueProducao']):
        loadPage('./paginas/relatorio/estoque/relatorioEstoque.php', 'estoqueProducao', $acessosPermitidos);
        break;

    case isset($_GET['relatorioProducao']):
        loadPage('./paginas/relatorio/estoque/relatorioProducao.php', 'relatorioProducao', $acessosPermitidos);
        break;

    // --- FINANCEIRO ---
    case isset($_GET['resumoSaldo']):
        loadPage('./paginas/relatorio/financeiro/resumoSaldo.php', 'relatorioResumoSaldoReceber', $acessosPermitidos);
        break;
		
    case isset($_GET['limitedeCredito']):
        loadPage('./paginas/relatorio/financeiro/limitedeCredito.php', 'limitedeCredito', $acessosPermitidos);
        break;

    case isset($_GET['resumoSaldoPag']):
        loadPage('./paginas/relatorio/financeiro/resumoSaldoPag.php', 'relatorioResumoSaldoPagar', $acessosPermitidos);
        break;

    case isset($_GET['pagarVencimento']):
        loadPage('./paginas/relatorio/financeiro/contasPagarVencimento.php', 'relatorioContasPagarVencimento', $acessosPermitidos);
        break;

    case isset($_GET['receberVencimento']):
        loadPage('./paginas/relatorio/financeiro/contasReceberVencimento.php', 'relatorioContasReceberVencimento', $acessosPermitidos);
        break;

    case isset($_GET['resumoGeral']):
        loadPage('./paginas/relatorio/financeiro/resumoGeral.php', 'relatorioResumoGeral', $acessosPermitidos);
        break;

    // --- PCP ---
    case isset($_GET['confTransferencia']):
        loadPage('./paginas/relatorio/pcp/confTransferencia.php', 'conferenciaTransferencia', $acessosPermitidos);
        break;

    case isset($_GET['relatorioPallet']):
        loadPage('./paginas/relatorio/pcp/relatorioPallet.php', 'relatorioPallet', $acessosPermitidos);
        break;

    case isset($_GET['transferenciaXinxJatai']):
        loadPage('./paginas/relatorio/pcp/transferenciaXinxJatai.php', 'conferenciaProduto', $acessosPermitidos);
        break;

    case isset($_GET['transferenciaAltxJatai']):
        loadPage('./paginas/relatorio/pcp/transferenciaAltxJatai.php', 'conferenciaProduto', $acessosPermitidos);
        break;

    case isset($_GET['transferenciaAltxXin']):
        loadPage('./paginas/relatorio/pcp/transferenciaAltxXin.php', 'conferenciaProduto', $acessosPermitidos);
        break;

    case isset($_GET['conferenciaProduto']):
        loadPage('./paginas/relatorio/pcp/conferenciaProduto.php', 'conferenciaProduto', $acessosPermitidos);
        break;

    // --- COMERCIAL ---
    case isset($_GET['dashboardComercial']):
        loadPage('./paginas/relatorio/comercial/dashboardComercial.php', 'dashboardComercial', $acessosPermitidos);
        break;

    case isset($_GET['notasfiscais']):
        loadPage('./paginas/relatorio/comercial/notasfiscais.php', 'RelatorioNotaFiscal', $acessosPermitidos);
        break;

    case isset($_GET['estoqueRendimento']):
        loadPage('./paginas/relatorio/comercial/estoqueRendimento.php', 'estoqueRendimento', $acessosPermitidos);
        break;

    case isset($_GET['produtosnf']):
        loadPage('./paginas/relatorio/comercial/produtosNf.php', 'RelatorioNotaFiscal', $acessosPermitidos);
        break;

    case isset($_GET['relatorioPesoSaida']):
        loadPage('./paginas/relatorio/comercial/relatorioPesoSaida.php', 'relatorioPesoSaida', $acessosPermitidos);
        break;

    // --- EDILMA ---
    case isset($_GET['entradaxsaida']):
        loadPage('./paginas/relatorio/edilma/entradaxsaida.php', 'RelatorioEntradasxSaidas', $acessosPermitidos);
        break;

    // --- MAGELA ---
    case isset($_GET['relatoriocte']):
        loadPage('./paginas/relatorio/magela/relatoriocte.php', 'RelatorioCTE', $acessosPermitidos);
        break;

    // --- CONTABILIDADE ---
    case isset($_GET['relatorioContabilidade']):
        loadPage('./paginas/relatorio/contabilidade/relatorioContabilidade.php', 'relatorioContabilidade', $acessosPermitidos);
        break;

    // --- COMPRA DE GADO ---
    case isset($_GET['cadastroBovino']):
        loadPage('./paginas/modulo/compra_gado/cadastroBovino.php', 'cadastroBovino', $acessosPermitidos);
        break;

    case isset($_GET['cadastroLogistica']):
        loadPage('./paginas/modulo/compra_gado/cadastroLogistica.php', 'cadastroLogistica', $acessosPermitidos);
        break;

    case isset($_GET['dashboardGerencial']):
        loadPage('./paginas/modulo/compra_gado/dashboardGerencial.php', 'dashboardGerencial', $acessosPermitidos);
        break;

    // --- CENTRO DE DISTRIBUIÇÃO ---
    case isset($_GET['relatorioBrasilia']):
        loadPage('./paginas/relatorio/cd/relatorioBrasilia.php', 'relatorioBrasilia', $acessosPermitidos);
        break;

    case isset($_GET['relatorioTrindade']):
        loadPage('./paginas/relatorio/cd/relatorioTrindade.php', 'relatorioTrindade', $acessosPermitidos);
        break;

    case isset($_GET['relatorioSaoPaulo']):
        loadPage('./paginas/relatorio/cd/relatorioSaoPaulo.php', 'relatorioSaoPaulo', $acessosPermitidos);
        break;

    // --- ADMINISTRAÇÃO ---
    case isset($_GET['relatorioDisponibilidade']):
        loadPage('./paginas/relatorio/admin/Disponibilidade.php', 'relatorioDisponibilidade', $acessosPermitidos);
        break;
		
    case isset($_GET['Cenarios']):
        loadPage('./paginas/relatorio/admin/Cenarios.php', 'Cenarios', $acessosPermitidos);
        break;
		
    case isset($_GET['Desenvolvimento']):
        loadPage('./paginas/relatorio/admin/Desenvolvimento.php', 'Desenvolvimento', $acessosPermitidos);
        break;
		
    case isset($_GET['usuarioAcessos']):
        include('./paginas/acessosUsuarios.php');
        break;

    case isset($_GET['criarUsuario']):
        include('./paginas/criarUsuario.php');
        break;

    case isset($_GET['alterarSenha']):
        include('./paginas/alterarSenha.php');
        break;

    // --- DEFAULT (HOME) ---
    default:
        include('./paginas/conteudo.php');
        break;
}
