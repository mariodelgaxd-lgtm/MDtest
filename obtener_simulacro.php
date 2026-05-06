<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado."]);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$sql_racha = "SELECT racha FROM usuarios WHERE id_usuario = ?";
$stmt_racha = $conn->prepare($sql_racha);
$stmt_racha->bind_param("i", $id_usuario);
$stmt_racha->execute();
$racha_actual = $stmt_racha->get_result()->fetch_assoc()['racha'];
$stmt_racha->close();

if ($racha_actual < 30) {
    http_response_code(403);
    echo json_encode(["error" => "No tienes la racha suficiente para este simulacro."]);
    exit;
}

$sql = "SELECT id_pregunta, texto, opcion_a, opcion_b, opcion_c, correcta 
        FROM preguntas 
        ORDER BY RAND() 
        LIMIT 30";

$result = $conn->query($sql);
$preguntas_json = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $correcta_index = 0; 
        if ($row['correcta'] == 'B') $correcta_index = 1;
        elseif ($row['correcta'] == 'C') $correcta_index = 2;
        $preguntas_json[] = [ "id_pregunta" => $row['id_pregunta'], "pregunta" => $row['texto'], "opciones" => [$row['opcion_a'], $row['opcion_b'], $row['opcion_c']], "respuesta_correcta_index" => $correcta_index ];
    }
}
$conn->close(); 
header('Content-Type: application/json');
echo json_encode($preguntas_json);
?>