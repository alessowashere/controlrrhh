<?php
// controllers/VacacionController.php
require_once 'models/Vacacion.php';
require_once 'models/Periodo.php'; // Needed for period list in filter
require_once 'models/Persona.php';

class VacacionController {
    private $db;
    private $vacacionModel;
    private $periodoModel;
    private $personaModel;

    public function __construct() {
        if (!class_exists('Database')) require_once __DIR__ . '/../config/Database.php';
        $this->db = Database::getInstance()->getConnection();
        $this->vacacionModel = new Vacacion($this->db);
        $this->periodoModel = new Periodo($this->db);
        $this->personaModel = new Persona($this->db);
    }

    // --- ACCIÓN INDEX (READ - Updated for Search/Filter) ---
    public function index() {
        $search_nombre = null; $search_area = null; $anio_inicio_filtro = null;
        $listaAnios = []; $listaVacaciones = []; $errorMessage = null;

        try {
            // 1. Get Search/Filter parameters from URL
            $search_nombre = filter_input(INPUT_GET, 'search_nombre', FILTER_SANITIZE_STRING);
            $search_area = filter_input(INPUT_GET, 'search_area', FILTER_SANITIZE_STRING);
            $anio_inicio_filtro = filter_input(INPUT_GET, 'anio_inicio', FILTER_VALIDATE_INT);
             if ($anio_inicio_filtro === false || $anio_inicio_filtro < 1900 || $anio_inicio_filtro > 2100) $anio_inicio_filtro = null;


            // 2. Get period year options for the filter dropdown
            $listaAnios = $this->periodoModel->getPeriodoAnios();

            // 3. Get vacation list from Model, passing search/filter parameters
            $listaVacaciones = $this->vacacionModel->listar(
                $search_nombre,
                $search_area,
                $anio_inicio_filtro
            );

        } catch (Exception $e) {
             error_log("Error in VacacionController::index - " . $e->getMessage());
             $errorMessage = "Error al cargar datos de vacaciones: " . $e->getMessage();
        }

        // 4. Load views and pass all data (including search terms for persistence)
        require 'views/layout/header.php';
        require 'views/vacaciones/index.php'; // Pass $listaVacaciones, $listaAnios, $search_nombre, $search_area, $anio_inicio_filtro, $errorMessage
        require 'views/layout/footer.php';
    }


    // --- ACCIÓN CREATE ---
    public function create() {
        $listaPersonas = [];
        try {
            $listaPersonas = $this->personaModel->listar(); // Get active employees for dropdown
        } catch (Exception $e) { error_log("Error fetching personas for Vacacion create form: " . $e->getMessage()); }
        require 'views/layout/header.php';
        require 'views/vacaciones/create.php'; // Pass $listaPersonas
        require 'views/layout/footer.php';
    }

    // --- ACCIÓN STORE ---
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_POST['persona_id']) || empty($_POST['periodo_id']) || empty($_POST['fecha_inicio']) || empty($_POST['fecha_fin']) || !isset($_POST['dias_tomados'])) {
                 header('Location: index.php?controller=vacacion&action=create&status=error_datos'); exit; }

            $persona_id = filter_input(INPUT_POST, 'persona_id', FILTER_VALIDATE_INT);
            $periodo_id = filter_input(INPUT_POST, 'periodo_id', FILTER_VALIDATE_INT); // Get from form
            $fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_STRING);
            $fecha_fin = filter_input(INPUT_POST, 'fecha_fin', FILTER_SANITIZE_STRING);
            $dias_tomados = filter_input(INPUT_POST, 'dias_tomados', FILTER_VALIDATE_INT);
            $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING) ?? 'NORMAL';
            $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING) ?? 'PENDIENTE';

            // --- VALIDATION: Check Available Balance ---
            $saldo_disponible = -999; // Use a distinct error value
            if ($periodo_id) {
                try {
                     $periodo_data_raw = $this->periodoModel->obtenerPorIdConSaldo($periodo_id);
                     if ($periodo_data_raw) {
                          $total_dias_periodo = $periodo_data_raw['total_dias'] ?? 0;
                          $dias_usados_periodo = $periodo_data_raw['dias_usados_calculados'] ?? 0;
                          // Check if it's the current earning period and hasn't reached full entitlement
                          $isCurrentEarning = (new DateTime() >= new DateTime($periodo_data_raw['periodo_inicio']) && $total_dias_periodo < 30);
                          // Available balance is based on earned days if current earning, or total if past
                          $saldo_disponible = $total_dias_periodo - $dias_usados_periodo;
                     } else { $saldo_disponible = -998; } // Period ID invalid
                } catch (Exception $e) { error_log("Error fetching saldo for periodo {$periodo_id}: " . $e->getMessage()); $saldo_disponible = -997; } // DB error
            }

            if ($dias_tomados === false || $dias_tomados <= 0) {
                 header('Location: index.php?controller=vacacion&action=create&status=error_dias_invalidos'); exit;
            }
            // Allow if Adelanto OR if days requested <= available balance
            if ($tipo != 'ADELANTO' && $dias_tomados > $saldo_disponible) {
                 $saldo_info = ($saldo_disponible >= -30) ? "&saldo={$saldo_disponible}" : ""; // Pass saldo back unless large error code
                 header('Location: index.php?controller=vacacion&action=create&status=error_saldo' . $saldo_info . "&req={$dias_tomados}"); exit;
            }
            // --- END Balance Validation ---

            $datos = ['persona_id' => $persona_id, 'periodo_id' => $periodo_id, 'fecha_inicio' => $fecha_inicio,
                      'fecha_fin' => $fecha_fin, 'dias_tomados' => $dias_tomados, 'tipo' => $tipo, 'estado' => $estado];

            try { if ($this->vacacionModel->crear($datos)) { header('Location: index.php?controller=vacacion&action=index&status=creado'); exit; }
                  else { header('Location: index.php?controller=vacacion&action=create&status=error_guardar'); exit; }
            } catch (Exception $e) { error_log("Error storing vacacion: " . $e->getMessage()); header('Location: index.php?controller=vacacion&action=create&status=error_excepcion'); exit; }
        }
        header('Location: index.php?controller=vacacion&action=index'); exit;
    }


    // --- ACCIÓN EDIT ---
    public function edit() {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) die('ID de vacación no válido.');
        $vacacion = null; $listaPersonas = [];
        try { $vacacion = $this->vacacionModel->obtenerPorId($id); $listaPersonas = $this->personaModel->listar(); }
        catch (Exception $e) { error_log("Error fetching data for Vacacion edit form (ID: {$id}): " . $e->getMessage()); die("Error al cargar datos."); }
        if (!$vacacion) die('Vacación no encontrada.');
        require 'views/layout/header.php'; require 'views/vacaciones/edit.php'; require 'views/layout/footer.php';
    }

    // --- ACCIÓN UPDATE ---
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id || empty($_POST['persona_id']) || empty($_POST['periodo_id']) || empty($_POST['fecha_inicio']) || empty($_POST['fecha_fin']) || !isset($_POST['dias_tomados'])) {
                 header('Location: index.php?controller=vacacion&action=edit&id=' . ($id ?? '') . '&status=error_datos'); exit; }

            $persona_id = filter_input(INPUT_POST, 'persona_id', FILTER_VALIDATE_INT);
            $periodo_id = filter_input(INPUT_POST, 'periodo_id', FILTER_VALIDATE_INT); // Get from form
            $fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_SANITIZE_STRING);
            $fecha_fin = filter_input(INPUT_POST, 'fecha_fin', FILTER_SANITIZE_STRING);
            $dias_tomados = filter_input(INPUT_POST, 'dias_tomados', FILTER_VALIDATE_INT);
            $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING) ?? 'NORMAL';
            $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING) ?? 'PENDIENTE';

            // --- VALIDATION: Check Available Balance (Edit version) ---
            $saldo_disponible = -999; $dias_actuales_registro = 0;
            if ($id) { $vacacion_actual = $this->vacacionModel->obtenerPorId($id); if ($vacacion_actual) $dias_actuales_registro = $vacacion_actual['dias_tomados']; }
            if ($periodo_id) { try { $periodo_data_raw = $this->periodoModel->obtenerPorIdConSaldo($periodo_id);
                     if ($periodo_data_raw) {
                          $total_dias_periodo = $periodo_data_raw['total_dias'] ?? 0;
                          $dias_usados_periodo = $periodo_data_raw['dias_usados_calculados'] ?? 0;
                          $saldo_periodo = $total_dias_periodo - $dias_usados_periodo;
                          // Available = current period saldo + days currently assigned to THIS vacation record
                          $saldo_disponible = $saldo_periodo + $dias_actuales_registro;
                     } else { $saldo_disponible = -998; }
                } catch (Exception $e) { error_log("Error fetching saldo for edit (periodo {$periodo_id}): " . $e->getMessage()); $saldo_disponible = -997; } }

            if ($dias_tomados === false || $dias_tomados <= 0) {
                 header('Location: index.php?controller=vacacion&action=edit&id=' . $id . '&status=error_dias_invalidos'); exit;
            }
            if ($tipo != 'ADELANTO' && $dias_tomados > $saldo_disponible) {
                 $saldo_info = ($saldo_disponible >= -30) ? "&saldo={$saldo_disponible}" : "";
                 header('Location: index.php?controller=vacacion&action=edit&id=' . $id . '&status=error_saldo' . $saldo_info . "&req={$dias_tomados}"); exit;
            }
            // --- END Balance Validation ---

            $datos = ['persona_id' => $persona_id, 'periodo_id' => $periodo_id, 'fecha_inicio' => $fecha_inicio,
                      'fecha_fin' => $fecha_fin, 'dias_tomados' => $dias_tomados, 'tipo' => $tipo, 'estado' => $estado];

            try { if ($this->vacacionModel->actualizar($id, $datos)) { header('Location: index.php?controller=vacacion&action=index&status=actualizado'); exit; }
                  else { header('Location: index.php?controller=vacacion&action=edit&id=' . $id . '&status=error_guardar'); exit; }
            } catch (Exception $e) { error_log("Error updating vacacion (ID: {$id}): " . $e->getMessage()); header('Location: index.php?controller=vacacion&action=edit&id=' . $id . '&status=error_excepcion'); exit; }
        }
        header('Location: index.php?controller=vacacion&action=index'); exit;
    }


    // --- ACCIÓN DELETE ---
    public function delete() {
         $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) { header('Location: index.php?controller=vacacion&action=index&status=error_id'); exit; }
        try { if ($this->vacacionModel->eliminar($id)) { header('Location: index.php?controller=vacacion&action=index&status=eliminado'); exit; }
              else { header('Location: index.php?controller=vacacion&action=index&status=error_eliminar'); exit; }
        } catch (Exception $e) { error_log("Error deleting vacacion (ID: {$id}): " . $e->getMessage()); header('Location: index.php?controller=vacacion&action=index&status=error_excepcion'); exit; }
    }

} // End Class