<?php
header('Content-Type: application/json');
require_once 'db_Rutas.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Esta es la consulta correcta para LEER el historial. No pide ningún folio.
    $sql = "SELECT IdBitacoraCompleta, TiempoTotal, IdRuta, Usuario, FechaInicio, FechaFinalizacion 
            FROM BitacoraCompleta 
            ORDER BY FechaFinalizacion DESC 
            LIMIT 50";

    $stmt = $conex->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar la consulta: ' . $conex->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $completedRoutes = [];
    while ($row = $result->fetch_assoc()) {
        $completedRoutes[] = $row;
    }

    if (count($completedRoutes) > 0) {
        $response['success'] = true;
        $response['data'] = $completedRoutes;
    } else {
        $response['message'] = 'No se encontraron rutas en el historial.';
    }

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>

