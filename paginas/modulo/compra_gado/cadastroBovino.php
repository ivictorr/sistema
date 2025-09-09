<?php

	$pecuarista = $pdoS->query("SELECT * FROM tbCadastroGeral A WHERE A.Tipo_cadastro = 'P'");
	$comprador = $pdoS->query("SELECT * FROM tbCadastroGeral A WHERE A.Tipo_cadastro = 'E'");
	$prazo = $pdoS->query("SELECT A.Prazo_medio FROM tbCondPgto A");
	$listagem = $pdoM->query("SELECT * FROM cadastrobovinos WHERE usuario = '".$_SESSION['user_id']."'");
	
	// Verifica se o formulário foi enviado
if (isset($_POST['cadastrarBovino'])) {
    try {

     // Captura dos valores do formulário
        $bovino = trim($_POST['tipo_bovino'] ?? '');
        $pecuarista = trim($_POST['pecuarista'] ?? '');
        $preco_i = trim($_POST['preco'] ?? '');
        $preco_f = trim($_POST['preco_final'] ?? '');
        $prazo = trim($_POST['prazo'] ?? '');
        $tipo_peso = trim($_POST['tipo_peso'] ?? '');
        $comprador = trim($_POST['comprador'] ?? '');
        $data_compra = trim($_POST['data_compra'] ?? '');
        $data_abate = trim($_POST['data_abate'] ?? '');
        $usuario = $_SESSION['user_id'] ?? '';

        // Validação de campos obrigatórios
        if (empty($bovino) || empty($pecuarista) || empty($preco_i) || empty($preco_f) || empty($prazo) ||
            empty($tipo_peso) || empty($comprador) || empty($data_compra) || empty($data_abate)) {
	    $_SESSION['mensagem'] = "Preencha todos os campos obrigatórios.";
        $_SESSION['tipo_mensagem'] = "danger";
        }

        // Validação numérica dos preços
        if (!is_numeric($preco_i) || !is_numeric($preco_f)) {
	    $_SESSION['mensagem'] = "Os campos 'Preço' e 'Preço Final' devem ser números.";
        $_SESSION['tipo_mensagem'] = "danger";
        }

        // Validação das datas
        if (!preg_match("/\d{4}-\d{2}-\d{2}/", $data_compra) || !preg_match("/\d{4}-\d{2}-\d{2}/", $data_abate)) {
		$_SESSION['mensagem'] = "As datas informadas são inválidas.";
        $_SESSION['tipo_mensagem'] = "danger";
        }

        // Impedir que a data de abate seja anterior à data de compra
        if (strtotime($data_abate) < strtotime($data_compra)) {
	    $_SESSION['mensagem'] = "A data de abate não pode ser anterior à data de compra.";
        $_SESSION['tipo_mensagem'] = "danger";
        }

        // Inserção no banco de dados
        $stmt = $pdoM->prepare("INSERT INTO cadastrobovinos 
            (bovino, pecuarista, preco_i, preco_f, prazo, tipo, comprador, data_compra, data_abate, usuario)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([$bovino, $pecuarista, $preco_i, $preco_f, $prazo, $tipo_peso, $comprador, $data_compra, $data_abate, $usuario]);

        $_SESSION['mensagem'] = "Cadastro realizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
    } catch (Exception $e) {
        // Captura e exibe erros personalizados
        $_SESSION['mensagem'] = "Erro: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
} 
?>
<style>
        body {
            background-color: #f1f8e9;
        }

        .container-compra {
            background: white;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }

        h2 {
            text-align: center;
            color: #558b2f;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: bold;
            color: #33691e;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #a5d6a7;
        }

        .btn-primary {
            background-color: #81c784;
            border-color: #66bb6a;
            color: white;
            border-radius: 6px;
            font-weight: bold;
        }

        .btn-primary:hover {
            background-color: #66bb6a;
            border-color: #558b2f;
        }

        .row {
            margin-bottom: 15px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 30px;
        }

        .table th {
            background: #81c784;
            color: white;
        }

        .btn-sm {
            font-size: 14px;
            padding: 5px 10px;
        }
    </style>

<br><br>
<?php 
if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?=$_SESSION['tipo_mensagem']?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['mensagem']; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php 
    unset($_SESSION['mensagem']); // Remove a mensagem após exibição
    unset($_SESSION['tipo_mensagem']);
endif; 
?>
    <div class="container mt-4">
        <div class="container-compra">
            <h2>Cadastro de Bovinos</h2>
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="tipo_bovino">Tipo de Bovino:</label>
                        <select id="tipo_bovino-select" name="tipo_bovino" class="selectpicker form-control" data-live-search="true" title="Selecione uma opção">';
                            <option>Marruco</option>
                            <option>Boi Inteiro</option>
                            <option>Boi Capeado</option>
                            <option>Boi Confinado</option>
                            <option>Vaca</option>
                            <option>Vaca Pesada</option>
                            <option>Vaca Comercial</option>
                            <option>Novilha</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="pecuarista">Nome do Pecuarista:</label>
                        <select id="pecuarista" name="pecuarista" class="selectpicker form-control" data-live-search="true" title="Selecione uma opção">
						<?php while($r = $pecuarista->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?=$r['Nome_cadastro']?>"><?=$r['Nome_cadastro']?></option>
						<?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="preco">Preço R$:</label>
                        <input type="text" class="form-control" id="preco" name="preco" placeholder="Digite o preço">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="preco_final">Preço Final R$:</label>
                        <input type="text" class="form-control" id="preco_final" name="preco_final" placeholder="Digite o preço final">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="prazo">Prazo:</label>
                        <select id="prazo" name="prazo" class="selectpicker form-control" data-live-search="true" title="Selecione uma opção">
						<?php while($rs = $prazo->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?=$rs['Prazo_medio']?>"><?=$rs['Prazo_medio']?></option>
						<?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="tipo_peso">Tipo de Peso:</label>
                        <select id="tipo_peso" name="tipo_peso" class="selectpicker form-control" data-live-search="true" title="Selecione uma opção">
                            <option>Peso Morto</option>
                            <option>Peso Vivo</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="comprador">Comprador:</label>
                        <select id="comprador" name="comprador" class="selectpicker form-control" data-live-search="true" title="Selecione uma opção">
						<?php while($rx = $comprador->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?=$rx['Nome_cadastro']?>"><?=$rx['Nome_cadastro']?></option>
						<?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="data_compra">Data da Compra:</label>
                        <input type="date" class="form-control" id="data_compra" name="data_compra">
                    </div>
                </div>
				  <div class="row">
                    <div class="col-md-12 form-group">
                        <label for="data_abate">Data do Abate:</label>
                        <input type="date" class="form-control" id="data_abate" name="data_abate">
                    </div>
                </div>
<br>
                <div class="text-center">
                    <button type="submit" name="cadastrarBovino" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>

        <!-- Caixa com a Tabela de Cadastrados -->
        <div class="table-container">
            <h2>Bovinos Cadastrados</h2>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Pecuarista</th>
                        <th>Preço</th>
                        <th>Preço Final</th>
						<th>Prazo</th>
						<th>Tipo</th>
                        <th>Comprador</th>
                        <th>Data Compra</th>
						<th>Data Abate</th>
                        <th>Ações</th>
                    </tr>
                </thead>
				<?php while($r = $listagem->fetch(PDO::FETCH_ASSOC)): ?>
                <tbody>
                    <tr>
                        <td><?=$r['bovino']?></td>
                        <td><?=$r['pecuarista']?></td>
                        <td><?=$r['preco_i']?></td>
                        <td><?=$r['preco_f']?></td>
						<td><?=$r['prazo']?></td>
						<td><?=$r['tipo']?></td>
                        <td><?=$r['comprador']?></td>
                        <td><?=$r['data_compra']?>7</td>
						<td><?=$r['data_abate']?></td>
                        <td>
                            <button class="btn btn-warning btn-sm">Editar</button>
                            <button class="btn btn-danger btn-sm">Excluir</button>
							<button class="btn btn-success btn-sm">Copiar</button>
                        </td>
                </tbody>
				<?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>
