<?php
// 🔹 TOPO DA PÁGINA (NAVEGAÇÃO SUPERIOR)
?>
<div class="top_nav">
  <div class="nav_menu">
    <form action="#" method="post">
      <nav>
        <div class="nav toggle">
          <a id="menu_toggle"><i class="fa fa-bars"></i></a>
        </div>

        <ul class="nav navbar-nav navbar-right">
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fas fa-map-marker-alt"></i> Filial:
              <?= $GLOBALS['FILIAL_USUARIO'] ?? '---' ?> <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="?trocar_filial=100">Filial 100 - Xinguara</a></li>
              <li><a href="?trocar_filial=200">Filial 200 - Jataí</a></li>
              <li><a href="?trocar_filial=400">Filial 400 - Altamira</a></li>
            </ul>
          </li>
        </ul>
      </nav>
    </form>
  </div>
</div>
