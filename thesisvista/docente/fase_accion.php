<?php

require_once __DIR__ . '/includes/inicio.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigir('fases.php');
exigir_csrf('fases.php');

$pdo  = conectar();
$id   = (int) ($_POST['id'] ?? 0);
$fase = fase_para_docente($id_docente, $id);
if (!$fase) {
    mensaje('error', 'La fase no existe o no pertenece a sus cursos.');
    redirigir('fases.php');
}
$id_curso = (int) $fase['id_curso'];
$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'subir':
        case 'bajar':
            
            $ids = array_map('intval', array_column(fases_de_curso($id_curso), 'id_fase'));
            $pos = array_search($id, $ids, true);
            $otra = $accion === 'subir' ? $pos - 1 : $pos + 1;
            if ($pos !== false && isset($ids[$otra])) {
                [$ids[$pos], $ids[$otra]] = [$ids[$otra], $ids[$pos]];
                $pdo->beginTransaction();
                $upd = $pdo->prepare('UPDATE fases SET orden = ? WHERE id_fase = ? AND id_curso = ?');
                foreach ($ids as $i => $id_f) $upd->execute([$i + 1, $id_f, $id_curso]);
                $pdo->commit();
                mensaje('exito', 'Orden de las fases actualizado.');
            }
            redirigir('fases.php?curso=' . $id_curso);

        case 'estado':
            $estado = $_POST['estado'] ?? '';
            if (!isset(ESTADOS_FASE[$estado])) {
                mensaje('error', 'Estado no válido.');
                redirigir('fase.php?id=' . $id);
            }
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE fases SET estado = ?, activa = ?, fecha_modificacion = NOW() WHERE id_fase = ?')
                ->execute([$estado, $estado === 'Borrador' ? 0 : 1, $id]);
            foreach (ids_grupos_de_fase($id) as $g) sincronizar_tesis_fase($id, $g);
            $pdo->commit();
            mensaje('exito', 'La fase ahora está: ' . $estado . '.');
            redirigir('fase.php?id=' . $id);

        case 'eliminar':
            $motivo = motivo_no_eliminar_fase($id_docente, $fase);
            if ($motivo) {
                mensaje('error', $motivo);
                redirigir('fase.php?id=' . $id);
            }
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE incentivos_grupales SET id_fase = NULL WHERE id_fase = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM tesis_fase WHERE id_fase = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM fase_grupo WHERE id_fase = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM criterios_fase WHERE id_fase = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM fases WHERE id_fase = ? AND id_profesor_creador = ?')->execute([$id, $id_docente]);
            // Reordenar las que quedan
            $upd = $pdo->prepare('UPDATE fases SET orden = ? WHERE id_fase = ?');
            foreach (array_column(fases_de_curso($id_curso), 'id_fase') as $i => $id_f) $upd->execute([$i + 1, $id_f]);
            $pdo->commit();
            if ($fase['archivo_guia']) @unlink(CARPETA_UPLOADS . '/guias/' . basename($fase['archivo_guia']));
            mensaje('exito', 'Fase «' . $fase['nombre_fase'] . '» eliminada.');
            redirigir('fases.php?curso=' . $id_curso);
    }
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('fase_accion: ' . $ex->getMessage());
    mensaje('error', 'No se pudo completar la acción.');
}
redirigir('fase.php?id=' . $id);
