<?php
// Previene que la página se corte si Gemini tarda
set_time_limit(300); // 5 minutos

// 1. --- CONFIGURACIÓN ---
$apiKey = "AIzaSyCkdJcxKLw24RZFkWxk1CA_d43bYmnfeYs"; // ¡¡ALERTA!! Tu clave sigue aquí.
$nombre_del_modelo = "models/gemini-2.5-flash"; 

// ¿Cuántas preguntas queremos que genere esta vez?
$cantidad_preguntas = 30;

// 2. --- ¡EL PROMPT PARA LAS PREGUNTAS! ---
$prompt = "
    Eres un experto en seguridad vial y reglamentación de la DGT en España.
    Tu tarea es generar $cantidad_preguntas preguntas tipo test para el permiso B.
    
    IMPORTANTE:
    1. Las preguntas deben ser variadas y cubrir diferentes temas (velocidad, señales, prioridad, mecánica, etc.).
    2. Las preguntas deben ser diferentes entre sí.
    3. Devuelve *exclusivamente* un array JSON. No escribas nada antes ni después del JSON.
    
    Usa la siguiente estructura JSON para cada pregunta:
    {
      \"texto\": \"Texto de la pregunta...\",
      \"opcion_a\": \"Texto de la opción A\",
      \"opcion_b\": \"Texto de la opción B\",
      \"opcion_c\": \"Texto de la opción C\",
      \"correcta\": \"A\", 
      \"tema\": \"Nombre del tema (ej. Señales, Velocidad, Prioridad)\"
    }
    
    (Nota: 'correcta' debe ser solo la letra 'A', 'B' o 'C').
";

// 3. --- LLAMADA cURL A LA API ---
$url = "https://generativelanguage.googleapis.com/v1beta/" . $nombre_del_modelo . ":generateContent?key=" . $apiKey;
$data = [ "contents" => [ [ "parts" => [ ["text" => $prompt] ] ] ] ];
$jsonData = json_encode($data);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);

// Arreglo SSL para XAMPP/Laragon
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
curl_close($ch);

// 4. --- PROCESAR RESPUESTA Y GENERAR SQL ---
header('Content-Type: text/plain; charset=utf-8');

if ($response === false) {
    echo "-- ERROR: No se pudo contactar con la API de Gemini (Error de cURL).";
    exit;
}

$responseData = json_decode($response, true);

if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    
    $jsonText = $responseData['candidates'][0]['content']['parts'][0]['text'];
    $jsonText = str_replace(["```json", "```"], "", $jsonText); // Limpiar
    
    // CAMBIO: La variable se llama $preguntas, no $tema_info
    $preguntas = json_decode($jsonText, true); 
    
    if ($preguntas === null) {
        echo "-- ERROR: La IA no devolvió un JSON válido.\n";
        echo "-- Respuesta recibida:\n" . $jsonText;
        exit;
    }
    
    echo "-- ==============================================\n";
    echo "-- SCRIPT GENERADO CON IA ($cantidad_preguntas preguntas)\n";
    echo "-- Copia y pega esto en HeidiSQL para tu tabla 'preguntas'.\n";
    echo "-- ==============================================\n\n";

    // Bucle que crea las sentencias INSERT
    foreach ($preguntas as $p) {
        // Escapamos las comillas simples para evitar errores de SQL
        $texto = addslashes($p['texto']);
        $op_a = addslashes($p['opcion_a']);
        $op_b = addslashes($p['opcion_b']);
        $op_c = addslashes($p['opcion_c']);
        $correcta = addslashes($p['correcta']);
        $tema = addslashes($p['tema']);
        
        echo "INSERT INTO preguntas (texto, opcion_a, opcion_b, opcion_c, correcta, tema, fuente) VALUES \n";
        echo "('$texto', '$op_a', '$op_b', '$op_c', '$correcta', '$tema', 'IA-Gemini');\n\n";
    }

} else {
    echo "-- ERROR: La respuesta de la API no tuvo el formato esperado.\n";
    print_r($responseData);
}
?>