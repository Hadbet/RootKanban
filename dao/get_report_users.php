<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'data' => []];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $sql = "SELECT DISTINCT Usuario FROM BitacoraCompleta WHERE Usuario IS NOT NULL AND Usuario != '' ORDER BY Usuario ASC";
    $result = $conex->query($sql);

    if($result) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $users;
    } else {
        throw new Exception("Error al obtener usuarios: " . $conex->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
} finally {
    if (isset($conex) && $conex->ping()) $conex->close();
}

echo json_encode($response);
?>
