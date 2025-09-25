<?php
header('Content-Type: application/json');
include_once('db/db_Inventario.php'); // Asegúrate de que este archivo de conexión exista y funcione

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // No seleccionamos la contraseña por seguridad
    $sql = "SELECT `IdUsuario`, `Username`, `Nombre`, `Rol`, `Estatus` FROM `Usuario`";
    $result = $conex->query($sql);

    if ($result) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $users;
    } else {
        $response['message'] = 'Error en la consulta: ' . $conex->error;
    }

    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error de conexión a la base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>
