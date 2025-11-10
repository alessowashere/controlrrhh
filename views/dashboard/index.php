<?php
// views/dashboard/index.php
// Variables: $dashboardData, $errorMessage (optional)

// Extract data safely
$totalActivos = $dashboardData['totalActivos'] ?? 0;
$solicitudesPendientes = $dashboardData['solicitudesPendientes'] ?? 0;
$enVacacionesAhora = $dashboardData['enVacacionesAhora'] ?? [];
$actualmenteVacacionesCount = count($enVacacionesAhora); // Count from the fetched list

$proximasVacaciones = $dashboardData['proximasVacaciones'] ?? [];
$saldosBajos = $dashboardData['saldosBajos'] ?? [];
$saldosAltos = $dashboardData['saldosAltos'] ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard Principal</h1>
</div>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-start-primary shadow-sm h-100 py-2"> <div class="card-body"> <div class="row no-gutters align-items-center">
            <div class="col me-2"> <div class="text-xs fw-bold text-primary text-uppercase mb-1">Empleados Activos</div> <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $totalActivos; ?></div> </div>
            <div class="col-auto"><i class="bi bi-people-fill fs-2 text-secondary opacity-50"></i></div>
        </div></div></div>
    </div>
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-start-warning shadow-sm h-100 py-2 position-relative"> <div class="card-body"> <div class="row no-gutters align-items-center">
            <div class="col me-2"> <div class="text-xs fw-bold text-warning text-uppercase mb-1">Solicitudes Pendientes</div> <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $solicitudesPendientes; ?></div> </div>
            <div class="col-auto"><i class="bi bi-hourglass-split fs-2 text-secondary opacity-50"></i></div>
        </div></div> <?php if ($solicitudesPendientes > 0): ?><a href="index.php?controller=vacacion&action=index&estado_filtro=PENDIENTE" class="stretched-link" title="Ver pendientes"></a><?php endif; ?></div>
    </div>
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-start-info shadow-sm h-100 py-2"> <div class="card-body"> <div class="row no-gutters align-items-center">
            <div class="col me-2"> <div class="text-xs fw-bold text-info text-uppercase mb-1">Actualmente de Vacaciones</div> <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $actualmenteVacacionesCount; ?></div> </div>
            <div class="col-auto"><i class="bi bi-person-walking fs-2 text-secondary opacity-50"></i></div>
        </div></div></div>
    </div>
</div><div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100"> <div class="card-header py-3"><h6 class="m-0 fw-bold text-primary"><i class="bi bi-calendar-event me-2"></i>Próximas Vacaciones (14 días)</h6></div>
            <div class="card-body">
                <?php if (empty($proximasVacaciones)): ?> <p class="text-center text-muted">No hay vacaciones aprobadas próximamente.</p>
                <?php else: ?> <ul class="list-group list-group-flush">
                    <?php foreach ($proximasVacaciones as $vac): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center"> <?php echo htmlspecialchars($vac['nombre_completo'] ?? 'N/A'); ?>
                            <span class="badge bg-light text-dark"> <?php echo htmlspecialchars(date('d/m/y', strtotime($vac['fecha_inicio'] ?? ''))); ?> - <?php echo htmlspecialchars(date('d/m/y', strtotime($vac['fecha_fin'] ?? ''))); ?> </span>
                        </li> <?php endforeach; ?>
                </ul> <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100"> <div class="card-header py-3"><h6 class="m-0 fw-bold text-info"><i class="bi bi-geo-alt-fill me-2"></i>En Vacaciones Ahora</h6></div>
            <div class="card-body">
                 <?php if (empty($enVacacionesAhora)): ?> <p class="text-center text-muted">Nadie se encuentra de vacaciones.</p>
                <?php else: ?> <ul class="list-group list-group-flush">
                    <?php foreach ($enVacacionesAhora as $vac): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center"> <?php echo htmlspecialchars($vac['nombre_completo'] ?? 'N/A'); ?>
                            <span class="badge bg-light text-dark"> Regresa: <?php echo htmlspecialchars(date('d/m/y', strtotime('+1 day', strtotime($vac['fecha_fin'] ?? '')))); ?> </span>
                        </li> <?php endforeach; ?>
                </ul> <?php endif; ?>
            </div>
        </div>
    </div>
</div><div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100"> <div class="card-header py-3"><h6 class="m-0 fw-bold text-danger"><i class="bi bi-battery-alert me-2"></i>Empleados con Saldo Bajo (&lt;= 5 días)</h6></div>
            <div class="card-body">
                <?php if (empty($saldosBajos)): ?> <p class="text-center text-muted">Nadie tiene saldo bajo.</p>
                <?php else: ?> <ul class="list-group list-group-flush">
                    <?php foreach ($saldosBajos as $emp): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center"> <?php echo htmlspecialchars($emp['nombre_completo'] ?? 'N/A'); ?> <span class="badge bg-danger"> <?php echo htmlspecialchars($emp['saldo_calculado'] ?? '?'); ?> días </span></li>
                    <?php endforeach; ?>
                </ul> <small class="d-block text-muted mt-2">Considera el período relevante actual o más próximo.</small> <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100"> <div class="card-header py-3"><h6 class="m-0 fw-bold text-success"><i class="bi bi-battery-full me-2"></i>Empleados con Saldo Alto (&gt;= 25 días)</h6></div>
            <div class="card-body">
                 <?php if (empty($saldosAltos)): ?> <p class="text-center text-muted">Nadie tiene saldo alto.</p>
                <?php else: ?> <ul class="list-group list-group-flush">
                     <?php foreach ($saldosAltos as $emp): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center"> <?php echo htmlspecialchars($emp['nombre_completo'] ?? 'N/A'); ?> <span class="badge bg-success"> <?php echo htmlspecialchars($emp['saldo_calculado'] ?? '?'); ?> días </span></li>
                    <?php endforeach; ?>
                </ul> <small class="d-block text-muted mt-2">Considera el período relevante actual o más próximo.</small> <?php endif; ?>
            </div>
        </div>
    </div>
</div><div class="row"> <div class="col-12 mb-4"> <div class="card shadow-sm">
    <div class="card-header py-3"><h6 class="m-0 fw-bold text-secondary"><i class="bi bi-lightning-fill me-2"></i>Accesos Rápidos</h6></div>
    <div class="card-body text-center">
         <a href="index.php?controller=vacacion&action=create" class="btn btn-primary me-2 my-1"><i class="bi bi-plus-lg me-1"></i> Registrar Vacación</a>
         <a href="index.php?controller=periodo&action=create" class="btn btn-outline-primary me-2 my-1"><i class="bi bi-calendar-plus me-1"></i> Crear Período Manual</a>
         <a href="index.php?controller=persona&action=create" class="btn btn-outline-secondary my-1"><i class="bi bi-person-plus-fill me-1"></i> Añadir Empleado</a>
    </div>
</div></div></div>

<style>.border-start-primary{border-left:4px solid var(--bs-primary)!important}.border-start-warning{border-left:4px solid var(--bs-warning)!important}.border-start-info{border-left:4px solid var(--bs-info)!important}.text-xs{font-size:.75rem}</style>