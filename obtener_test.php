<?php
session_start();
require_once "conexion.php";

// Verificación de autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado. Debes iniciar sesión."]);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$modo = $_GET['modo'] ?? 'normal';
$preguntas_json = [];

if ($modo === 'repaso') {
    // Modo repaso rápido: solo preguntas falladas
    $sql = "SELECT p.id_pregunta, p.texto, p.opcion_a, p.opcion_b, p.opcion_c, p.correcta 
            FROM fallos_usuario fu
            JOIN preguntas p ON fu.id_pregunta = p.id_pregunta
            WHERE fu.id_usuario = ? 
            ORDER BY RAND() 
            LIMIT 30";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $correcta_index = 0; 
                if ($row['correcta'] == 'B') $correcta_index = 1;
                elseif ($row['correcta'] == 'C') $correcta_index = 2;

                $preguntas_json[] = [
                    "id_pregunta" => $row['id_pregunta'], 
                    "pregunta" => $row['texto'],
                    "opciones" => [$row['opcion_a'], $row['opcion_b'], $row['opcion_c']],
                    "respuesta_correcta_index" => $correcta_index,
                    "tipo" => "repaso"
                ];
            }
        } else {
            echo json_encode(["error" => "No tienes preguntas falladas para repasar."]);
            $stmt->close();
            $conn->close();
            exit();
        }
        $stmt->close();
    }
} else {
    // Modo normal: algoritmo de repaso espaciado
    // PASO 1: Obtener preguntas de repaso espaciado (fallos pendientes de repasar hoy)
    $sql_repaso = "SELECT p.id_pregunta, p.texto, p.opcion_a, p.opcion_b, p.opcion_c, p.correcta 
                   FROM fallos_usuario fu
                   JOIN preguntas p ON fu.id_pregunta = p.id_pregunta
                   WHERE fu.id_usuario = ? 
                   AND (fu.fecha_proximo_repaso IS NULL OR fu.fecha_proximo_repaso <= NOW())
                   ORDER BY fu.nivel_repaso ASC, fu.ultima_vez ASC
                   LIMIT 15";

    $preguntas_repaso = [];
    if ($stmt_repaso = $conn->prepare($sql_repaso)) {
        $stmt_repaso->bind_param("i", $id_usuario);
        $stmt_repaso->execute();
        $result_repaso = $stmt_repaso->get_result();
        
        while ($row = $result_repaso->fetch_assoc()) {
            $correcta_index = 0;
            if ($row['correcta'] == 'B') $correcta_index = 1;
            elseif ($row['correcta'] == 'C') $correcta_index = 2;
            
            $preguntas_repaso[] = [
                "id_pregunta" => $row['id_pregunta'],
                "pregunta" => $row['texto'],
                "opciones" => [$row['opcion_a'], $row['opcion_b'], $row['opcion_c']],
                "respuesta_correcta_index" => $correcta_index,
                "tipo" => "repaso"
            ];
        }
        $stmt_repaso->close();
    }

    // PASO 2: Obtener preguntas nuevas (que nunca ha respondido)
    $cantidad_repaso = count($preguntas_repaso);
    $cantidad_nuevas = 30 - $cantidad_repaso;

    $preguntas_ids_repaso = array_column($preguntas_repaso, 'id_pregunta');
    $ids_excluir = !empty($preguntas_ids_repaso) ? implode(',', $preguntas_ids_repaso) : '0';

    $sql_nuevas = "SELECT id_pregunta, texto, opcion_a, opcion_b, opcion_c, correcta 
                   FROM preguntas 
                   WHERE id_pregunta NOT IN (
                       SELECT id_pregunta 
                       FROM respuestas_test
                       JOIN test ON respuestas_test.id_test = test.id_test
                       WHERE test.id_usuario = ?
                   )
                   AND id_pregunta NOT IN ($ids_excluir)
                   ORDER BY RAND() 
                   LIMIT ?";

    $preguntas_nuevas = [];
    if ($stmt_nuevas = $conn->prepare($sql_nuevas)) {
        $stmt_nuevas->bind_param("ii", $id_usuario, $cantidad_nuevas);
        $stmt_nuevas->execute();
        $result_nuevas = $stmt_nuevas->get_result();
        
        while ($row = $result_nuevas->fetch_assoc()) {
            $correcta_index = 0;
            if ($row['correcta'] == 'B') $correcta_index = 1;
            elseif ($row['correcta'] == 'C') $correcta_index = 2;
            
            $preguntas_nuevas[] = [
                "id_pregunta" => $row['id_pregunta'],
                "pregunta" => $row['texto'],
                "opciones" => [$row['opcion_a'], $row['opcion_b'], $row['opcion_c']],
                "respuesta_correcta_index" => $correcta_index,
                "tipo" => "nueva"
            ];
        }
        $stmt_nuevas->close();
    }

    // PASO 3: Si no tenemos 30 preguntas, completar con preguntas ya respondidas aleatoriamente
    $cantidad_actual = count($preguntas_repaso) + count($preguntas_nuevas);
    if ($cantidad_actual < 30) {
        $cantidad_random = 30 - $cantidad_actual;
        $ids_excluir_total = !empty($preguntas_ids_repaso) ? implode(',', $preguntas_ids_repaso) : '0';
        
        $sql_random = "SELECT DISTINCT p.id_pregunta, p.texto, p.opcion_a, p.opcion_b, p.opcion_c, p.correcta 
                       FROM preguntas p
                       JOIN respuestas_test rt ON p.id_pregunta = rt.id_pregunta
                       JOIN test t ON rt.id_test = t.id_test
                       WHERE t.id_usuario = ?
                       AND p.id_pregunta NOT IN ($ids_excluir_total)
                       ORDER BY RAND() 
                       LIMIT ?";
        
        if ($stmt_random = $conn->prepare($sql_random)) {
            $stmt_random->bind_param("ii", $id_usuario, $cantidad_random);
            $stmt_random->execute();
            $result_random = $stmt_random->get_result();
            
            while ($row = $result_random->fetch_assoc()) {
                $correcta_index = 0;
                if ($row['correcta'] == 'B') $correcta_index = 1;
                elseif ($row['correcta'] == 'C') $correcta_index = 2;
                
    $preguntas_nuevas[] = [
                "id_pregunta" => $row['id_pregunta'],
                "pregunta" => $row['texto'],
                "opciones" => [$row['opcion_a'], $row['opcion_b'], $row['opcion_c']],
                "respuesta_correcta_index" => $correcta_index,
                "tipo" => "repaso_general"
            ];
            }
            $stmt_random->close();
        }
    }

    // Mezclar todas las preguntas
    $preguntas_json = array_merge($preguntas_repaso, $preguntas_nuevas);
    shuffle($preguntas_json);
}

// Verificar si tenemos preguntas
if (empty($preguntas_json)) {
    echo json_encode(["error" => "¡Felicidades! Has completado todas las preguntas de nuestra base de datos."]);
    $conn->close();
    exit();
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($preguntas_json);
?>
