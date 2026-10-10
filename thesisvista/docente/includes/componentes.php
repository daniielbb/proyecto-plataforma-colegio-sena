<?php



function icono(string $nombre, int $tam = 18): string
{
    $p = [
        'inicio'     => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'proyecto'   => '<path d="M4 4h11l5 5v11H4z"/><path d="M15 4v5h5"/><path d="M8 13h8M8 17h5"/>',
        'cursos'     => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
        'fases'      => '<path d="M3 6h6l2 2h10v11H3z"/><path d="M3 10h18"/>',
        'carpeta'    => '<path d="M3 6h6l2 2h10v11H3z"/>',
        'avance'     => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 15l3-4 3 2 5-6"/>',
        'trabajos'   => '<path d="M6 3h9l4 4v14H6z"/><path d="M9 12l2 2 4-4"/>',
        'nota'       => '<path d="M12 3l2.6 5.6 6 .6-4.5 4 1.3 6L12 16l-5.4 3.2 1.3-6-4.5-4 6-.6z"/>',
        'comentario' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/>',
        'incentivo'  => '<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4"/><path d="M12 13v4M8 21h8M9 17h6v4H9z"/>',
        'perfil'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
        'salir'      => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h10"/>',
        'grupo'      => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c.5-3.5 3-5 6-5s5.5 1.5 6 5"/><path d="M15 15c2.5 0 4.5 1.5 5 4"/>',
        'calendario' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'archivo'    => '<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4"/>',
        'mas'        => '<path d="M12 5v14M5 12h14"/>',
        'editar'     => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 6l4 4"/>',
        'arriba'     => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'abajo'      => '<path d="M12 5v14M6 13l6 6 6-6"/>',
        'ojo'        => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'reloj'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'      => '<path d="M5 12l5 5 9-10"/>',
        'descargar'  => '<path d="M12 4v11M7 10l5 5 5-5"/><path d="M5 20h14"/>',
    ][$nombre] ?? '<circle cx="12" cy="12" r="8"/>';
    return '<svg class="ico" width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function etiqueta(string $estado): string
{
    return '<span class="etiqueta ' . clase_estado_avance($estado) . '">' . e($estado) . '</span>';
}

function barra(int $pct, bool $con_texto = true, string $extra = ''): string
{
    $pct = max(0, min(100, $pct));
    $clase = $pct >= 100 ? 'lleno' : ($pct >= 60 ? 'alto' : ($pct > 0 ? 'medio' : 'cero'));
    return '<div class="d-barra ' . $clase . ' ' . e($extra) . '" role="progressbar" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100">'
        . '<div class="d-barra-pista"><span style="width:' . $pct . '%"></span></div>'
        . ($con_texto ? '<strong>' . $pct . '%</strong>' : '') . '</div>';
}


function limite(?string $fecha, bool $terminada = false): string
{
    if (!$fecha) return '<span class="d-tenue">Sin fecha límite</span>';
    $d = dias_restantes($fecha);
    $txt = fecha_corta($fecha);
    if ($terminada) return e($txt);
    if ($d < 0)  return e($txt) . ' <span class="d-plazo vencido">vencida hace ' . abs($d) . ' d</span>';
    if ($d === 0) return e($txt) . ' <span class="d-plazo hoy">vence hoy</span>';
    if ($d <= 5) return e($txt) . ' <span class="d-plazo pronto">faltan ' . $d . ' d</span>';
    return e($txt);
}


 
function linea_fases(array $fases, int $id_grupo): string
{
    if (!$fases) return '<p class="vacio">Este grupo todavía no tiene fases asignadas.</p>';
    $html = '<ol class="d-linea">';
    foreach ($fases as $f) {
        $clase = fase_terminada($f['estado']) ? 'hecha' : ($f['estado'] === 'Pendiente' ? 'pendiente' : ($f['estado'] === 'Requiere corrección' ? 'alerta' : 'actual'));
        $html .= '<li class="' . $clase . '"><a href="grupo_fase.php?grupo=' . $id_grupo . '&amp;fase=' . (int) $f['id_fase'] . '">'
            . '<span class="d-linea-punto">' . simbolo_estado($f['estado']) . '</span>'
            . '<span class="d-linea-texto"><small>FASE ' . (int) $f['orden'] . '</small><strong>' . e($f['nombre_fase']) . '</strong>'
            . '<span>' . etiqueta($f['estado']) . ' · ' . limite($f['fecha_limite'], fase_terminada($f['estado']))
            . ((int) $f['total_entregas'] ? ' · ' . (int) $f['total_entregas'] . ' entrega(s)' : '')
            . ($f['nota_grupo'] !== null ? ' · Nota <b>' . formato_nota($f['nota_grupo']) . '</b>' : '') . '</span></span>'
            . '<span class="d-linea-barra">' . barra($f['porcentaje']) . '</span>'
            . '</a></li>';
    }
    return $html . '</ol>';
}


function lista_comentarios(array $comentarios, int $id_docente, bool $contexto = false, ?string $volver = null): string
{
    if (!$comentarios) return '<p class="vacio">Aún no hay comentarios.</p>';
    $html = '<ul class="d-comentarios">';
    foreach ($comentarios as $c) {
        $html .= '<li class="tipo-' . e(strtolower(str_replace(['ó', ' '], ['o', '-'], $c['tipo']))) . '">'
            . '<div class="d-comentario-cab"><span class="avatar chico">' . e(mb_strtoupper(mb_substr($c['nombre'], 0, 1))) . '</span>'
            . '<div><strong>' . e($c['nombre'] . ' ' . $c['apellido']) . '</strong> <span class="d-tipo">' . e($c['tipo']) . '</span><br>'
            . '<small>' . e(fecha_hora($c['fecha'])) . ($c['fecha_edicion'] ? ' · editado ' . e(hace($c['fecha_edicion'])) : '') . '</small></div></div>'
            . '<p>' . nl2br(e($c['comentario'])) . '</p>'
            . '<div class="d-comentario-pie">';
        $partes = [];
        if ($contexto) $partes[] = e($c['nombre_curso'] . ' · ' . $c['nombre_grupo']);
        if ($contexto && $c['nombre_fase']) $partes[] = 'Fase ' . (int) $c['orden'] . ' · ' . e($c['nombre_fase']);
        if ($c['est_nombre']) $partes[] = 'Para: ' . e($c['est_nombre'] . ' ' . $c['est_apellido']);
        if ($c['version']) $partes[] = 'Trabajo: ' . e($c['nombre_original']) . ' (v' . (int) $c['version'] . ')';
        if (!$c['est_nombre']) $partes[] = 'Para: todo el grupo';
        $html .= '<small>' . implode(' · ', $partes) . '</small>';
        if ($contexto && $c['id_fase']) {
            $html .= ' <a class="d-enlace" href="grupo_fase.php?grupo=' . (int) $c['id_grupo'] . '&amp;fase=' . (int) $c['id_fase'] . '">Abrir</a>';
        }
        if ((int) $c['id_profesor'] === $id_docente && $volver) {
            $html .= ' <button type="button" class="d-enlace" data-abrir="editar-com-' . (int) $c['id_retro'] . '">Editar</button>'
                . '<dialog class="d-modal" id="editar-com-' . (int) $c['id_retro'] . '"><form method="post" action="comentario_accion.php" class="formulario">'
                . campo_csrf() . '<input type="hidden" name="id" value="' . (int) $c['id_retro'] . '"><input type="hidden" name="volver" value="' . e($volver) . '">'
                . '<h3>Editar comentario</h3><label>Tipo</label><select name="tipo">';
            foreach (TIPOS_COMENTARIO as $t) $html .= '<option ' . ($t === $c['tipo'] ? 'selected' : '') . '>' . e($t) . '</option>';
            $html .= '</select><label>Comentario</label><textarea name="comentario" required maxlength="5000">' . e($c['comentario']) . '</textarea>'
                . '<div class="botones"><button class="btn btn-primario">Guardar cambios</button><button type="button" class="btn btn-secundario" data-cerrar>Cancelar</button></div></form></dialog>';
        }
        $html .= '</div></li>';
    }
    return $html . '</ul>';
}


function tabla_calificaciones(array $notas, bool $contexto = true): string
{
    if (!$notas) return '<p class="vacio">Aún no hay calificaciones registradas.</p>';
    $html = '<div class="tabla-contenedor"><table><thead><tr>'
        . ($contexto ? '<th>Curso / grupo</th>' : '') . '<th>Fase</th><th>Para</th><th>Nota</th><th>Docente</th><th>Fecha</th><th></th></tr></thead><tbody>';
    foreach ($notas as $n) {
        $clase = (float) $n['nota'] >= NOTA_APROBATORIA ? 'aprobada' : 'baja';
        $html .= '<tr>'
            . ($contexto ? '<td>' . e($n['nombre_curso']) . ' · <strong>' . e($n['nombre_grupo']) . '</strong></td>' : '')
            . '<td>Fase ' . (int) $n['orden'] . ' · ' . e($n['nombre_fase']) . '</td>'
            . '<td>' . ($n['est_nombre'] ? e($n['est_nombre'] . ' ' . $n['est_apellido']) : '<em>Todo el grupo</em>')
            . ($n['version'] ? '<br><small>Trabajo: ' . e($n['nombre_original']) . ' v' . (int) $n['version'] . '</small>' : '') . '</td>'
            . '<td><span class="d-nota ' . $clase . '">' . formato_nota($n['nota']) . '</span></td>'
            . '<td><small>' . e($n['nombre'] . ' ' . $n['apellido']) . '</small></td>'
            . '<td><small>' . e(fecha_hora($n['fecha_modificacion'] ?? $n['fecha'])) . ($n['fecha_modificacion'] ? '<br>(editada)' : '') . '</small></td>'
            . '<td><a class="btn btn-secundario btn-chico" href="grupo_fase.php?grupo=' . (int) $n['id_grupo'] . '&amp;fase=' . (int) $n['id_fase'] . '#calificacion">Ver</a></td>'
            . '</tr>';
    }
    return $html . '</tbody></table></div>';
}


function lista_actividad(array $actividad): string
{
    if (!$actividad) return '<p class="vacio">Todavía no hay actividad registrada en sus cursos.</p>';
    $iconos = ['entrega' => 'archivo', 'revision' => 'ojo', 'comentario' => 'comentario', 'nota' => 'nota',
               'incentivo' => 'incentivo', 'avance' => 'avance', 'fase' => 'fases'];
    $html = '<ul class="d-actividad">';
    foreach ($actividad as $a) {
        $url = ($a['id_grupo'] && $a['id_fase']) ? 'grupo_fase.php?grupo=' . (int) $a['id_grupo'] . '&amp;fase=' . (int) $a['id_fase']
             : ($a['tipo'] === 'fase' ? 'fase.php?id=' . (int) $a['id_fase'] : ($a['id_grupo'] ? 'grupo.php?id=' . (int) $a['id_grupo'] : null));
        $html .= '<li class="act-' . e($a['tipo']) . '"><span class="d-act-ico">' . icono($iconos[$a['tipo']] ?? 'reloj', 16) . '</span>'
            . '<div>' . ($url ? '<a href="' . $url . '">' : '') . texto_actividad($a) . ($url ? '</a>' : '')
            . '<small>' . e(hace($a['fecha'])) . '</small></div></li>';
    }
    return $html . '</ul>';
}


function lista_otorgados(array $otorgados, bool $contexto = true): string
{
    if (!$otorgados) return '<p class="vacio">Aún no se han otorgado incentivos.</p>';
    $html = '<ul class="d-otorgados">';
    foreach ($otorgados as $o) {
        $html .= '<li><span class="d-medalla">' . e($o['icono'] ?: '🏆') . '</span><div><strong>' . e($o['incentivo']) . '</strong>'
            . ($o['valor'] ? ' <span class="etiqueta etiqueta-cafe">' . e($o['valor']) . '</span>' : '') . '<br><small>'
            . ($o['est_nombre'] ? e($o['est_nombre'] . ' ' . $o['est_apellido']) . ' · ' : 'Todo el grupo · ')
            . ($contexto ? e($o['nombre_curso'] . ' · ' . $o['nombre_grupo']) . ' · ' : '')
            . e(fecha_corta($o['fecha'])) . ' · por ' . e($o['nombre'] . ' ' . $o['apellido']) . '</small>'
            . ($o['observacion'] ? '<p>' . nl2br(e($o['observacion'])) . '</p>' : '') . '</div></li>';
    }
    return $html . '</ul>';
}


function pestanas_cursos(array $cursos, int $actual, string $url_base): string
{
    if (count($cursos) < 2) return '';
    $html = '<nav class="d-pestanas">';
    foreach ($cursos as $c) {
        $html .= '<a href="' . e($url_base . (str_contains($url_base, '?') ? '&' : '?') . 'curso=' . (int) $c['id_curso']) . '" class="'
            . ((int) $c['id_curso'] === $actual ? 'activa' : '') . '">Curso ' . e($c['nombre_curso']) . '</a>';
    }
    return $html . '</nav>';
}
