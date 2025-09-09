<?php
// ***************************************************************
// CONEXÃO PDO: INCLUA AQUI O MESMO ARQUIVO DE CONEXÃO DO PRINCIPAL
// Exemplo: require_once 'sua_conexao.php';
// SEM ISSO, A BUSCA NÃO FUNCIONARÁ.
// ***************************************************************

// Define o cabeçalho da resposta como JSON, que é o formato que o JavaScript entende.
header('Content-Type: application/json');

// Validação da conexão
if (!isset($pdoS)) {
    http_response_code(500); // Erro interno do servidor
    echo json_encode(['error' => 'A conexão com o banco de dados não foi configurada neste arquivo.']);
    exit();
}

$searchTerm = $_GET['q'] ?? '';
$response = [];

if (strlen($searchTerm) >= 2) { // Executa a busca com no mínimo 2 caracteres
    try {
        $sql = "
            SELECT TOP 50 -- Limita a 50 resultados para ser sempre rápido
                A.Cod_cadastro, 
                A.Nome_cadastro
            FROM 
                tbCadastroGeral A
            WHERE 
                A.Tipo_cadastro = 'C'
                AND (
                    A.Nome_cadastro LIKE :searchTerm 
                    OR A.Cod_cadastro LIKE :searchTerm
                )
            ORDER BY 
                A.Nome_cadastro
        ";
        
        $stmt = $pdoS->prepare($sql);
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formata os dados para o formato que o campo de busca precisa
        foreach ($clients as $client) {
            $response[] = [
                'id'   => $client['Cod_cadastro'],
                'text' => $client['Cod_cadastro'] . ' - ' . $client['Nome_cadastro']
            ];
        }

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Erro na busca de clientes AJAX: " . $e->getMessage());
        $response = ['error' => 'Erro ao buscar dados.'];
    }
}

// Retorna os dados para o JavaScript
echo json_encode($response);