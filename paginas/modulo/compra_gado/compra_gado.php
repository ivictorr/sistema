<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro de Bovinos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        .container {
            max-width: 800px;
            margin-top: 20px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Cadastro de Bovinos</h2>
        <form>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="tipo_bovino">Tipo de Bovino:</label>
                    <select class="form-control" id="tipo_bovino" name="tipo_bovino">
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
                    <select class="form-control" id="pecuarista" name="pecuarista">
                        <option>Vanderlei</option>
                        <option>Enoque</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="preco">Preço R$:</label>
                    <input type="text" class="form-control" id="preco" name="preco">
                </div>
                <div class="col-md-6 form-group">
                    <label for="preco_final">Preço Final R$:</label>
                    <input type="text" class="form-control" id="preco_final" name="preco_final">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="prazo">Prazo:</label>
                    <select class="form-control" id="prazo" name="prazo">
                        <option>8 dias</option>
                        <option>10 dias</option>
                        <option>5 dias</option>
                        <option>30 dias</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="tipo_peso">Tipo de Peso:</label>
                    <select class="form-control" id="tipo_peso" name="tipo_peso">
                        <option>Peso Morto</option>
                        <option>Peso Vivo</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="comprador">Comprador:</label>
                    <select class="form-control" id="comprador" name="comprador">
                        <option>Lourenço</option>
                        <option>Zacarias</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="data_compra">Data da Compra:</label>
                    <input type="date" class="form-control" id="data_compra" name="data_compra">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="data_embarque">Data do Embarque:</label>
                    <input type="date" class="form-control" id="data_embarque" name="data_embarque">
                </div>
                <div class="col-md-6 form-group">
                    <label for="data_abate">Data do Abate:</label>
                    <input type="date" class="form-control" id="data_abate" name="data_abate">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
        </form>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>