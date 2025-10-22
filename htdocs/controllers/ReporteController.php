<?php
require_once '../models/ReporteModel.php';  // <-- Añadido ../
require_once '../models/EmpleadoModel.php';  // <-- Añadido ../
// ...

class ReporteController {

    private $reporteModel;
    private $empleadoModel;

    public function __construct() {
        $this->reporteModel = new ReporteModel();
        $this->empleadoModel = new EmpleadoModel(); // Asumiendo que existe
    }

    /**
     * Muestra la página principal de selección de reportes.
     */
    public function index() {
        // Obtenemos la lista de empleados para el selector
        $empleados = $this->empleadoModel->obtenerTodosLosEmpleados(); // Debes implementar esta función en EmpleadoModel
        
        // Cargamos la vista principal del módulo de reportes
        $view = 'views/reportes/index.php';
        require_once 'views/layout/layout.php'; // Tu layout principal
    }

    /**
     * Punto de entrada central para CUALQUIER reporte.
     * Decide qué hacer basándose en $_POST['tipo_reporte'].
     */
    public function generar() {
        if (!isset($_POST['tipo_reporte'])) {
            die("Error: Tipo de reporte no especificado.");
        }

        $tipoReporte = $_POST['tipo_reporte'];
        $data = []; // Aquí guardaremos los datos para el reporte
        $vistaReporte = ''; // El archivo .php que contiene la tabla/formato del reporte
        $tituloReporte = ''; // Título para la vista previa

        // Filtros opcionales
        $filtros = [
            'empleado_id' => $_POST['empleado_id'] ?? null,
            'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'fecha_fin' => $_POST['fecha_fin'] ?? null,
        ];

        try {
            // Decidimos qué datos buscar
            switch ($tipoReporte) {
                case 'general':
                    $tituloReporte = 'Reporte General de Personal';
                    $data['resultados'] = $this->reporteModel->getReporteGeneral($filtros);
                    $vistaReporte = 'views/reportes/vistas/general.php';
                    break;

                case 'por_persona':
                    if (empty($filtros['empleado_id'])) {
                        throw new Exception("Debe seleccionar un empleado.");
                    }
                    $tituloReporte = 'Reporte por Persona';
                    $data['resultados'] = $this->reporteModel->getReportePorPersona($filtros);
                    $data['info_empleado'] = $this->empleadoModel->obtenerEmpleadoPorId($filtros['empleado_id']); // Info para la cabecera
                    $vistaReporte = 'views/reportes/vistas/por_persona.php';
                    break;

                case 'por_periodo':
                     if (empty($filtros['fecha_inicio']) || empty($filtros['fecha_fin'])) {
                        throw new Exception("Debe seleccionar un rango de fechas.");
                    }
                    $tituloReporte = 'Reporte por Período';
                    $data['resultados'] = $this->reporteModel->getReportePorPeriodo($filtros);
                    $vistaReporte = 'views/reportes/vistas/por_periodo.php';
                    break;

                case 'saldos':
                    $tituloReporte = 'Reporte de Saldos y Pendientes';
                    $data['resultados'] = $this->reporteModel->getReporteSaldos($filtros);
                    $vistaReporte = 'views/reportes/vistas/saldos.php';
                    break;

                default:
                    throw new Exception("Tipo de reporte no válido.");
            }

            // Si llegamos aquí, tenemos datos y una vista.
            // ¡Mostramos la VISTA PREVIA!
            $this->mostrarVistaPrevia($tituloReporte, $vistaReporte, $data, $filtros);

        } catch (Exception $e) {
            // Manejo de errores simple
            echo "Error al generar el reporte: " . $e->getMessage();
            // Aquí podrías redirigir a una página de error más elegante
        }
    }

    /**
     * Carga el layout especial de "Vista Previa"
     */
    private function mostrarVistaPrevia($tituloReporte, $vistaReporte, $data, $filtros) {
        // Hacemos que $data esté disponible en la vista
        extract($data);
        // Hacemos $filtros disponible en la vista (para mostrar "Reporte del 01/01 al 31/01")
        extract(['filtros' => $filtros]);
        
        // Cargamos el layout de vista previa (¡NO el layout principal!)
        require_once 'views/layout/report_preview.php';
    }
}