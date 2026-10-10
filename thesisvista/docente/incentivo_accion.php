<?php

require_once __DIR__ . '/includes/inicio.php';

$id = (int) ($_POST['id'] ?? 0);
$volver = (string) ($_POST['volver'] ?? '');
if (!preg_match('/^[a-z_]+\.php(\?[^\s]*)?(#[a-z]+)?$/i', $volver)) $volver = 'incentivo.php?id=' . $id;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirigir('incentivos.php');
exigir_csrf($volver);

$pdo = conectar();
$inc = incentivo_para_docente($id_docente, $id);
if (!$inc) {
    mensaje('error', 'El incentivo no existe o no pertenece a sus cursos.');
    redirigir('incentivos.php');
}

try {
    if (($_POST['accion'] ?? '') === 'otorgar') {
        $grupo = grupo_para_docente($id_docente, (int) ($_POST['id_grupo'] ?? 0));
        if (!$grupo || (int) $grupo['id_curso'] !== (int) $inc['id_curso']) throw new InvalidArgumentException('El grupo no pertenece al curso del incentivo.');
        if ($inc['estado'] !== 'Activo') throw new InvalidArgumentException('El incentivo no está activo.');
        $est = (int) ($_POST['id_estudiante'] ?? 0) ?: null;
        if ($est && !in_array($est, array_map('intval', array_column(integrantes_grupo((int) $grupo['id_grupo']), 'usuario_id')), true)) {
            throw new InvalidArgumentException('El estudiante no pertenece al grupo.');
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM incentivo_otorgado WHERE id_incentivo_grupal = ? AND id_grupo = ? AND id_estudiante <=> ?');
        $stmt->execute([$id, $grupo['id_grupo'], $est]);
        if ((int) $stmt->fetchColumn() > 0) throw new InvalidArgumentException('Ya había otorgado este incentivo a ese destinatario.');

        $obs = mb_substr(trim((string) ($_POST['observacion'] ?? '')), 0, 1000) ?: null;
        $pdo->prepare('INSERT INTO incentivo_otorgado (id_incentivo_grupal, id_grupo, id_estudiante, id_profesor, observacion) VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $grupo['id_grupo'], $est, $id_docente, $obs]);
        mensaje('exito', 'Incentivo «' . $inc['nombre'] . '» otorgado ' . ($est ? 'a un estudiante de ' : 'al grupo ') . $grupo['nombre_grupo'] . '.');
    } elseif (($_POST['accion'] ?? '') === 'revocar') {
        $stmt = $pdo->prepare('DELETE FROM incentivo_otorgado WHERE id_otorgado = ? AND id_incentivo_grupal = ? AND id_profesor = ?');
        $stmt->execute([(int) ($_POST['id_otorgado'] ?? 0), $id, $id_docente]);
        mensaje($stmt->rowCount() ? 'exito' : 'error', $stmt->rowCount() ? 'Incentivo retirado.' : 'Solo puede quitar los incentivos que usted otorgó.');
    }
} catch (InvalidArgumentException $ex) {
    mensaje('error', $ex->getMessage());
} catch (PDOException $ex) {
    error_log('incentivo_accion: ' . $ex->getMessage());
    mensaje('error', ($ex->errorInfo[0] ?? '') === '45000' ? $ex->errorInfo[2] : 'No se pudo guardar.');
}
redirigir($volver);
