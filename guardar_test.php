<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "No autorizado."]);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$data = json_decode(file_get_contents("php://input"), true);

$puntuacion = $data['puntuacion'];
$resultados = $data['resultados'];
$tiempo_segundos = $data['tiempo_segundos'] ?? 0;
$total_preguntas = count($resultados);
$fallos = $total_preguntas - $puntuacion;

$nueva_racha = 0;
$alerta_30_dias = false;
$alerta_50_dias = false;
$logros_nuevos = [];

$conn->begin_transaction();

try {
    // Obtener datos actuales del usuario
    $sql_user = "SELECT racha, racha_perdida, fecha_ultimo_test, tests_completados, id_nivel FROM usuarios WHERE id_usuario = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $id_usuario);
    $stmt_user->execute();
    $user_data = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();
    
    $racha_actual = $user_data['racha'] ?? 0;
    $racha_perdida = $user_data['racha_perdida'] ?? 0;
    $fecha_ultimo_test = $user_data['fecha_ultimo_test'] ?? null;
    $tests_completados = $user_data['tests_completados'] ?? 0;
    $id_nivel_actual = $user_data['id_nivel'] ?? 1;

    // Insertar test
    $sql_test = "INSERT INTO test (id_usuario, puntuacion) VALUES (?, ?)";
    $stmt_test = $conn->prepare($sql_test);
    $stmt_test->bind_param("ii", $id_usuario, $puntuacion);
    $stmt_test->execute();
    $id_test = $conn->insert_id;
    $stmt_test->close();

    // Insertar respuestas
    $sql_respuestas = "INSERT INTO respuestas_test (id_test, id_pregunta, respuesta_usuario, correcta) VALUES (?, ?, ?, ?)";
    $stmt_respuestas = $conn->prepare($sql_respuestas);

    // Repaso espaciado: al fallar, nivel 0 y repaso mañana
    $sql_fallos_bd = "INSERT INTO fallos_usuario (id_usuario, id_pregunta, veces_fallada, ultima_vez, nivel_repaso, fecha_proximo_repaso) VALUES (?, ?, 1, NOW(), 0, DATE_ADD(NOW(), INTERVAL 1 DAY))
                   ON DUPLICATE KEY UPDATE veces_fallada = veces_fallada + 1, ultima_vez = NOW(), nivel_repaso = 0, fecha_proximo_repaso = DATE_ADD(NOW(), INTERVAL 1 DAY)";
    $stmt_fallos_bd = $conn->prepare($sql_fallos_bd);

    // Repaso espaciado: al acertar, subir nivel y retrasar repaso
    $sql_acierto_bd = "UPDATE fallos_usuario SET 
        fecha_proximo_repaso = CASE 
            WHEN nivel_repaso = 0 THEN DATE_ADD(NOW(), INTERVAL 3 DAY)
            WHEN nivel_repaso = 1 THEN DATE_ADD(NOW(), INTERVAL 7 DAY)
            ELSE DATE_ADD(NOW(), INTERVAL 30 DAY) 
        END,
        nivel_repaso = nivel_repaso + 1
        WHERE id_usuario = ? AND id_pregunta = ?";
    $stmt_acierto_bd = $conn->prepare($sql_acierto_bd);
    
    $preguntas_repaso_acertadas = 0;
    $preguntas_repaso_total = 0;
    
    foreach ($resultados as $res) {
        $id_pregunta = $res['id_pregunta'];
        $respuesta_usuario = $res['respuesta_usuario'];
        $correcta = $res['correcta'];
        
        $stmt_respuestas->bind_param("iisi", $id_test, $id_pregunta, $respuesta_usuario, $correcta);
        $stmt_respuestas->execute();
        
        // Verificar si es pregunta de repaso (está en fallos_usuario)
        $sql_check_repaso = "SELECT id_fallo FROM fallos_usuario WHERE id_usuario = ? AND id_pregunta = ? AND fecha_proximo_repaso <= NOW()";
        $stmt_check_repaso = $conn->prepare($sql_check_repaso);
        $stmt_check_repaso->bind_param("ii", $id_usuario, $id_pregunta);
        $stmt_check_repaso->execute();
        $es_repaso = $stmt_check_repaso->get_result()->num_rows > 0;
        $stmt_check_repaso->close();
        
        if ($es_repaso) {
            $preguntas_repaso_total++;
            if ($correcta) {
                $preguntas_repaso_acertadas++;
            }
        }
        
        if ($correcta == false) {
            $stmt_fallos_bd->bind_param("ii", $id_usuario, $id_pregunta);
            $stmt_fallos_bd->execute();
        } else {
            $stmt_acierto_bd->bind_param("ii", $id_usuario, $id_pregunta);
            $stmt_acierto_bd->execute();
        }
    }
    $stmt_respuestas->close();
    $stmt_fallos_bd->close();
    $stmt_acierto_bd->close();

    // Actualizar racha y estadísticas
    $hoy = date('Y-m-d');
    $tests_completados++;
    
    if ($fallos <= 3) {
        // Verificar si es recuperación de racha
        if ($racha_perdida > 0) {
            $nueva_racha = $racha_perdida + 1;
            $racha_perdida = 0;
        } else {
            $nueva_racha = $racha_actual + 1;
        }
        
        if ($nueva_racha == 30) {
            $alerta_30_dias = true;
        }
        if ($nueva_racha == 50) {
            $alerta_50_dias = true;
        }
    } else {
        // Perder racha
        $racha_perdida = $racha_actual;
        $nueva_racha = 0;
    }

    // Actualizar usuario
    $sql_set_user = "UPDATE usuarios SET racha = ?, racha_perdida = ?, fecha_ultimo_test = ?, tests_completados = ? WHERE id_usuario = ?";
    $stmt_set_user = $conn->prepare($sql_set_user);
    $stmt_set_user->bind_param("iisii", $nueva_racha, $racha_perdida, $hoy, $tests_completados, $id_usuario);
    $stmt_set_user->execute();
    $stmt_set_user->close();

    // Actualizar nivel del usuario
    $sql_nivel = "SELECT id_nivel FROM niveles WHERE tests_minimos <= ? ORDER BY tests_minimos DESC LIMIT 1";
    $stmt_nivel = $conn->prepare($sql_nivel);
    $stmt_nivel->bind_param("i", $tests_completados);
    $stmt_nivel->execute();
    $nivel_result = $stmt_nivel->get_result()->fetch_assoc();
    $stmt_nivel->close();
    
    if ($nivel_result && $nivel_result['id_nivel'] != $id_nivel_actual) {
        $sql_update_nivel = "UPDATE usuarios SET id_nivel = ? WHERE id_usuario = ?";
        $stmt_update_nivel = $conn->prepare($sql_update_nivel);
        $stmt_update_nivel->bind_param("ii", $nivel_result['id_nivel'], $id_usuario);
        $stmt_update_nivel->execute();
        $stmt_update_nivel->close();
    }

    // COMPROBACIÓN DE LOGROS
    $check_logros = [];
    
    // Logro 1: Primera Sangre (Primer test completado)
    if ($tests_completados == 1) {
        $check_logros[1] = true;
    }
    
    // Logro 2: Racha de Fuego (5 aprobados seguidos)
    if ($nueva_racha >= 5) {
        $check_logros[2] = true;
    }
    
    // Logro 3: Test Perfecto (0 fallos)
    if ($fallos == 0) {
        $check_logros[3] = true;
    }
    
    // Logro 5: Comeback (recuperar racha)
    if ($racha_perdida == 0 && $racha_actual >= 5 && $fallos <= 3) {
        $check_logros[5] = true;
    }
    
    // Logro 7: Perfecto en Repaso
    if ($preguntas_repaso_total > 0 && $preguntas_repaso_acertadas == $preguntas_repaso_total) {
        $check_logros[7] = true;
    }
    
    // Logro 8: Superviviente (exactamente 3 fallos)
    if ($fallos == 3) {
        $check_logros[8] = true;
    }
    
    // Logro 9: Velocista (menos de 5 minutos = 300 segundos)
    if ($tiempo_segundos > 0 && $tiempo_segundos < 300) {
        $check_logros[9] = true;
    }
    
    // Logro 11: Veterano (50 tests)
    if ($tests_completados >= 50) {
        $check_logros[11] = true;
    }
    
    // Logro 12: Inmortal (50 días de racha)
    if ($nueva_racha >= 50) {
        $check_logros[12] = true;
    }
    
    // Verificar constancia (7 días seguidos)
    if ($nueva_racha >= 7) {
        $sql_constancia = "SELECT COUNT(DISTINCT DATE(fecha)) as dias FROM test WHERE id_usuario = ? AND fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt_constancia = $conn->prepare($sql_constancia);
        $stmt_constancia->bind_param("i", $id_usuario);
        $stmt_constancia->execute();
        $dias_constancia = $stmt_constancia->get_result()->fetch_assoc()['dias'];
        $stmt_constancia->close();
        
        if ($dias_constancia >= 7) {
            $check_logros[6] = true;
        }
    }
    
    // Insertar logros conseguidos
    $sql_insert_logro = "INSERT IGNORE INTO logros_usuario (id_usuario, id_logro, fecha_conseguido) VALUES (?, ?, NOW())";
    $stmt_insert_logro = $conn->prepare($sql_insert_logro);
    
    foreach ($check_logros as $id_logro => $condicion) {
        if ($condicion) {
            $stmt_insert_logro->bind_param("ii", $id_usuario, $id_logro);
            $stmt_insert_logro->execute();
            
            // Obtener nombre del logro
            $sql_nombre_logro = "SELECT nombre FROM logros WHERE id_logro = ?";
            $stmt_nombre = $conn->prepare($sql_nombre_logro);
            $stmt_nombre->bind_param("i", $id_logro);
            $stmt_nombre->execute();
            $nombre_logro = $stmt_nombre->get_result()->fetch_assoc()['nombre'];
            $stmt_nombre->close();
            
            $logros_nuevos[] = $nombre_logro;
        }
    }
    $stmt_insert_logro->close();

    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        "success" => true, 
        "id_test" => $id_test,
        "nueva_racha" => $nueva_racha,
        "fallos" => $fallos,
        "alerta_30_dias" => $alerta_30_dias,
        "alerta_50_dias" => $alerta_50_dias,
        "logros_nuevos" => $logros_nuevos,
        "tests_completados" => $tests_completados
    ]);

} catch (Exception $e) {
    $conn->rollback();
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

$conn->close();
?>
