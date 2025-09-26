<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['IdUsuarios']) || !isset($data['Password'])) {
    $response['message'] = 'Datos incompletos.';
    echo json_encode($response);
    exit;
}

$idUsuario = $data['IdUsuarios'];
$password = $data['Password'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $sql = "SELECT IdUsuarios, Nombre, Rol, Password, Estatus FROM Usuarios WHERE IdUsuarios = ?";
    $stmt = $conex->prepare($sql);
    $stmt->bind_param("s", $idUsuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['Estatus'] != '1') {
            $response['message'] = 'El usuario está inactivo.';
        } else if (password_verify($password, $user['Password'])) {
            $response['success'] = true;
            $response['data'] = [
                'IdUsuarios' => $user['IdUsuarios'],
                'Nombre' => $user['Nombre'],
                'Rol' => $user['Rol']
            ];
        } else {
            $response['message'] = 'Contraseña incorrecta.';
        }
    } else {
        $response['message'] = 'Usuario no encontrado.';
    }

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>
