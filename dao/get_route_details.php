<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'message' => '', 'data' => []];

$idRuta = $_GET['idRuta'] ?? null;

if (empty($idRuta)) {
    $response['message'] = 'No se proporcionó un ID de ruta.';
    echo json_encode($response);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $sql = "SELECT Ruta, Parada, Fecha,Usuario FROM BitacoraParadas WHERE FolioRuta = ? ORDER BY Fecha ASC";
    $stmt = $conex->prepare($sql);
    if ($stmt === false) throw new Exception('Error al preparar la consulta: ' . $conex->error);

    $stmt->bind_param("s", $idRuta);
    $stmt->execute();
    $result = $stmt->get_result();

    $stops = [];
    while ($row = $result->fetch_assoc()) {
        $stops[] = $row;
    }
    $stmt->close();

    $response['success'] = true;
    $response['data'] = $stops;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
} finally {
    if (isset($conex) && $conex->ping()) {
        $conex->close();
    }
}

echo json_encode($response);
?>
