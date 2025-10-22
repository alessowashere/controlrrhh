<div class="alert alert-info">
    <h4>Datos del Empleado</h4>
    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($info_empleado['nombre_completo']); ?></p>
    <p><strong>Puesto:</strong> <?php echo htmlspecialchars($info_empleado['puesto']); ?></p>
    <p><strong>Fecha de Ingreso:</strong> <?php echo date('d/m/Y', strtotime($info_empleado['fecha_ingreso'])); ?></p>
</div>

<hr>

<h5>Detalle de Movimientos</h5>
<div class="table-responsive">
    <table class="table table-bordered table-striped" width="100%" cellspacing="0">
        <thead>
            <tr>
                <th>Fecha Solicitud</th>
                <th>Días Tomados</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($resultados)): ?>
                <tr>
                    <td colspan="3" class="text-center">No se encontraron movimientos para esta persona (o en este período).</td>
                </tr>
            <?php else: ?>
                <?php foreach ($resultados as $fila): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($fila['fecha_solicitud'])); ?></td>
                        <td><?php echo $fila['dias_tomados']; ?></td>
                        <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>