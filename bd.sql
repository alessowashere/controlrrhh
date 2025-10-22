/* ---------------------------------------------------- */
/* ---      SCRIPT BASE DE DATOS RRHH VACACIONES    --- */
/* ---------------------------------------------------- */

-- 1. CREACIÓN DE LA BASE DE DATOS
CREATE DATABASE IF NOT EXISTS rrhh_vacaciones 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_general_ci;

-- 2. USAR LA BASE DE DATOS
USE rrhh_vacaciones;

/* ---------------------------------------------------- */
/* ---            TABLA DE PERSONAS                 --- */
/* (Modificada para la carga de datos del CSV)      --- */
/* ---------------------------------------------------- */
CREATE TABLE IF NOT EXISTS personas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  
  /* El DNI, identificador único a largo plazo. */
  /* Se permite NULL al inicio para poder cargar los datos antes de tener el DNI. */
  dni VARCHAR(30) UNIQUE NULL,
  
  /* El "N°" del archivo CSV. */
  /* Actúa como un "puente" para enlazar las vacaciones con la persona */
  /* hasta que tengas todos los DNI. Debe ser único. */
  numero_empleado VARCHAR(20) UNIQUE,
  
  /* Campo unificado que viene del CSV (ej. "ACHAHUI LOZANO GRETEL") */
  nombre_completo VARCHAR(200) NOT NULL,
  
  /* Columna "CARGO" del CSV */
  cargo VARCHAR(100),
  
  /* Columna "LUGAR" del CSV */
  area VARCHAR(100),
  
  /* Campo opcional para el futuro */
  fecha_ingreso DATE NULL,
  
  estado ENUM('ACTIVO','CESADO') DEFAULT 'ACTIVO',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* ---------------------------------------------------- */
/* ---             TABLA DE FERIADOS                --- */
/* ---------------------------------------------------- */
CREATE TABLE IF NOT EXISTS feriados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  descripcion VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* ---------------------------------------------------- */
/* ---        TABLA DE PERIODOS DE DERECHO          --- */
/* (Define el derecho a vacaciones, ej. "Periodo 2025")*/
/* ---------------------------------------------------- */
CREATE TABLE IF NOT EXISTS periodos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  persona_id INT NOT NULL,
  periodo_inicio DATE,
  periodo_fin DATE,
  total_dias INT DEFAULT 30, /* Días totales a los que tiene derecho */
  dias_usados INT DEFAULT 0, /* Contador de días que va tomando */
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (persona_id) REFERENCES personas(id) 
    ON DELETE CASCADE
);

/* ---------------------------------------------------- */
/* ---        TABLA DE VACACIONES TOMADAS           --- */
/* (Registra cada rango de fechas que el empleado goza) */
/* ---------------------------------------------------- */
CREATE TABLE IF NOT EXISTS vacaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  persona_id INT NOT NULL,
  periodo_id INT NOT NULL, /* A qué periodo pertenecen estas vacaciones */
  fecha_inicio DATE,
  fecha_fin DATE,
  dias_tomados INT,        /* Los días que procesamos del CSV */
  
  tipo ENUM('NORMAL','PENDIENTE','ADELANTO') DEFAULT 'NORMAL',
  estado ENUM('PENDIENTE','GOZADO','APROBADO','RECHAZADO') DEFAULT 'PENDIENTE',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (persona_id) REFERENCES personas(id) 
    ON DELETE CASCADE,
  FOREIGN KEY (periodo_id) REFERENCES periodos(id) 
    ON DELETE CASCADE
);