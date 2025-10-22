<?php
// config/config.php

// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
// (Usa los datos de la BD que creamos)
define('DB_HOST', 'sql100.infinityfree.com');
define('DB_NAME', 'if0_40212744_vac');
define('DB_USER', 'if0_40212744');
define('DB_PASS', 'ONEPIECE11cop'); // Cambia esto por tu contraseña de MySQL
define('DB_CHARSET', 'utf8mb4');

// --- CONFIGURACIÓN DE LA APLICACIÓN ---
// (URL base de tu proyecto. ¡IMPORTANTE AJUSTAR ESTO!)
// Si tu proyecto está en http://localhost/proyecto_vacaciones/
// entonces la base URL es '/proyecto_vacaciones/'
define('BASE_URL', '/');

// Configuración de zona horaria
date_default_timezone_set('America/Lima');