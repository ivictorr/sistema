<?php
require_once('../configuracao/conexao.php');
$mensagem = ''; // Variável para armazenar mensagens de sucesso ou erro
$tipoMensagem = ''; // Tipo de mensagem: success ou danger

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usernameInput = $_POST['login'];
        $passwordInput = $_POST['senha'];
        $sessionId = session_id();

        // Verifica se o usuário existe
        $stmt = $pdoM->prepare('SELECT * FROM usuarios WHERE usuario = :username');
        $stmt->execute([':username' => $usernameInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verifica a senha
            if ($passwordInput === $user['senha']) {
                // Verifica se o usuário já está logado em outra sessão
                if ($user['is_logged_in'] && $user['last_session_id'] !== $sessionId) {
                    // Força o logout da outra sessão
                    $update = $pdoM->prepare('UPDATE usuarios SET is_logged_in = 0, last_session_id = NULL WHERE id = :id');
                    $update->execute([':id' => $user['id']]);
                }

                // Atualiza o status de login
                $update = $pdoM->prepare('UPDATE usuarios SET is_logged_in = 1, last_session_id = :session_id, filial_selecionada = :filialSelecionada  WHERE id = :id');
                $update->execute([
                    ':session_id' => $sessionId,
					':filialSelecionada' => $_POST['filial'],
                    ':id' => $user['id']
                ]);

                // Armazena os dados do usuário na sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['usuario'];

                $mensagem = 'Login bem-sucedido! Bem-vindo, ' . htmlspecialchars($user['usuario']) . '.';
                $tipoMensagem = 'success'; // Define tipo de mensagem como sucesso
            } else {
                $mensagem = 'Senha incorreta.';
                $tipoMensagem = 'danger'; // Define tipo de mensagem como erro
            }
        } else {
            $mensagem = 'Usuário não encontrado.';
            $tipoMensagem = 'danger'; // Define tipo de mensagem como erro
        }
    }
} catch (PDOException $e) {
    $mensagem = 'Erro de conexão: ' . $e->getMessage();
    $tipoMensagem = 'danger'; // Define tipo de mensagem como erro
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administração Valencio</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <style>
    body {
      background-color: #e8f5e9; /* Fundo suave em tom verde claro */
      font-family: 'Arial', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .bg-img {
      background-size: cover;
      background-position: center;
    }
    header {
      margin-bottom: 20px;
    }
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      background-color: #ffffff;
      transition: transform 0.3s ease-in-out;
    }
    .card:hover {
      transform: translateY(-10px);
    }
    .card-header {
      background-color: #81c784; /* Verde claro */
      color: #ffffff;
      font-size: 1.25rem;
      text-transform: uppercase;
    }
    .btn-primary {
      background-color: #81c784; /* Verde claro */
      border-color: #81c784;
      transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
    }
    .btn-primary:hover {
      background-color: #66bb6a; /* Tom um pouco mais escuro no hover */
      transform: scale(1.05);
    }
    footer {
      background-color: #4caf50;
      color: #ffffff;
      text-align: center;
      padding: 20px 0;
      margin-top: auto;
    }
    footer p {
      margin: 0;
      font-size: 0.9rem;
    }
    main {
      flex: 1;
    }
  </style>
</head>
<body class="bg-img">

<header>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-8 text-center">
        <img src="../images/valencio.png" alt="Logo" class="img-fluid mb-3" width="200">
        <h1 class="text-success font-weight-bold">Bem-vindo à Administração Valencio</h1>
        <p class="text-secondary">Por favor, faça login para continuar.</p>
      </div>
    </div>
  </div>
</header>

<main>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header text-center">
            Login
          </div>
          <div class="card-body">
            <form action="" method="POST">
              <div class="form-group">
                <label for="username">Nome de usuário</label>
                <input type="text" name="login" class="form-control" id="username" placeholder="Digite seu usuário" required>
              </div>
              <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" name="senha" class="form-control" id="password" placeholder="Digite sua senha" required>
              </div>
              <div class="form-group">
                <label for="filial">Filial</label>
                <select name="filial" class="form-control" required>
                  <option value="100">Xinguara</option>
                  <option value="200">Jataí</option>
				  <option value="400">Altamira</option>
                </select>
              </div>
              <button type="submit" name="logarUsuario" class="btn btn-primary btn-block">Entrar</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Modal de Mensagem -->
<?php if ($mensagem): ?>
<div class="modal fade" id="mensagemModal" tabindex="-1" role="dialog" aria-labelledby="mensagemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-<?= $tipoMensagem ?>">
        <h5 class="modal-title text-white" id="mensagemModalLabel"><?= $tipoMensagem === 'success' ? 'Sucesso' : 'Erro' ?></h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" id="closeModal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?= htmlspecialchars($mensagem) ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="closeButton">Fechar</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<footer>
  <div class="container">
    <p>&copy; 2024 Frigorifico Valencio. Todos os direitos reservados.</p>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
<script>
  // Exibe o modal automaticamente se houver mensagem
  <?php if ($mensagem): ?>
  $(document).ready(function() {
    $('#mensagemModal').modal('show');
  });

  // Redireciona após fechar o modal
  $('#closeModal, #closeButton').on('click', function() {
    <?php if ($tipoMensagem === 'success'): ?>
    window.location.href = "../"; // Substitua pelo URL desejado
    <?php endif; ?>
  });
  <?php endif; ?>
</script>
</body>
</html>
