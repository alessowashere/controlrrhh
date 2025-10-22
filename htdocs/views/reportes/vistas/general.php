<div class="table-responsive">
    <table class="table table-bordered table-striped" width="100%" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Puesto</th>
                <th>Días Pendientes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($resultados)): ?>
                <tr>
                    <td colspan="4" class="text-center">No se encontraron resultados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($resultados as $fila): ?>
                    <tr>
                        <td><?php echo $fila['id']; ?></td>
                        <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($fila['puesto']); ?></td>
                        <td><?php echo $fila['dias_pendientes']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>