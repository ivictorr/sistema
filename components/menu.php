<?php
// 🔹 MENU LATERAL
?>
<div class="col-md-3 left_col">
  <div class="left_col scroll-view">
    <div class="navbar nav_title" style="border: 0;">
      <a href="./" class="site_title">
        <img src="./images/icon-valencio.png" width="50" height="50" />
        <span style="margin-left:10px"> VALÊNCIO</span>
      </a>
    </div>

    <div class="clearfix"></div>
    <br />

    <!-- Sidebar menu -->
    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
      <div class="menu_section">
        <h3>Geral</h3>
        <ul class="nav side-menu">

          <!-- HOME -->
          <li><a href="./"><i class="fas fa-home"></i> HOME </a></li>

<!-- ACOMPANHAMENTOS -->
<?php if (
    temPermissaoModulo(1, $modulosPermitidos) || 
    temPermissaoModulo(2, $modulosPermitidos) || 
    temPermissaoModulo(12, $modulosPermitidos)
): ?>
  <li>
    <a><i class="fas fa-eye"></i> ACOMPANHAMENTOS <span class="fas fa-chevron-down"></span></a>
    <ul class="nav child_menu">
      <?php if (temPermissaoAcesso('acompanhaAbate', $acessosPermitidos)): ?>
        <li><a href="./?acompanhaAbate">Acompanha Abate</a></li>
      <?php endif; ?>

      <?php if (temPermissaoAcesso('acompanhaEmbarque', $acessosPermitidos)): ?>
        <li><a href="./?acompanhaEmbarque">Acompanha Embarque</a></li>
      <?php endif; ?>

      <?php if (temPermissaoAcesso('acompanhaDesossa', $acessosPermitidos)): ?>
        <li><a href="./?acompanhaDesossa">Acompanha Desossa</a></li>
      <?php endif; ?>

      <?php if (temPermissaoAcesso('relatorioParadasAbate', $acessosPermitidos)): ?>
        <li><a href="./?paradasAbate">Paradas Abate</a></li>
      <?php endif; ?>
    </ul>
  </li>
<?php endif; ?>


          <!-- ESTOQUE -->
          <?php if (temPermissaoModulo(3, $modulosPermitidos)): ?>
            <li>
              <a><i class="fas fa-eye"></i> ESTOQUE <span class="fas fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                <?php if (temPermissaoAcesso('relatorioFifo', $acessosPermitidos)): ?>
                  <li><a href="./?relatorioFifo">Relatório Fifo Analítico</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('estoqueProducao', $acessosPermitidos)): ?>
                  <li><a href="./?estoqueProducao">Estoque Produtos</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioProducao', $acessosPermitidos)): ?>
                  <li><a href="./?relatorioProducao">Relatório Produção</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioEstoqueGeral', $acessosPermitidos)): ?>
                  <li><a href="./?relatorioEstoqueValorizado">Relatório Estoque Valorizado</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioProducaoDesossa', $acessosPermitidos)): ?>
                  <li><a href="./?relatorioProducaoDesossa">Estoque Geral (Apenas Desossa)</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioEstoqueSIF', $acessosPermitidos)): ?>
                  <li><a href="./?EstoqueSIF">Estoque por SIF</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioConferenciaSxV', $acessosPermitidos)): ?>
                  <li><a href="./?relatorioConferenciaSxV">Conferência Volume x Saldo</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioEstoquePrevisto', $acessosPermitidos)): ?>
                  <li><a href="./?relatorioEstoquePrevisto">Relatório Estoque Previsto</a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

          <!-- COMERCIAL -->
          <?php if (temPermissaoModulo(7, $modulosPermitidos)): ?>
            <li>
              <a><i class="fas fa-eye"></i> COMERCIAL <span class="fas fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                <?php if (temPermissaoAcesso('dashboardComercial', $acessosPermitidos)): ?>
                  <li><a href="./?dashboardComercial">Dashboard Comercial</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioConferenciaVendas', $acessosPermitidos)): ?>
                  <li><a href="./?notasfiscais">Relatório Preço Médio por Local de Venda</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('estoqueRendimento', $acessosPermitidos)): ?>
                  <li><a href="./?estoqueRendimento">Relatório Preço Médio por Grupo</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('RelatorioNotaFiscal', $acessosPermitidos)): ?>
                  <li><a href="./?produtosnf">Relatório NFs Produtos</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioPesoSaida', $acessosPermitidos)): ?>
                  <li><a href="./?relatorioPesoSaida">Conferência Peso de Saída</a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

          <!-- PCP -->
          <?php if (temPermissaoModulo(5, $modulosPermitidos)): ?>
            <li>
              <a><i class="fas fa-eye"></i> PCP <span class="fas fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                <?php if (temPermissaoAcesso('conferenciaTransferencia', $acessosPermitidos)): ?>
                  <li><a href="./?transferenciaXinxJatai">Conf. Xinguara x Jataí</a></li>
                  <li><a href="./?transferenciaAltxJatai">Conf. Altamira x Jataí</a></li>
                  <li><a href="./?transferenciaAltxXin">Conf. Altamira x Xinguara</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('relatorioPallet', $acessosPermitidos)): ?>
                  <li><a href="./?relatorioPallet">Produção x Pallet</a></li>
                <?php endif; ?>
                <?php if (temPermissaoAcesso('conferenciaProduto', $acessosPermitidos)): ?>
                  <li><a href="./?conferenciaProduto">Conferência Produto</a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>
                <?php if (temPermissaoModulo(8, $modulosPermitidos)): ?>
                  <li>
                    <a><i class="fas fa-eye"></i> MAGELA <span class="fas fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <?php if (temPermissaoAcesso('RelatorioCTE', $acessosPermitidos)): ?>
                        <li><a href="./?relatoriocte">Relatorio CTE</a></li>
                      <?php endif; ?>
                    </ul>
                  </li>
                <?php endif; ?>
				                <?php if (temPermissaoModulo(4, $modulosPermitidos)): ?>
                  <li>
                    <a><i class="fas fa-eye"></i> FINANCEIRO <span class="fas fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <?php if (temPermissaoAcesso('limitedeCredito', $acessosPermitidos)): ?>
                        <li><a href="./?limitedeCredito">Limite de Crédito (Clientes)</a></li>
                      <?php endif; ?>
                    </ul>
                  </li>
                <?php endif; ?>
          <!-- CONFIGURAÇÕES -->
          <li>
            <a><i class="fas fa-tools"></i> CONFIGURAÇÕES <span class="fas fa-chevron-down"></span></a>
            <ul class="nav child_menu">
              <li><a href="./?alterarSenha">Alterar Senha</a></li>
              <?php if (temPermissaoAcesso('relatorioDisponibilidade', $acessosPermitidos)): ?>
                <li><a href="./?relatorioDisponibilidade">Simulação Desossa</a></li>
              <?php endif; ?>
			  <?php if (temPermissaoAcesso('Desenvolvimento', $acessosPermitidos)): ?>
                <li><a href="./?Desenvolvimento">Desenvolvimento</a></li>
              <?php endif; ?>
			  <?php if (temPermissaoAcesso('Cenarios', $acessosPermitidos)): ?>
                <li><a href="./?Cenarios">Alterar Cenarios</a></li>
              <?php endif; ?>
            </ul>
          </li>

          <!-- ADMINISTRAÇÃO -->
          <?php if (isAdmin($_SESSION['user_id'])): ?>
            <li>
              <a><i class="fas fa-tools"></i> ADMINISTRAÇÃO <span class="fas fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                <li><a href="./?criarUsuario">Criar Usuário</a></li>
                <li><a href="./?usuarioAcessos">Acessos</a></li>
              </ul>
            </li>
          <?php endif; ?>

        </ul>
      </div>
    </div>

    <!-- Footer do menu -->
    <div class="sidebar-footer hidden-small">
      <a data-toggle="tooltip" title="Settings"><span class="glyphicon glyphicon-cog"></span></a>
      <a data-toggle="tooltip" title="FullScreen"><span class="glyphicon glyphicon-fullscreen"></span></a>
      <a data-toggle="tooltip" title="Lock"><span class="glyphicon glyphicon-eye-close"></span></a>
      <a data-toggle="tooltip" title="Logout" href="./?sair"><span class="glyphicon glyphicon-off"></span></a>
    </div>
  </div>
</div>
	