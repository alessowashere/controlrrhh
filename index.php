<?php
// ---- AÑADE ESTO ----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --------------------

// El resto de tu código...
// index.php (Enrutador Front-Controller)

// --- TEMPORARY: FOR DEBUGGING ONLY ---
// Comment these out once the errors are fixed!
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
// --- END DEBUGGING ---

session_start(); // Start session for potential future use (like login messages)

// --- Core Includes ---
// Adjust paths if your config folder is elsewhere
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

// --- Routing Logic ---

// 1. Determine the controller and action from URL parameters
// Default to DashboardController and index action if not specified

// --- CAMBIO AQUÍ (Líneas 28 y 29): Reemplazado FILTER_SANITIZE_STRING ---
$controllerName = isset($_GET['controller']) ? strip_tags(ucfirst(strtolower($_GET['controller']))) : 'Dashboard';
$actionName = isset($_GET['action']) ? strip_tags(strtolower($_GET['action'])) : 'index';

// Basic security check: Ensure controller/action names are simple alphanumeric
if (!preg_match('/^[a-zA-Z0-9_]+$/', $controllerName) || !preg_match('/^[a-zA-Z0-9_]+$/', $actionName)) {
    die('Error: Invalid controller or action name.');
}

// 2. Construct the path to the controller file
$controllerFile = __DIR__ . '/controllers/' . $controllerName . 'Controller.php';

// 3. Validate if the controller file exists
if (file_exists($controllerFile)) {
    require_once $controllerFile; // Include the controller file

    // 4. Construct the full class name
    $controllerClassName = $controllerName . 'Controller';

    // 5. Validate if the class exists within the included file
    if (class_exists($controllerClassName)) {
        // 6. Create an instance of the controller
        try {
            $controller = new $controllerClassName();
        } catch (Exception $e) {
             // Handle potential constructor errors (e.g., DB connection issue caught)
             error_log("Error creating controller '{$controllerClassName}': " . $e->getMessage());
             die("Error: Could not initialize the application component.");
        }


        // 7. Validate if the method (action) exists in the controller instance
        if (method_exists($controller, $actionName)) {
            // 8. Call the action method
            try {
                $controller->$actionName();
            } catch (Exception $e) {
                 // Handle potential errors during action execution
                 error_log("Error executing action '{$actionName}' in controller '{$controllerClassName}': " . $e->getMessage());
                 // Display a user-friendly error page or message
                 // For now, just show a generic error
                 require 'views/layout/header.php'; // Load header for consistent look
                 echo '<div class="alert alert-danger m-4">Error: An application error occurred. Please try again later.</div>';
                 require 'views/layout/footer.php'; // Load footer
                 // Alternatively, redirect to an error page:
                 // header('Location: index.php?controller=error&action=show'); exit;
            }
        } else {
            // Error: Action method not found
            error_log("Action '{$actionName}' not found in controller '{$controllerClassName}'.");
            // Display a 404 Not Found error page or message
            require 'views/layout/header.php';
            echo '<div class="alert alert-warning m-4">Error 404: The requested page action was not found.</div>';
            require 'views/layout/footer.php';
            // header('Location: index.php?controller=error&action=notFound'); exit;
        }
    } else {
        // Error: Controller class not found within the file
        error_log("Class '{$controllerClassName}' not found in file '{$controllerFile}'.");
        require 'views/layout/header.php';
        echo '<div class="alert alert-danger m-4">Error: Application component not found.</div>';
        require 'views/layout/footer.php';
        // header('Location: index.php?controller=error&action=notFound'); exit;
    }
} else {
    // Error: Controller file not found
    error_log("Controller file '{$controllerFile}' not found for controller '{$controllerName}'.");
    require 'views/layout/header.php';
    echo '<div class="alert alert-warning m-4">Error 404: The requested page controller was not found.</div>';
    require 'views/layout/footer.php';
    // header('Location: index.php?controller=error&action=notFound'); exit;
}
?>