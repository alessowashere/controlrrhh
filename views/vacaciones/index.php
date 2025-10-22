<?php
// views/vacaciones/index.php
// Variables: $listaVacaciones, $listaAnios, $search_nombre, $search_area, $anio_inicio_filtro, $errorMessage
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Registro de Vacaciones</h1>
    <a href="index.php?controller=vacacion&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Registrar Vacaciones
    </a>
</div>

<?php // --- BLOQUE DE NOTIFICACIONES --- ?>
<?php if (isset($_GET['status'])): ?>
    <?php
    $status = $_GET['status'];
    $mensaje = '';
    $tipoAlerta = 'success'; // Default

    switch ($status) {
        // --- Mensajes NUEVOS ---
        case 'aprobado':
            $mensaje = 'Solicitud de vacaciones APROBADA con éxito.';
            break;
        case 'rechazado':
            $mensaje = 'Solicitud de vacaciones RECHAZADA con éxito.';
            $tipoAlerta = 'info';
            break;
        case 'error_estado':
            $mensaje = 'Error: No se pudo actualizar el estado de la solicitud.';
            $tipoAlerta = 'danger';
            break;

        // --- Mensajes existentes ---
        case 'creado':
            $mensaje = 'Registro de vacaciones creado con éxito.';
            break;
        case 'actualizado':
            $mensaje = 'Registro de vacaciones actualizado con éxito.';
            break;
        case 'eliminado':
            $mensaje = 'Registro de vacaciones eliminado correctamente.';
            break;
        case 'error_id':
            $mensaje = 'Error: ID no válido o no proporcionado.';
            $tipoAlerta = 'warning';
            break;
        case 'error_excepcion':
        case 'error_eliminar':
            $mensaje = 'Ocurrió un error inesperado. Contacte al administrador.';
            $tipoAlerta = 'danger';
            break;
    }
    
    if ($mensaje):
    ?>
    <div class="alert alert-<?php echo $tipoAlerta; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensaje); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
<?php endif; ?>


<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
     <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Buscar / Filtrar Vacaciones</h6>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="controller" value="vacacion">
            <input type="hidden" name="action" value="index">

            <div class="col-md-4">
                <label for="search_nombre" class="form-label">Nombre Empleado:</label>
                <input type="text" class="form-control" id="search_nombre" name="search_nombre" value="<?php echo htmlspecialchars($search_nombre ?? ''); ?>">
            </div>

            <div class="col-md-3">
                <label for="search_area" class="form-label">Área / Lugar:</label>
                <input type="text" class="form-control" id="search_area" name="search_area" value="<?php echo htmlspecialchars($search_area ?? ''); ?>">
            </div>

            <div class="col-md-3">
                <label for="anio_inicio_filter" class="form-label">Período (Año Inicio):</label>
                <select name="anio_inicio" id="anio_inicio_filter" class="form-select">
                    <option value="">-- Todos --</option>
                    <?php if (isset($listaAnios) && is_array($listaAnios)): ?>
                        <?php foreach ($listaAnios as $anioInfo): ?>
                            <?php
                                $valor = $anioInfo['filter_value'] ?? ''; // YEAR
                                $texto = $anioInfo['display_text'] ?? 'Inválido';
                                $current_filter = $anio_inicio_filtro ?? null;
                                $selected = ($current_filter == $valor && $valor !== '') ? 'selected' : '';
                            ?>
                            <option value="<?php echo htmlspecialchars($valor); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($texto); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-search me-1"></i>Buscar
                </button>
                 <a href="index.php?controller=vacacion&action=index" class="btn btn-outline-secondary w-100 mt-2">Limpiar</a>
            </div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
     <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">Resultados</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Empleado</th>
                        <th>Área / Lugar</th> <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th class="text-center">Días</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!isset($listaVacaciones) || empty($listaVacaciones)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No se encontraron vacaciones (o para los filtros aplicados).</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listaVacaciones as $vacacion): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vacacion['nombre_completo'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vacacion['area'] ?? 'N/A'); ?></td> 
                                <td><?php echo htmlspecialchars($vacacion['fecha_inicio'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($vacacion['fecha_fin'] ?? 'N/A'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($vacacion['dias_tomados'] ?? 'N/A'); ?></td>
                                <td class="text-center">
                                    <?php
                                        $estado = htmlspecialchars($vacacion['estado'] ?? 'N/A');
                                        $claseBadge = 'text-bg-secondary'; // Default
                                        if ($estado == 'GOZADO') $claseBadge = 'text-bg-success';
                                        if ($estado == 'PENDIENTE') $claseBadge = 'text-bg-warning';
                                        if ($estado == 'RECHAZADO') $claseBadge = 'text-bg-danger';
                                        if ($estado == 'APROBADO') $claseBadge = 'text-bg-info';
                                    ?>
                                    <span class="badge <?php echo $claseBadge; ?>"><?php echo $estado; ?></span>
                                </td>
                                
                                <?php // --- LÓGICA DE BOTONES CORREGIDA --- ?>
                                <td class="text-center">
                                    <?php $vacacion_id = $vacacion['id'] ?? null; ?>
                                    
                                    <?php if ($vacacion_id): ?>
                                        
                                        <?php if ($estado == 'PENDIENTE'): ?>
                                            
                                            <a href="index.php?controller=vacacion&action=aprobar&id=<?php echo $vacacion_id; ?>"
                                               class="btn btn-success btn-sm" 
                                               title="Aprobar"
                                               onclick="return confirm('¿Estás seguro de que deseas APROBAR esta solicitud?');">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                            
                                            <a href="index.php?controller=vacacion&action=rechazar&id=<?php echo $vacacion_id; ?>"
                                               class="btn btn-danger btn-sm" 
                                               title="Rechazar"
                                               onclick="return confirm('¿Estás seguro de que deseas RECHAZAR esta solicitud?');">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                        
                                        <?php else: ?>
                                            
                                            <?php // --- ¡CAMBIO AQUÍ! ---
                                                  // Se eliminó el "if ($estado != 'GOZADO')"
                                                  // El botón "Editar" ahora aparece para GOZADO, APROBADO, RECHAZADO
                                            ?>
                                            <a href="index.php?controller=vacacion&action=edit&id=<?php echo $vacacion_id; ?>" class="btn btn-warning btn-sm" title="Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            
                                            <a href="index.php?controller=vacacion&action=delete&id=<?php echo $vacacion_id; ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               title="Cancelar/Eliminar"
                                               onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                            
                                        <?php endif; // Fin del if/else 'PENDIENTE' ?>
                                        
                                    <?php endif; // Fin del if $vacacion_id ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>