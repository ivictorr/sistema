<?php
header('Content-Type: application/json');
require('./configuracao/conexao.php');

try {
    // Ativar o modo de erro do PDO
    $pdoM->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Usar a data atual do sistema se nada for enviado no POST
    $dataSelecionada = $_POST['data'] ?? date('Y-m-d');

    // Query SQL com verificação de NULL nos campos tempo1 e tempo2
$sql = "SELECT A.motivo, A.tempo1, A.tempo2, B.motivo AS descricao
FROM paradaabate A
LEFT JOIN motivosparada B ON A.motivo = B.id
WHERE DATE(A.data) = '2024-12-17'
AND A.tempo1 IS NOT NULL
AND A.tempo2 IS NOT NULL";

    // Preparar e executar a query
    $stmt = $pdoM->prepare($sql);
    $stmt->execute();
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retornar os dados ou mensagem vazia
    if (empty($dados)) {
        echo json_encode(["status" => "empty", "message" => "Nenhum dado encontrado para a data selecionada: " . $dataSelecionada]);
    } else {
        echo json_encode(["status" => "success", "data" => $dados]);
    }
} catch (PDOException $e) {
    // Tratamento de erros no SQL ou conexão
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
