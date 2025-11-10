<?php
// config/config.php

// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
// (Usa los datos de la BD que creamos)
define('DB_HOST', 'localhost');
define('DB_NAME', 'rrhh-control-vac');
define('DB_USER', 'admin');
define('DB_PASS', 'Redlabel@'); // Cambia esto por tu contraseña de MySQL
define('DB_CHARSET', 'utf8mb4');

// --- CONFIGURACIÓN DE LA APLICACIÓN ---
// (URL base de tu proyecto. ¡IMPORTANTE AJUSTAR ESTO!)
// Si tu proyecto está en http://localhost/proyecto_vacaciones/
// entonces la base URL es '/proyecto_vacaciones/'
define('BASE_URL', '/control-rrhh/');

// Configuración de zona horaria
date_default_timezone_set('America/Lima');