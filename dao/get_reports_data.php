<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

// --- Parámetros de Filtro ---
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$usuario_filter = $_GET['usuario'] ?? null;
$ruta_filter = $_GET['ruta'] ?? null;

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- Construcción de la Consulta Dinámica ---
    $sql_all = "SELECT 
                    bc.IdRuta, 
                    bc.TiempoTotal, 
                    bc.FechaInicio, 
                    bc.FechaFinalizacion,
                    bc.Usuario
                FROM BitacoraCompleta bc";

    $where_clauses = [];
    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $where_clauses[] = "bc.FechaInicio >= ?";
        $params[] = $startDate . " 00:00:00";
        $types .= "s";
    }
    if (!empty($endDate)) {
        $where_clauses[] = "bc.FechaInicio <= ?";
        $params[] = $endDate . " 23:59:59";
        $types .= "s";
    }
    if (!empty($usuario_filter)) {
        $where_clauses[] = "bc.Usuario = ?";
        $params[] = $usuario_filter;
        $types .= "s";
    }
    if (!empty($ruta_filter)) {
        // Asumimos que la primera parada nos da el número de ruta
        $where_clauses[] = "(SELECT bp.Ruta FROM BitacoraParadas bp WHERE bp.FolioRuta = bc.IdRuta LIMIT 1) = ?";
        $params[] = $ruta_filter;
        $types .= "i";
    }

    if (!empty($where_clauses)) {
        $sql_all .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql_all .= " ORDER BY bc.FechaFinalizacion DESC";

    $stmt = $conex->prepare($sql_all);
    if ($stmt === false) throw new Exception('Error al preparar la consulta principal: ' . $conex->error);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result_all = $stmt->get_result();

    $all_routes = [];
    while ($row = $result_all->fetch_assoc()) {
        $all_routes[] = $row;
    }
    $stmt->close();


    // --- Procesamiento de Datos (igual que antes, pero sobre los datos filtrados) ---
    $routes_by_shift = array_map(function($route) {
        $hour = (int)date('H', strtotime($route['FechaInicio']));
        $minute = (int)date('i', strtotime($route['FechaInicio']));
        $time_in_minutes = $hour * 60 + $minute;

        if ($time_in_minutes >= 390 && $time_in_minutes < 870) $route['turno'] = '1';
        elseif ($time_in_minutes >= 870 && $time_in_minutes <= 1320) $route['turno'] = '2';
        else $route['turno'] = '3';
        return $route;
    }, $all_routes);

    // Ordenar por tiempo para el análisis de turnos
    usort($routes_by_shift, function($a, $b) {
        return (int)$b['TiempoTotal'] <=> (int)$a['TiempoTotal'];
    });

    // Clonar y ordenar por tiempo para el top 5
    $top_routes_data = $all_routes;
    usort($top_routes_data, function($a, $b) {
        return (int)$b['TiempoTotal'] <=> (int)$a['TiempoTotal'];
    });
    $top_routes = array_slice($top_routes_data, 0, 5);

    $response['success'] = true;
    $response['data'] = [
        'top_routes' => $top_routes,
        'routes_by_shift' => $routes_by_shift,
        'all_completed_routes' => $all_routes
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
} finally {
    if (isset($conex) && $conex->ping()) $conex->close();
}

echo json_encode($response);
?>

