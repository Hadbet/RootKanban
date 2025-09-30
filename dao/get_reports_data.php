<?php
header('Content-Type: application/json');
require_once 'db/db_Rutas.php';

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // 1. Obtener todas las rutas completadas con su usuario de la primera parada
    $sql_all = "SELECT 
                    bc.IdRuta, 
                    bc.TiempoTotal, 
                    bc.FechaInicio, 
                    bc.FechaFinalizacion,
                    (SELECT bp.Usuario FROM BitacoraParadas bp WHERE bp.FolioRuta = bc.IdRuta ORDER BY bp.Fecha ASC LIMIT 1) as Usuario
                FROM BitacoraCompleta bc 
                ORDER BY bc.FechaFinalizacion DESC";

    $result_all = $conex->query($sql_all);
    if ($result_all === false) throw new Exception('Error al obtener rutas completas: ' . $conex->error);

    $all_routes = [];
    while ($row = $result_all->fetch_assoc()) {
        $all_routes[] = $row;
    }

    // 2. Procesar para análisis por turnos
    $routes_by_shift = array_map(function($route) {
        $hour = (int)date('H', strtotime($route['FechaInicio']));
        $minute = (int)date('i', strtotime($route['FechaInicio']));
        $time_in_minutes = $hour * 60 + $minute;

        // Turno 1: 06:30 (390 min) a 14:29 (869 min)
        // Turno 2: 14:30 (870 min) a 22:00 (1320 min)
        // Turno 3: 22:01 (1321 min) a 06:29 (389 min del día siguiente)
        if ($time_in_minutes >= 390 && $time_in_minutes < 870) {
            $route['turno'] = '1';
        } elseif ($time_in_minutes >= 870 && $time_in_minutes <= 1320) {
            $route['turno'] = '2';
        } else {
            $route['turno'] = '3';
        }
        return $route;
    }, $all_routes);

    // 3. Obtener el Top 5
    // Ya tenemos todas las rutas, solo hay que ordenar y cortar
    usort($all_routes, function($a, $b) {
        return $b['TiempoTotal'] <=> $a['TiempoTotal'];
    });
    $top_routes = array_slice($all_routes, 0, 5);

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
    if (isset($conex) && $conex->ping()) {
        $conex->close();
    }
}

echo json_encode($response);
?>
