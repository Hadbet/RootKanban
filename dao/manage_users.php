<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'message' => ''];
$action = $_REQUEST['action'] ?? null;

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    switch ($action) {
        case 'create':
            // Lógica para crear un nuevo usuario
            $id = $_POST['IdUsuarios'];
            $nombre = $_POST['Nombre'];
            $correo = $_POST['Correo'];
            $pass = password_hash($_POST['Password'], PASSWORD_DEFAULT);
            $rol = $_POST['Rol'];
            $estatus = 1; // Siempre activo al crear

            $sql = "INSERT INTO Usuarios (IdUsuarios, Nombre, Correo, Password, Rol, Estatus) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conex->prepare($sql);
            $stmt->bind_param("ssssii", $id, $nombre, $correo, $pass, $rol, $estatus);
            if ($stmt->execute()) {
                $response['success'] = true;
            } else {
                $response['message'] = 'Error al crear: ' . $stmt->error;
            }
            $stmt->close();
            break;

        case 'read':
            // Lógica para leer todos los usuarios
            $sql = "SELECT IdUsuarios, Nombre, Correo, Rol, Estatus FROM Usuarios";
            $result = $conex->query($sql);
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $users;
            break;

        case 'update':
            // Lógica para actualizar un usuario
            $id = $_POST['IdUsuarios'];
            $correo = $_POST['Correo'];
            $rol = $_POST['Rol'];
            $password = $_POST['Password'];

            if (!empty($password)) {
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE Usuarios SET Correo = ?, Rol = ?, Password = ? WHERE IdUsuarios = ?";
                $stmt = $conex->prepare($sql);
                $stmt->bind_param("siss", $correo, $rol, $pass_hash, $id);
            } else {
                $sql = "UPDATE Usuarios SET Correo = ?, Rol = ? WHERE IdUsuarios = ?";
                $stmt = $conex->prepare($sql);
                $stmt->bind_param("sis", $correo, $rol, $id);
            }

            if ($stmt->execute()) {
                $response['success'] = true;
            } else {
                $response['message'] = 'Error al actualizar: ' . $stmt->error;
            }
            $stmt->close();
            break;

        case 'toggle_status':
            // Lógica para activar/inactivar
            $id = $_POST['IdUsuarios'];
            $estatus = $_POST['Estatus'];
            $sql = "UPDATE Usuarios SET Estatus = ? WHERE IdUsuarios = ?";
            $stmt = $conex->prepare($sql);
            $stmt->bind_param("is", $estatus, $id);
            if ($stmt->execute()) {
                $response['success'] = true;
            } else {
                $response['message'] = 'Error al cambiar estatus: ' . $stmt->error;
            }
            $stmt->close();
            break;

        default:
            $response['message'] = 'Acción no válida.';
            break;
    }

    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>
