<?php
// controllers/DashboardController.php

require_once 'models/Persona.php';
require_once 'models/Vacacion.php';
// require_once 'models/Periodo.php'; // Not directly needed for this dashboard version

class DashboardController {

    private $db;
    private $personaModel;
    private $vacacionModel;

    public function __construct() {
        if (!class_exists('Database')) require_once __DIR__ . '/../config/Database.php';
        try {
            $this->db = Database::getInstance()->getConnection();
            $this->personaModel = new Persona($this->db);
            $this->vacacionModel = new Vacacion($this->db);
        } catch (Exception $e) {
             error_log("FATAL: Failed to initialize DashboardController dependencies: " . $e->getMessage());
             // In a real app, show a user-friendly error page
             die("Error crítico al inicializar la aplicación. Verifique la conexión a la base de datos y la configuración.");
        }
    }

    public function index() {
        $errorMessage = null;
        $dashboardData = [
            'totalActivos' => 0,
            'solicitudesPendientes' => 0,
            'enVacacionesAhora' => [], // Store the list here
            'proximasVacaciones' => [],
            'saldosBajos' => [],
            'saldosAltos' => []
        ];

        try {
            // KPIs
            $dashboardData['totalActivos'] = $this->personaModel->contarActivos();
            $dashboardData['solicitudesPendientes'] = $this->vacacionModel->contarPorEstado('PENDIENTE');
            // Fetch list of who is on vacation now
            $dashboardData['enVacacionesAhora'] = $this->vacacionModel->listarActuales(5); // Limit list size

            // Lists
            $dashboardData['proximasVacaciones'] = $this->vacacionModel->listarProximas(14, 5); // Next 14 days, max 5

            // Extreme Balances
            $dashboardData['saldosBajos'] = $this->personaModel->listarSaldosExtremos('bajo', 5, 5); // <= 5 days
            $dashboardData['saldosAltos'] = $this->personaModel->listarSaldosExtremos('alto', 25, 5); // >= 25 days

        } catch (Exception $e) {
            error_log("Error loading dashboard data: " . $e->getMessage());
            $errorMessage = "Ocurrió un error al cargar los datos del dashboard.";
            // Avoid passing potentially empty/null arrays if DB failed badly
            // Reset to ensure view handles empty gracefully
             $dashboardData = [ /* Reset structure */ ];
        }

        // Load view and pass data
        require 'views/layout/header.php';
        require 'views/dashboard/index.php';
        require 'views/layout/footer.php';
    }
}