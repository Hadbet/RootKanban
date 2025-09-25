<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['folioRuta'])) {
    $response['message'] = 'FolioRuta no proporcionado.';
    echo json_encode($response);
    exit;
}

$folioRuta = $data['folioRuta'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->begin_transaction();

    // 1. Obtener la primera parada para calcular el tiempo
    $sql_select = "SELECT MIN(Fecha) as startTime, Ruta, Usuario FROM BitacoraParadas WHERE FolioRuta = ? GROUP BY Ruta, Usuario";
    $stmt_select = $conex->prepare($sql_select);
    if ($stmt_select === false) throw new Exception('Error al preparar consulta de tiempo: ' . $conex->error);

    $stmt_select->bind_param("s", $folioRuta);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('No se encontró la ruta para este folio.');
    }

    $routeInfo = $result->fetch_assoc();
    $startTime = new DateTime($routeInfo['startTime']);
    $endTime = new DateTime(); // Hora actual
    $diff = $endTime->getTimestamp() - $startTime->getTimestamp();
    $tiempoTotalMinutos = round($diff / 60);

    $idRuta = $routeInfo['Ruta'];
    $usuario = $routeInfo['Usuario'];
    $stmt_select->close();

    // 2. Insertar en la tabla BitacoraCompleta
    $sql_insert = "INSERT INTO BitacoraCompleta (TiempoTotal, IdRuta, Usuario) VALUES (?, ?, ?)";
    $stmt_insert = $conex->prepare($sql_insert);
    if ($stmt_insert === false) throw new Exception('Error al preparar inserción completa: ' . $conex->error);

    $stmt_insert->bind_param("iis", $tiempoTotalMinutos, $idRuta, $usuario);
    if (!$stmt_insert->execute()) {
        throw new Exception('Error al guardar la bitácora completa: ' . $stmt_insert->error);
    }
    $stmt_insert->close();

    $conex->commit();
    $response['success'] = true;
    $response['message'] = 'Bitácora completa guardada.';
    $response['data'] = ['tiempoTotal' => $tiempoTotalMinutos];

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

