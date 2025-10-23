<?php
class ReporteModel {

    private $db; 

    // 1. Constructor para recibir la conexión BD
    public function __construct($db) {
        if (!($db instanceof PDO)) {
            die("FATAL ERROR in ReporteModel Constructor: DB connection invalid!");
        }
        $this->db = $db;
    }

    // 2. SQL REAL para Reporte General
    public function getReporteGeneral($filtros) {
        try {
            $sql = "SELECT p.id, p.nombre_completo, p.cargo, p.area, p.fecha_ingreso,
                           per.periodo_inicio, per.periodo_fin, per.total_dias,
                           COALESCE(v_calc.dias_reales, 0) AS dias_usados_calculados,
                           (per.total_dias - COALESCE(v_calc.dias_reales, 0)) AS saldo_calculado
                    FROM personas p
                    JOIN (
                        SELECT psub.id as persona_id_sub, COALESCE(
                            (SELECT per1.id FROM periodos per1 WHERE per1.persona_id = psub.id AND per1.periodo_fin >= CURDATE() ORDER BY per1.periodo_fin ASC LIMIT 1),
                            (SELECT per2.id FROM periodos per2 WHERE per2.persona_id = psub.id ORDER BY per2.periodo_inicio DESC LIMIT 1)
                        ) as periodo_relevante_id
                        FROM personas psub WHERE psub.estado = 'ACTIVO'
                    ) AS pr ON p.id = pr.persona_id_sub
                    LEFT JOIN periodos per ON pr.periodo_relevante_id = per.id
                    LEFT JOIN (SELECT periodo_id, SUM(dias_tomados) as dias_reales FROM vacaciones WHERE estado IN ('APROBADO', 'GOZADO') GROUP BY periodo_id) AS v_calc ON per.id = v_calc.periodo_id
                    WHERE p.estado = 'ACTIVO'
                    ORDER BY p.nombre_completo ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ReporteModel::getReporteGeneral: " . $e->getMessage());
            return [];
        }
    }

    // 3. SQL REAL para Reporte por Persona
    public function getReportePorPersona($filtros) {
        try {
            $sql = "SELECT v.fecha_inicio, v.fecha_fin, v.dias_tomados, v.estado, v.tipo, 
                           per.periodo_inicio, per.periodo_fin
                    FROM vacaciones v
                    JOIN periodos per ON v.periodo_id = per.id
                    WHERE v.persona_id = :persona_id";
            
            $params = [':persona_id' => $filtros['empleado_id']];
            
            if (!empty($filtros['fecha_inicio'])) {
                $sql .= " AND v.fecha_inicio >= :fecha_inicio";
                $params[':fecha_inicio'] = $filtros['fecha_inicio'];
            }
            if (!empty($filtros['fecha_fin'])) {
                $sql .= " AND v.fecha_fin <= :fecha_fin";
                $params[':fecha_fin'] = $filtros['fecha_fin'];
            }
            $sql .= " ORDER BY v.fecha_inicio DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ReporteModel::getReportePorPersona: " . $e->getMessage());
            return [];
        }
    }

    // 4. CAMBIO AQUÍ: SQL para Reporte por Período (usa anio_inicio)
    public function getReportePorPeriodo($filtros) {
        try {
            // La consulta ahora une 'periodos' para filtrar por AÑO
            $sql = "SELECT p.nombre_completo, v.fecha_inicio, v.fecha_fin, v.dias_tomados, v.estado, v.tipo
                    FROM vacaciones v
                    JOIN personas p ON v.persona_id = p.id
                    JOIN periodos per ON v.periodo_id = per.id
                    WHERE YEAR(per.periodo_inicio) = :anio_inicio
                    AND v.estado IN ('APROBADO', 'GOZADO')
                    ORDER BY p.nombre_completo ASC, v.fecha_inicio ASC";
            
            // Los parámetros ahora solo usan 'anio_inicio'
            $params = [
                ':anio_inicio' => $filtros['anio_inicio']
            ];
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ReporteModel::getReportePorPeriodo: " . $e->getMessage());
            return [];
        }
    }

    // 5. SQL REAL para Reporte de Saldos
    public function getReporteSaldos($filtros) {
         try {
            // Reutiliza la consulta general, pero filtra por saldos no-cero
            $sql = "SELECT p.nombre_completo, p.cargo,
                           (per.total_dias - COALESCE(v_calc.dias_reales, 0)) AS saldo_calculado
                    FROM personas p
                    JOIN (
                        SELECT psub.id as persona_id_sub, COALESCE(
                            (SELECT per1.id FROM periodos per1 WHERE per1.persona_id = psub.id AND per1.periodo_fin >= CURDATE() ORDER BY per1.periodo_fin ASC LIMIT 1),
                            (SELECT per2.id FROM periodos per2 WHERE per2.persona_id = psub.id ORDER BY per2.periodo_inicio DESC LIMIT 1)
                        ) as periodo_relevante_id
                        FROM personas psub WHERE psub.estado = 'ACTIVO'
                    ) AS pr ON p.id = pr.persona_id_sub
                    LEFT JOIN periodos per ON pr.periodo_relevante_id = per.id
                    LEFT JOIN (SELECT periodo_id, SUM(dias_tomados) as dias_reales FROM vacaciones WHERE estado IN ('APROBADO', 'GOZADO') GROUP BY periodo_id) AS v_calc ON per.id = v_calc.periodo_id
                    WHERE p.estado = 'ACTIVO'
                    HAVING saldo_calculado != 0
                    ORDER BY saldo_calculado ASC"; // Muestra deudas (negativos) primero
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ReporteModel::getReporteSaldos: " . $e->getMessage());
            return [];
        }
    }
    public function getReportePorArea($filtros) {
        // Esta consulta es idéntica a getReporteGeneral, 
        // pero con un filtro WHERE adicional para el área.
        try {
            $sql = "SELECT p.id, p.nombre_completo, p.cargo, p.fecha_ingreso,
                           per.periodo_inicio, per.periodo_fin, per.total_dias,
                           COALESCE(v_calc.dias_reales, 0) AS dias_usados_calculados,
                           (per.total_dias - COALESCE(v_calc.dias_reales, 0)) AS saldo_calculado
                    FROM personas p
                    JOIN (
                        SELECT psub.id as persona_id_sub, COALESCE(
                            (SELECT per1.id FROM periodos per1 WHERE per1.persona_id = psub.id AND per1.periodo_fin >= CURDATE() ORDER BY per1.periodo_fin ASC LIMIT 1),
                            (SELECT per2.id FROM periodos per2 WHERE per2.persona_id = psub.id ORDER BY per2.periodo_inicio DESC LIMIT 1)
                        ) as periodo_relevante_id
                        FROM personas psub WHERE psub.estado = 'ACTIVO'
                    ) AS pr ON p.id = pr.persona_id_sub
                    LEFT JOIN periodos per ON pr.periodo_relevante_id = per.id
                    LEFT JOIN (SELECT periodo_id, SUM(dias_tomados) as dias_reales FROM vacaciones WHERE estado IN ('APROBADO', 'GOZADO') GROUP BY periodo_id) AS v_calc ON per.id = v_calc.periodo_id
                    WHERE p.estado = 'ACTIVO' 
                      AND p.area = :area  -- <-- El filtro NUEVO
                    ORDER BY p.nombre_completo ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':area' => $filtros['area']]); // Pasamos el área al SGBD
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en ReporteModel::getReportePorArea: " . $e->getMessage());
            return [];
        }
    }
}