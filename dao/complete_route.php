<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

// CORRECCIÓN: Establecer la zona horaria solicitada.
date_default_timezone_set('America/Denver');

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

    // 1. Obtener la fecha de la primera parada (FechaInicio) y el usuario.
    // Se agrupa por usuario para asegurar que tomamos los datos de un solo operador por ruta.
    $sql_select = "SELECT MIN(Fecha) as startTime, Usuario FROM BitacoraParadas WHERE FolioRuta = ? GROUP BY Usuario";
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

    // CORRECCIÓN: Calcular el tiempo total en minutos de forma precisa.
    // Ambos objetos DateTime ahora usarán la zona horaria definida al inicio del script.
    $start_time_obj = new DateTime($startTime);
    $end_time_obj = new DateTime(); // Fecha y hora actual

    $interval = $start_time_obj->diff($end_time_obj);
    // Cálculo preciso de la diferencia total en minutos.
    $tiempoTotal = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

    // Formatear la fecha de finalización para la base de datos.
    $DateAndTime = $end_time_obj->format("Y-m-d H:i:s");


    // 3. Insertar el registro en la bitácora completa
    $sql_insert = "INSERT INTO BitacoraCompleta (TiempoTotal, IdRuta, Usuario, FechaInicio, FechaFinalizacion) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conex->prepare($sql_insert);
    if($stmt_insert === false) throw new Exception("Error al preparar la inserción final: " . $conex->error);

    $stmt_insert->bind_param("issss", $tiempoTotal, $folioRuta, $usuario, $startTime, $DateAndTime);

    if (!$stmt_insert->execute()) {
        throw new Exception("Error al guardar la bitácora completa: " . $stmt_insert->error);
    }
    $stmt_insert->close();

    $conex->commit();

    $response['data'] = ['tiempoTotal' => $tiempoTotal];
    $response['success'] = true;
    $response['message'] = 'Ruta completada y registrada.';

} catch (Exception $e) {
    if (isset($conex) && $conex->ping()) $conex->rollback();
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
} finally {
    if (isset($conex) && $conex->ping()) $conex->close();
}

echo json_encode($response);
?>

