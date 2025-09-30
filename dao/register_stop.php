<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'message' => '', 'routeFinished' => false];
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos de entrada
if (!$data || !isset($data['ruta'], $data['parada'], $data['folioRuta'], $data['usuario'], $data['totalStops'])) {
    $response['message'] = 'Datos incompletos.';
    echo json_encode($response);
    exit;
}

$ruta = $data['ruta'];
$parada = $data['parada'];
$folioRuta = $data['folioRuta'];
$usuario = $data['usuario'];
$comentarios = $data['comentarios'] ?? '';
$totalStops = (int)$data['totalStops'];
$estatus = 1;

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->begin_transaction();

    $Object = new DateTime();
    $Object->setTimezone(new DateTimeZone('America/Denver')); // Considera usar 'America/Mexico_City' si aplica
    $DateAndTime = $Object->format("Y-m-d H:i:s");

    // 1. Insertar la nueva parada
    $sql_insert = "INSERT INTO BitacoraParadas (Ruta, Parada, Fecha, Estatus, Usuario, Comentarios, FolioRuta) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conex->prepare($sql_insert);
    if ($stmt_insert === false) throw new Exception('Error al preparar inserción: ' . $conex->error);

    $stmt_insert->bind_param("iississ", $ruta, $parada,$DateAndTime, $estatus, $usuario, $comentarios, $folioRuta);

    if (!$stmt_insert->execute()) {
        throw new Exception('Error al registrar la parada: ' . $stmt_insert->error);
    }
    $stmt_insert->close();

    // 2. Verificar si es la última parada
    if ((int)$parada === $totalStops) {
        $response['routeFinished'] = true;

        // 3. Si es la última, actualizar el estatus de todas las paradas de ese FolioRuta a 0
        $sql_update = "UPDATE BitacoraParadas SET Estatus = 0 WHERE FolioRuta = ?";
        $stmt_update = $conex->prepare($sql_update);
        if ($stmt_update === false) throw new Exception('Error al preparar actualización: ' . $conex->error);

        $stmt_update->bind_param("s", $folioRuta);

        if (!$stmt_update->execute()) {
            throw new Exception('Error al finalizar la ruta: ' . $stmt_update->error);
        }
        $stmt_update->close();
    }

    // Si todo fue bien, confirmar la transacción
    $conex->commit();
    $response['success'] = true;
    $response['message'] = 'Parada registrada correctamente.';

} catch (Exception $e) {
    if (isset($conex) && $conex->ping()) {
        $conex->rollback();
    }
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
} finally {
    if (isset($conex) && $conex->ping()) {
        $conex->close();
    }
}

echo json_encode($response);
?>

