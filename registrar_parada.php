<?php
require('./configuracao/conexao.php');

// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'] ?? null; // Formato YYYY-MM-DD HH:MM:SS
    $horaInicio = $_POST['horaInicio'] ?? null; // Formato YYYY-MM-DD HH:MM:SS
    $horaParada = $_POST['horaParada'] ?? null; // Formato YYYY-MM-DD HH:MM:SS
    $motivo = $_POST['motivo'] ?? null;

    // Validação dos dados
    if (!$data || !$horaInicio || !$horaParada || !$motivo) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados incompletos. Verifique e tente novamente.']);
        exit;
    }

    // Inserção no banco de dados
    try {
        $sql = "INSERT INTO paradaabate (data, tempo1, tempo2, motivo) VALUES (:data, :tempo1, :tempo2, :motivo)";
        $stmt = $pdoM->prepare($sql);
        $stmt->execute([
            ':data' => $data,
            ':tempo1' => $horaInicio,
            ':tempo2' => $horaParada,
            ':motivo' => $motivo,
        ]);
        echo json_encode(['success' => true, 'message' => 'Parada registrada com sucesso.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao registrar a parada: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405); // Método não permitido
    echo json_encode(['error' => 'Método não permitido. Use POST.']);
}
?>