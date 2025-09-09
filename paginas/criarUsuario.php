<style>
  body {
    background-color: #f4f4f4;
    font-family: Arial, sans-serif;
  }

  .page-header {
    background-color: #343a40;
    color: #fff;
    padding: 15px 20px;
    margin: 0 -15px 20px -15px;
  }

  .form-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  }

  .btn-primary {
    background-color: #17a2b8;
    border-color: #17a2b8;
  }

  .btn-primary:hover {
    background-color: #138496;
    border-color: #117a8b;
  }

  label {
    font-weight: bold;
    color: #333;
  }

  .form-footer {
    margin-top: 20px;
  }
</style>
<?php
if (isset($_POST['cadastrarUsuario'])):
  // Captura os dados do formulário
  $nome = $_POST['nome'];
  $usuario = $_POST['login'];
  $senha = $_POST['senha']; // Criptografa a senha
  $administrador = ($_POST['tipo_usuario'] === 'Admin') ? 2 : 1; // Define o tipo de usuário

  try {
    // Prepara a query para evitar SQL Injection
    $stmt = $pdoM->prepare("INSERT INTO usuarios (nome, usuario, senha, Administrador) VALUES (:nome, :usuario, :senha, :Administrador)");
    // Executa a query com os dados do formulário
    $stmt->execute([
      ':nome' => $nome,
      ':usuario' => $usuario,
      ':senha' => $senha,
      ':Administrador' => $administrador
    ]);
    echo '<div class="alert alert-success">Usuário cadastrado com sucesso!</div>';
  } catch (PDOException $e) {
    // Exibe mensagem de erro caso algo dê errado
    echo '<div class="alert alert-danger">Erro ao cadastrar o usuário: ' . $e->getMessage() . '</div>';
  }
endif;
?>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-12">
      <div class="page-header">
        <h2>Cadastro de Usuário</h2>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <div class="form-container">
        <form action="" method="POST">
          <!-- Nome -->
          <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" class="form-control" placeholder="Digite o nome completo" required>
          </div>
          <!-- Login -->
          <div class="form-group">
            <label for="login">Login</label>
            <input type="text" id="login" name="login" class="form-control" placeholder="Digite o login" required>
          </div>
          <!-- Senha -->
          <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" placeholder="Digite a senha" required>
          </div>
          <!-- Tipo de Usuário -->
          <div class="form-group">
            <label for="tipo_usuario">Tipo de Usuário</label>
            <select id="tipo_usuario" name="tipo_usuario" class="form-control" required>
              <option value="Admin">Administrador</option>
              <option value="Usuario">Usuário</option>
            </select>
          </div>
          <!-- Botões -->
          <div class="form-footer text-center">
            <button type="submit" name="cadastrarUsuario" class="btn btn-primary">Cadastrar</button>
            <button type="reset" class="btn btn-default">Limpar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>