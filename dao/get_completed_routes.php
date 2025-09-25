<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para obtener las rutas completadas, ordenadas por la más reciente.
    // He añadido un campo 'FechaFinalizacion' que asumo que tienes. Si no, puedes quitarlo.
    // Si no tienes un campo de fecha, lo he nombrado IdBitacoraCompleta para ordenar.
    // Es MUY RECOMENDABLE añadir una columna de tipo DATETIME a BitacoraCompleta para registrar cuándo se finalizó.
    $sql = "SELECT IdBitacoraCompleta, TiempoTotal, IdRuta, Usuario, FechaFinalizacion 
            FROM BitacoraCompleta 
            ORDER BY IdBitacoraCompleta DESC 
            LIMIT 50"; // Limitar a las últimas 50 para no sobrecargar

    $stmt = $conex->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar la consulta: ' . $conex->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
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
