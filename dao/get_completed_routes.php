<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta ajustada a tu tabla: IdBitacoraCompleta, TiempoTotal, IdRuta, Usuario
    // Se ordena por IdBitacoraCompleta para mostrar las rutas más recientes primero.
    $sql = "SELECT IdBitacoraCompleta, TiempoTotal, IdRuta, Usuario 
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

