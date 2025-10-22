<?php
class ReporteModel {

    private $db; // Asumiendo que tienes un objeto de conexión (ej. PDO)

    public function __construct() {
        // Aquí inicializas tu conexión a la base de datos
        // $this->db = new PDO(...); 
        // O $this->db = Database::connect(); (lo que uses)
    }

    // Consulta para el reporte general
    public function getReporteGeneral($filtros) {
        // $sql = "SELECT e.nombre, e.apellido, p.nombre AS puesto, e.fecha_ingreso, v.dias_tomados
        //         FROM empleados e 
        //         LEFT JOIN puestos p ON e.puesto_id = p.id
        //         LEFT JOIN (SELECT empleado_id, SUM(dias) AS dias_tomados FROM vacaciones GROUP BY empleado_id) v 
        //         ON e.id = v.empleado_id
        //         WHERE e.estado = 'activo'";
        // $stmt = $this->db->query($sql);
        // return $stmt->fetchAll(PDO::FETCH_ASSOC);

        // *** SQL DE EJEMPLO - DEBES REEMPLAZARLO ***
        return [
            ['id' => 1, 'nombre' => 'Juan Perez', 'puesto' => 'Desarrollador', 'dias_pendientes' => 15],
            ['id' => 2, 'nombre' => 'Ana Garcia', 'puesto' => 'Diseñadora', 'dias_pendientes' => 5],
        ];
    }

    // Consulta para el reporte por persona
    public function getReportePorPersona($filtros) {
        $id = $filtros['empleado_id'];
        // $sql = "SELECT * FROM vacaciones WHERE empleado_id = :id";
        
        // Aplicar filtros de fecha si existen
        // if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
        //     $sql .= " AND fecha_inicio >= :fecha_inicio AND fecha_fin <= :fecha_fin";
        // }
        // $stmt = $this->db->prepare($sql);
        // $stmt->execute(['id' => $id, ...]);
        // return $stmt->fetchAll(PDO::FETCH_ASSOC);

        // *** SQL DE EJEMPLO - DEBES REEMPLAZARLO ***
         return [
            ['fecha_solicitud' => '2025-01-10', 'dias_tomados' => 5, 'estado' => 'Aprobado'],
            ['fecha_solicitud' => '2025-03-15', 'dias_tomados' => 10, 'estado' => 'Aprobado'],
        ];
    }

    // Consulta para el reporte por período
    public function getReportePorPeriodo($filtros) {
        // $sql = "SELECT e.nombre, v.fecha_inicio, v.fecha_fin, v.dias_tomados
        //         FROM vacaciones v
        //         JOIN empleados e ON v.empleado_id = e.id
        //         WHERE v.fecha_inicio >= :fecha_inicio AND v.fecha_fin <= :fecha_fin
        //         ORDER BY e.nombre, v.fecha_inicio";
        // $stmt = $this->db->prepare($sql);
        // $stmt->execute(['fecha_inicio' => $filtros['fecha_inicio'], 'fecha_fin' => $filtros['fecha_fin']]);
        // return $stmt->fetchAll(PDO::FETCH_ASSOC);

        // *** SQL DE EJEMPLO - DEBES REEMPLAZARLO ***
        return [
            ['nombre' => 'Juan Perez', 'fecha_inicio' => '2025-01-10', 'dias_tomados' => 5],
            ['nombre' => 'Ana Garcia', 'fecha_inicio' => '2025-03-15', 'dias_tomados' => 10],
        ];
    }

    // Consulta para el reporte de saldos (días pendientes/deuda)
    public function getReporteSaldos($filtros) {
        // Esta es la consulta más compleja, dependerá de tu lógica de negocio
        // (días ganados por antigüedad vs días tomados)
        // $sql = "SELECT e.nombre, e.fecha_ingreso, 
        //               (FN_CALCULAR_DIAS_GANADOS(e.id) - FN_CALCULAR_DIAS_TOMADOS(e.id)) AS saldo_actual
        //         FROM empleados e
        //         HAVING saldo_actual != 0
        //         ORDER BY saldo_actual ASC"; // Los que deben (negativo) primero
        // $stmt = $this->db->query($sql);
        // return $stmt->fetchAll(PDO::FETCH_ASSOC);

        // *** SQL DE EJEMPLO - DEBES REEMPLAZARLO ***
         return [
            ['nombre' => 'Carlos Solis', 'saldo' => -3, 'mensaje' => 'Debe 3 días (adelanto)'],
            ['nombre' => 'Ana Garcia', 'saldo' => 5, 'mensaje' => 'Tiene 5 días pendientes'],
            ['nombre' => 'Juan Perez', 'saldo' => 15, 'mensaje' => 'Tiene 15 días pendientes'],
        ];
    }
}