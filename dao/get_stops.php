<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

// Validar que se recibió el parámetro de ruta
if (!isset($_GET['ruta'])) {
    $response['message'] = 'Parámetro de ruta no especificado.';
    echo json_encode($response);
    exit;
}

$rutaId = $_GET['ruta'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Usar sentencias preparadas para prevenir inyección SQL
    $sql = "SELECT Ruta, Parada, Fecha, Usuario FROM BitacoraParadas WHERE Ruta = ? AND Estatus = 1";

    $stmt = $conex->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar la consulta: ' . $conex->error);
    }

    $stmt->bind_param("i", $rutaId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $stops = [];
        while ($row = $result->fetch_assoc()) {
            $stops[] = $row;
        }

        if (count($stops) > 0) {
            $response['success'] = true;
            $response['data'] = $stops;
        } else {
            $response['message'] = 'No hay paradas activas para esta ruta.';
        }
    } else {
        $response['message'] = 'Error en la consulta: ' . $stmt->error;
    }

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>

