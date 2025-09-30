<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['folioRuta'])) {
    $response['message'] = 'Folio de ruta no proporcionado.';
    echo json_encode($response);
    exit;
}

$folioRuta = $data['folioRuta'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->begin_transaction();

    // 1. Obtener la fecha de la primera parada (FechaInicio), la ruta y el usuario.
    $sql_select = "SELECT MIN(Fecha) as startTime, Ruta, Usuario FROM BitacoraParadas WHERE FolioRuta = ? GROUP BY Ruta, Usuario";
    $stmt_select = $conex->prepare($sql_select);
    if($stmt_select === false) throw new Exception("Error al preparar la consulta de inicio: " . $conex->error);

    $stmt_select->bind_param("s", $folioRuta);
    $stmt_select->execute();
    $result = $stmt_select->get_result()->fetch_assoc();
    $stmt_select->close();

    if (!$result) {
        throw new Exception("No se encontraron paradas para el folio: " . $folioRuta);
    }

    $startTime = $result['startTime'];
    $usuario = $result['Usuario'];

    // 2. Calcular el tiempo total en minutos desde la primera parada hasta ahora.
    $start_time_obj = new DateTime($startTime);
    $end_time_obj = new DateTime(); // Fecha y hora actual para FechaFinalizacion
    $start_time_obj->setTimezone(new DateTimeZone('America/Denver'));
    $end_time_obj->setTimezone(new DateTimeZone('America/Denver'));
    $interval = $start_time_obj->diff($end_time_obj);
    $tiempoTotal = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

    $Object = new DateTime();
    $Object->setTimezone(new DateTimeZone('America/Denver')); // Considera usar 'America/Mexico_City' si aplica
    $DateAndTime = $Object->format("Y-m-d H:i:s");

    // 3. Insertar el registro en la bitácora completa
    $sql_insert = "INSERT INTO BitacoraCompleta (TiempoTotal, IdRuta, Usuario, FechaInicio, FechaFinalizacion) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conex->prepare($sql_insert);
    if($stmt_insert === false) throw new Exception("Error al preparar la inserción final: " . $conex->error);

    $stmt_insert->bind_param("issss", $tiempoTotal, $folioRuta, $usuario, $startTime,$DateAndTime);

    if (!$stmt_insert->execute()) {
        throw new Exception("Error al guardar la bitácora completa: " . $stmt_insert->error);
    }

    $response['data'] = ['tiempoTotal' => $tiempoTotal];
    $response['success'] = true;
    $response['message'] = 'Ruta completada y registrada.';

    $conex->commit();
    $stmt_insert->close();

} catch (Exception $e) {
    if (isset($conex) && $conex->ping()) $conex->rollback();
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
} finally {
    if (isset($conex) && $conex->ping()) $conex->close();
}

echo json_encode($response);
?>

