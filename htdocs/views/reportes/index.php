
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Módulo de Reportería</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Seleccione un Reporte</h6>
        </div>
        <div class="card-body">
            
            <p>Seleccione el tipo de reporte que desea generar. Los resultados se mostrarán en una vista previa lista para imprimir.</p>

            <form action="index.php?controller=Reporte&action=generar" method="POST" target="_blank" class="mb-3">
                <input type="hidden" name="tipo_reporte" value="general">
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <i class="fas fa-globe mr-2"></i> Reporte General (Todos los Empleados)
                </button>
            </form>

            <form action="index.php?controller=Reporte&action=generar" method="POST" target="_blank" class="mb-3">
                <input type="hidden" name="tipo_reporte" value="por_persona">
                <div class="form-group">
                    <label for="empleado_id">Seleccionar Empleado:</label>
                    <select name="empleado_id" id="empleado_id" class="form-control" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($empleados as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo htmlspecialchars($emp['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label>Desde:</label>
                        <input type="date" name="fecha_inicio" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label>Hasta:</label>
                        <input type="date" name="fecha_fin" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-info btn-lg btn-block mt-3">
                    <i class="fas fa-user mr-2"></i> Reporte por Persona
                </button>
            </form>

            <form action="index.php?controller=Reporte&action=generar" method="POST" target="_blank" class="mb-3">
                <input type="hidden" name="tipo_reporte" value="por_periodo">
                <div class="row">
                    <div class="col-md-6">
                        <label for="periodo_inicio">Desde:</label>
                        <input type="date" name="fecha_inicio" id="periodo_inicio" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="periodo_fin">Hasta:</label>
                        <input type="date" name="fecha_fin" id="periodo_fin" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning btn-lg btn-block mt-3 text-dark">
                    <i class="fas fa-calendar-alt mr-2"></i> Reporte por Período
                </button>
            </form>

            <form action="index.php?controller=Reporte&action=generar" method="POST" target="_blank" class="mb-3">
                <input type="hidden" name="tipo_reporte" value="saldos">
                <button type="submit" class="btn btn-danger btn-lg btn-block">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Reporte de Saldos y Pendientes
                </button>
            </form>

        </div>
    </div>
</div>

<script>
// Pequeño script para evitar enviar formularios vacíos si se usan fechas
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[target="_blank"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Si el formulario se abre en una nueva pestaña (_blank),
            // podemos simplemente dejar que se envíe.
            // La validación 'required' de HTML5 se encargará.
        });
    });
});
</script>