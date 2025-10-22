<?php
// controllers/ImportarController.php
require_once 'models/Persona.php';
require_once 'models/Periodo.php';
require_once 'models/Vacacion.php';

class ImportarController {

    private $db;

    public function __construct() {
        if (!class_exists('Database')) require_once __DIR__ . '/../config/Database.php';
        $this->db = Database::getInstance()->getConnection();
        
        // Iniciar la sesión si no está activa, para guardar la vista previa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Muestra la página con el formulario de subida.
     */
    public function index() {
        // Limpiar datos de sesión antiguos si existen
        unset($_SESSION['import_preview_data']);
        unset($_SESSION['import_mode']);
        
        require 'views/layout/header.php';
        require 'views/importar/index.php'; // Formulario de subida
        require 'views/layout/footer.php';
    }

    /**
     * PASO 1: Procesa el CSV, NO GUARDA NADA, solo muestra resumen.
     */
    public function previsualizar() {
        // 1. Validar la subida del archivo
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
            $this->redirigirConError('index', 'Error al subir el archivo. Inténtelo de nuevo.');
            return;
        }

        $csvFilePath = $_FILES['archivo_csv']['tmp_name'];
        $fileMimeType = mime_content_type($csvFilePath);
        
        // Se acepta text/plain (CSV) y application/csv
        $allowedMimeTypes = ['text/plain', 'text/csv', 'application/csv'];
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            $this->redirigirConError('index', "Error: El archivo no es un CSV válido. (Tipo detectado: $fileMimeType)");
            return;
        }

        $file = fopen($csvFilePath, 'r');
        if ($file === false) {
            $this->redirigirConError('index', 'Error: No se pudo abrir el archivo subido.');
            return;
        }
        
        // Guardar el modo de importación seleccionado
        $import_mode = $_POST['import_mode'] ?? 'reemplazar'; // 'reemplazar' o 'actualizar'
        $_SESSION['import_mode'] = $import_mode;

        // Variables para la vista previa
        $data_preview = [];
        $contadorPersonas = 0;
        $contadorVacaciones = 0;
        $errores_preview = [];

        try {
            // --- CAMBIO AQUÍ --- Se añade el delimitador ';'
            // Omitir las 3 primeras filas de cabecera
            fgetcsv($file, 1000, ";"); 
            fgetcsv($file, 1000, ";"); 
            fgetcsv($file, 1000, ";");
            // Omitir la fila de títulos (fila 4)
            $csv_headers = fgetcsv($file, 1000, ";"); 

            // 8. Leer fila por fila
            // --- CAMBIO AQUÍ --- Se usa ';' en lugar de ','
            while (($row = fgetcsv($file, 1000, ";")) !== FALSE) {
                // Indices: 0:N°, 1:DNI, 2:FECHA INICIO, 3:NOMBRE, 4:CARGO, 5:LUGAR, 6:DE - HASTA, 7:Nº(días)
                
                // Si la fila está mal formada o vacía, $row puede ser [0 => null]
                if (count($row) < 8 || empty($row[3])) continue; 

                $persona_id = (int)$this->clean_value_php($row[0], true);
                if ($persona_id == 0) continue; 
                
                $nombre = $this->clean_value_php($row[3]);
                $fecha_ingreso_raw = $this->clean_value_php($row[2]);

                // --- CAMBIO AQUÍ --- Se usa formato j/m/Y (ej. 4/01/2016)
                $ingreso_dt = $this->parse_date_php($fecha_ingreso_raw, 'j/m/Y');
                if ($ingreso_dt === null) {
                    $errores_preview[] = "Empleado '{$nombre}' (ID: {$persona_id}) omitido: Fecha de ingreso inválida ('{$fecha_ingreso_raw}'). Se esperaba formato dd/mm/YYYY.";
                    continue; // Saltar a la siguiente persona
                }
                
                $persona_data = [
                    'id' => $persona_id,
                    'dni' => $this->clean_value_php($row[1], true),
                    'nombre_completo' => $nombre,
                    'cargo' => $this->clean_value_php($row[4]),
                    'area' => $this->clean_value_php($row[5]),
                    // Guardamos la fecha en formato YYYY-MM-DD para la BD
                    'fecha_ingreso' => $ingreso_dt->format('Y-m-d'), 
                    'numero_empleado' => "UAC-{$this->get_initials_php($nombre)}-{$persona_id}",
                    'periodo' => null,
                    'vacaciones' => []
                ];
                
                // Calcular Período 2024-2025
                $periodo_id = $persona_id;
                $periodo_inicio_sql = "2024-" . $ingreso_dt->format('m-d');
                $periodo_fin_dt = (new DateTime("2025-" . $ingreso_dt->format('m-d')))->modify('-1 day');
                $periodo_fin_sql = $periodo_fin_dt->format('Y-m-d');
                
                $persona_data['periodo'] = [
                    'id' => $periodo_id,
                    'persona_id' => $persona_id,
                    'periodo_inicio' => $periodo_inicio_sql,
                    'periodo_fin' => $periodo_fin_sql
                ];

                // Procesar Vacaciones (rangos múltiples)
                $vacas_raw = $this->clean_value_php($row[6]);
                $dias_raw = $this->clean_value_php($row[7]);
                
                preg_match_all('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}\/\d{2}\/\d{4})/', $vacas_raw, $date_matches);
                preg_match_all('/(\d+)/', $dias_raw, $dias_matches);
                $dias_list = $dias_matches[1];

                foreach ($date_matches[1] as $index => $start_date_str) {
                    $start_sql = $this->format_date_php($start_date_str); // Convierte dd/mm/YYYY a YYYY-mm-dd
                    $end_sql = $this->format_date_php($date_matches[2][$index]);
                    $dias_tomados = $dias_list[$index] ?? 0;
                    
                    if ($start_sql && $end_sql && $dias_tomados > 0) {
                        $persona_data['vacaciones'][] = [
                            'fecha_inicio' => $start_sql,
                            'fecha_fin' => $end_sql,
                            'dias_tomados' => $dias_tomados
                        ];
                        $contadorVacaciones++;
                    }
                }
                
                $data_preview[] = $persona_data;
                $contadorPersonas++;
            }
            
            fclose($file);

            // Guardar los datos procesados en la sesión para el Paso 2
            $_SESSION['import_preview_data'] = $data_preview;

            // Cargar la vista de previsualización
            require 'views/layout/header.php';
            require 'views/importar/preview.php'; 
            require 'views/layout/footer.php';

        } catch (Exception $e) {
            fclose($file);
            $this->redirigirConError('index', 'Error Crítico al leer el archivo: ' . $e->getMessage());
        }
    }
    
    /**
     * PASO 2: Ejecuta la importación usando los datos guardados en la sesión.
     * (Esta función no lee el CSV, así que no necesita cambios en el delimitador)
     */
    public function ejecutar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['import_preview_data']) || !isset($_SESSION['import_mode'])) {
            $this->redirigirConError('index', 'Error: No hay datos de vista previa para importar o la sesión expiró.');
            return;
        }

        $data_to_import = $_SESSION['import_preview_data'];
        $import_mode = $_SESSION['import_mode'];
        
        // Limpiar la sesión para evitar re-envíos
        unset($_SESSION['import_preview_data']);
        unset($_SESSION['import_mode']);

        $this->db->beginTransaction();
        try {
            
            if ($import_mode === 'reemplazar') {
                $this->db->exec("SET FOREIGN_KEY_CHECKS=0;");
                $this->db->exec("DELETE FROM vacaciones;");
                $this->db->exec("DELETE FROM periodos;");
                $this->db->exec("DELETE FROM personas;");
               $this->db->exec("ALTER TABLE personas AUTO_INCREMENT = 1;");
                $this->db->exec("ALTER TABLE periodos AUTO_INCREMENT = 1;");
                $this->db->exec("ALTER TABLE vacaciones AUTO_INCREMENT = 1;");
                $this->db->exec("SET FOREIGN_KEY_CHECKS=1;");
            }
            
            // Preparar sentencias
            $stmtPersona = $this->db->prepare(
                "INSERT INTO personas (id, dni, numero_empleado, nombre_completo, cargo, area, fecha_ingreso, estado) 
                 VALUES (:id, :dni, :num_emp, :nombre, :cargo, :area, :ingreso, 'ACTIVO')"
            );
            $stmtPeriodo = $this->db->prepare(
                "INSERT INTO periodos (id, persona_id, periodo_inicio, periodo_fin, total_dias, dias_usados) 
                 VALUES (:id, :pid, :inicio, :fin, 30, 0)"
            );
            $stmtVaca = $this->db->prepare(
                "INSERT INTO vacaciones (persona_id, periodo_id, fecha_inicio, fecha_fin, dias_tomados, tipo, estado) 
                 VALUES (:pid, :perid, :inicio, :fin, :dias, 'NORMAL', 'GOZADO')"
            );

            $contadorPersonas = 0;
            $contadorVacaciones = 0;

            foreach ($data_to_import as $persona_data) {
                // Insertar Persona
                $stmtPersona->execute([
                    ':id' => $persona_data['id'],
                    ':dni' => $persona_data['dni'],
                    ':num_emp' => $persona_data['numero_empleado'],
                    ':nombre' => $persona_data['nombre_completo'],
                    ':cargo' => $persona_data['cargo'],
                    ':area' => $persona_data['area'],
                    ':ingreso' => $persona_data['fecha_ingreso'] // Ya está en YYYY-MM-DD
                ]);
                $contadorPersonas++;
                
                // Insertar Periodo
                $periodo = $persona_data['periodo'];
                $stmtPeriodo->execute([
                    ':id' => $periodo['id'],
                    ':pid' => $periodo['persona_id'],
                    ':inicio' => $periodo['periodo_inicio'],
                    ':fin' => $periodo['periodo_fin']
                ]);
                
                // Insertar Vacaciones
                foreach ($persona_data['vacaciones'] as $vaca) {
                    $stmtVaca->execute([
                        ':pid' => $persona_data['id'],
                        ':perid' => $periodo['id'],
                        ':inicio' => $vaca['fecha_inicio'],
                        ':fin' => $vaca['fecha_fin'],
                        ':dias' => $vaca['dias_tomados']
                    ]);
                    $contadorVacaciones++;
                }
            }

            $this->db->commit();
            
            // Redirigir a la página de importación con mensaje de éxito
            header("Location: index.php?controller=importar&action=index&status=success&count_p=$contadorPersonas&count_v=$contadorVacaciones");
            exit;

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->redirigirConError('index', 'Error en BD al ejecutar: ' . $e->getMessage());
        }
    }

    // --- Funciones de Ayuda ---

    private function redirigirConError($action, $mensaje) {
        header("Location: index.php?controller=importar&action=$action&status=error&msg=" . urlencode($mensaje));
        exit;
    }

    private function clean_value_php($value, $is_numeric = false) {
        if ($value === null) return $is_numeric ? null : "";
        // Limpia espacios, comillas simples y el espacio raro ' ' (nbsp)
        $cleaned = trim(str_replace(['\xa0', "'", ' '], [' ', "''", ' '], (string)$value));
        if ($is_numeric) {
            $cleaned = preg_replace('/[^0-9]/', '', $cleaned);
            return $cleaned === '' ? null : $cleaned;
        }
        return $cleaned;
    }

    private function get_initials_php($full_name) {
        $parts = explode(' ', (string)$full_name);
        $initials = "";
        foreach ($parts as $part) {
            if (!empty($part) && ctype_alpha(mb_substr($part, 0, 1))) { // mb_substr para caracteres UTF-8
                $initials .= mb_substr($part, 0, 1);
            }
        }
        return strtoupper($initials);
    }

    // Parsea fechas de formato 'd/m/Y' o 'j/m/Y'
    private function parse_date_php($date_str, $format = 'j/m/Y') {
        try {
            // --- CAMBIO AQUÍ --- Usamos 'j' en lugar de 'd' para aceptar días como '4' o '04'
            $d = DateTime::createFromFormat($format, trim($date_str));
            // Verifica que la fecha parseada coincida con el string original
            if ($d && $d->format($format) === trim($date_str)) {
                return $d;
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Formatea de 'd/m/Y' a 'Y-m-d'
    private function format_date_php($date_str_dmy) {
        // --- CAMBIO AQUÍ --- Usamos 'j/m/Y' para el parseo
        $d = $this->parse_date_php($date_str_dmy, 'j/m/Y');
        return $d ? $d->format('Y-m-d') : null;
    }
}