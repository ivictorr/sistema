<?php

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = trim($_POST['senha']);
    $newsenha = trim($_POST['newsenha']);
    $usuario_id = $_SESSION['user_id'];

    // Verificar se as senhas são iguais
    if ($senha === $newsenha) {
        // Hash da nova senha
        $hashedPassword = $senha;

        try {
			
			$uniqueId = bin2hex(random_bytes(12));
            // Atualizar a senha no banco de dados
            $sql = "UPDATE usuarios SET senha = :senha, is_logged_in = 1, last_session_id = '".$uniqueId."' WHERE id = :id";
            $stmt = $pdoM->prepare($sql);
            $stmt->bindParam(':senha', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT); 

            if ($stmt->execute()) {
                echo "<br><br><div class='alert alert-success'>Senha alterada com sucesso! Todos os usuários conectados foram desconectados.</div>";
            } else {
                echo "<br><br><div class='alert alert-danger'>Erro ao alterar a senha. Tente novamente.</div>";
            }
        } catch (PDOException $e) {
            echo "<br><br><div class='alert alert-danger'>Erro: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<br><br><div class='alert alert-danger'>As senhas não coincidem. Tente novamente.</div>";
    }
}
?>
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

  .alert-warning {
    background-color: #ffeeba;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #ffeeba;
  }
</style>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-12">
      <div class="page-header">
        <h2>Altere sua Senha</h2>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <div class="form-container">
        <!-- Alerta de aviso -->
        <div class="alert alert-warning">
          <strong>Aviso:</strong> Após alterar a senha, todos os usuários conectados com este usuário serão desconectados.
        </div>
        <form action="" method="POST">
          <!-- Nova Senha -->
          <div class="form-group">
            <label for="senha">Nova Senha</label>
            <input type="text" id="senha" name="senha" class="form-control" placeholder="Digite sua Nova Senha" required>
          </div>
          <!-- Repetir Nova Senha -->
          <div class="form-group">
            <label for="newsenha">Repetir Nova Senha</label>
            <input type="text" id="newsenha" name="newsenha" class="form-control" placeholder="Repita sua Nova Senha" required>
          </div>
          <!-- Botões -->
          <div class="form-footer text-center">
            <button type="submit" name="cadastrarUsuario" class="btn btn-primary">Alterar</button>
            <button type="reset" class="btn btn-default">Limpar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
