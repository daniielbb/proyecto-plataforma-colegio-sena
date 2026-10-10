<?php

function icono(string $nombre, int $tam = 18): string
{
    $p = [
        'inicio'     => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'grupo'      => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c.5-3.5 3-5 6-5s5.5 1.5 6 5"/><path d="M15 15c2.5 0 4.5 1.5 5 4"/>',
        'fases'      => '<path d="M3 6h6l2 2h10v11H3z"/><path d="M3 10h18"/>',
        'avance'     => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 15l3-4 3 2 5-6"/>',
        'trabajos'   => '<path d="M6 3h9l4 4v14H6z"/><path d="M9 12l2 2 4-4"/>',
        'nota'       => '<path d="M12 3l2.6 5.6 6 .6-4.5 4 1.3 6L12 16l-5.4 3.2 1.3-6-4.5-4 6-.6z"/>',
        'comentario' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/>',
        'incentivo'  => '<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4"/><path d="M12 13v4M8 21h8M9 17h6v4H9z"/>',
        'perfil'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
        'salir'      => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h10"/>',
        'calendario' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'archivo'    => '<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4"/>',
        'ojo'        => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'reloj'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'      => '<path d="M5 12l5 5 9-10"/>',
        'descargar'  => '<path d="M12 4v11M7 10l5 5 5-5"/><path d="M5 20h14"/>',
        'subir'      => '<path d="M12 20V9M7 14l5-5 5 5"/><path d="M5 4h14"/>',
        'candado'    => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'campana'    => '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'proyecto'   => '<path d="M4 4h11l5 5v11H4z"/><path d="M15 4v5h5"/><path d="M8 13h8M8 17h5"/>',
        'docente'    => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
    ][$nombre] ?? '<circle cx="12" cy="12" r="8"/>';
    return '<svg class="ico" width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}


function etiqueta(string $estado): string
{
    return '<span class="etiqueta ' . clase_estado_avance($estado) . '">' . e($estado) . '</span>';
}


function etiqueta_fase(array $f): string
{
    $txt = $f['estado_visible'];
    $html = '<span class="etiqueta ' . (ESTADOS_ESTUDIANTE[$txt] ?? 'etiqueta-gris') . '">'
        . ($txt === 'Bloqueada' ? icono('candado', 12) . ' ' : '') . e($txt) . '</span>';
    if ($f['estado'] === 'Requiere corrección') $html .= ' <span class="etiqueta etiqueta-roja">Requiere corrección</span>';
    return $html;
}


function simbolo_fase(array $f): string
{
    return match ($f['estado_visible']) {
        'Completada'            => '✓',
        'Bloqueada'             => icono('candado', 18),
        'Disponible'            => '○',
        'Pendiente de revisión' => icono('reloj', 18),
        default                 => $f['estado'] === 'Requiere corrección' ? '!' : '●',
    };
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
    if ($d < 0)   return e($txt) . ' <span class="d-plazo vencido">vencida hace ' . abs($d) . ' d</span>';
    if ($d === 0) return e($txt) . ' <span class="d-plazo hoy">vence hoy</span>';
    if ($d <= 5)  return e($txt) . ' <span class="d-plazo pronto">faltan ' . $d . ' d</span>';
    return e($txt);
}


function linea_fases(array $fases): string
{
    if (!$fases) return '<p class="vacio">El docente todavía no ha publicado fases para su grupo.</p>';
    $html = '<ol class="d-linea e-linea">';
    foreach ($fases as $f) {
        $clase = match ($f['estado_visible']) {
            'Completada' => 'hecha', 'Bloqueada' => 'bloqueada', 'Disponible' => 'pendiente',
            default => $f['estado'] === 'Requiere corrección' ? 'alerta' : 'actual',
        };
        $html .= '<li class="' . $clase . '"><a href="fase.php?id=' . (int) $f['id_fase'] . '">'
            . '<span class="d-linea-punto">' . simbolo_fase($f) . '</span>'
            . '<span class="d-linea-texto"><small>FASE ' . (int) $f['orden'] . '</small><strong>' . e($f['nombre_fase']) . '</strong>'
            . '<span>' . etiqueta_fase($f)
            . ($f['bloqueada'] ? ' · ' . e($f['motivo_bloqueo']) : ' · ' . limite($f['fecha_limite'], fase_terminada($f['estado'])))
            . ((int) $f['total_comentarios'] ? ' · ' . (int) $f['total_comentarios'] . ' comentario(s)' : '')
            . '</span></span>'
            . '<span class="d-linea-barra">' . ($f['bloqueada'] ? '' : barra($f['porcentaje'])) . '</span>'
            . '</a></li>';
    }
    return $html . '</ol>';
}


function lista_comentarios(array $comentarios, bool $con_fase = true): string
{
    if (!$comentarios) return '<p class="vacio">El docente aún no ha publicado comentarios.</p>';
    $html = '<ul class="d-comentarios">';
    foreach ($comentarios as $c) {
        $html .= '<li class="tipo-' . e(strtolower(str_replace(['ó', ' '], ['o', '-'], $c['tipo']))) . '">'
            . '<div class="d-comentario-cab"><span class="avatar chico">' . e(mb_strtoupper(mb_substr($c['nombre'], 0, 1))) . '</span>'
            . '<div><strong>' . e($c['nombre'] . ' ' . $c['apellido']) . '</strong> <span class="d-tipo">' . e($c['tipo']) . '</span><br>'
            . '<small>Docente · ' . e(fecha_hora($c['fecha'])) . ($c['fecha_edicion'] ? ' · editado ' . e(hace($c['fecha_edicion'])) : '') . '</small></div></div>'
            . '<p>' . nl2br(e($c['comentario'])) . '</p><div class="d-comentario-pie"><small>';
        $partes = [];
        if ($con_fase) $partes[] = $c['nombre_fase'] ? '<a href="fase.php?id=' . (int) $c['id_fase'] . '">Fase ' . (int) $c['orden'] . ' · ' . e($c['nombre_fase']) . '</a>' : 'Proyecto en general';
        $partes[] = $c['id_estudiante'] ? 'Solo para ti' : 'Para todo el grupo';
        if ($c['version']) $partes[] = 'Sobre: ' . e($c['nombre_original']) . ' (v' . (int) $c['version'] . ')';
        $html .= implode(' · ', $partes) . '</small></div></li>';
    }
    return $html . '</ul>';
}


function lista_entregas(array $entregas, int $id_estudiante, bool $con_fase = false): string
{
    if (!$entregas) return '<p class="vacio">No hay entregas registradas.</p>';
    $ultima = [];   // última versión de cada (fase, estudiante)
    foreach ($entregas as $en) {
        $k = $en['id_fase'] . '-' . $en['id_estudiante'];
        if (!isset($ultima[$k]) || $en['version'] > $ultima[$k]) $ultima[$k] = $en['version'];
    }
    $html = '<ul class="d-entregas">';
    foreach ($entregas as $en) {
        $antigua = $ultima[$en['id_fase'] . '-' . $en['id_estudiante']] != $en['version'];
        $mia = (int) $en['id_estudiante'] === $id_estudiante;
        $revs = revisiones_de_entrega((int) $en['id_entrega']);
        $html .= '<li class="d-entrega ' . ($antigua ? 'antigua' : '') . '">'
            . '<span class="d-entrega-ico">' . e($en['extension']) . '</span><div class="d-entrega-cuerpo">'
            . '<strong>' . e($en['nombre_original']) . '</strong> ' . etiqueta($en['estado'])
            . ($antigua ? ' <small>(versión anterior)</small>' : '') . '<br><small>'
            . ($con_fase ? '<a href="fase.php?id=' . (int) $en['id_fase'] . '">Fase ' . (int) $en['orden'] . ' · ' . e($en['nombre_fase']) . '</a> · ' : '')
            . 'Versión ' . (int) $en['version'] . ' · ' . e(tamano_legible((int) $en['tamano'])) . ' · ' . e(fecha_hora($en['fecha_subida']))
            . ' · ' . ($mia ? '<strong>Tú</strong>' : e($en['nombre'] . ' ' . $en['apellido']))
            . (!empty($en['fecha_limite']) && substr($en['fecha_subida'], 0, 10) > $en['fecha_limite'] ? ' <span class="d-plazo vencido">fuera de plazo</span>' : '')
            . '</small>'
            . ($en['comentario_estudiante'] ? '<blockquote>' . nl2br(e($en['comentario_estudiante'])) . '</blockquote>' : '');
        if ($revs) {
            $html .= '<details style="margin-top:6px"><summary><small>Revisiones del docente (' . count($revs) . ')</small></summary><ul class="lista-simple">';
            foreach ($revs as $r) {
                $html .= '<li><small>' . etiqueta($r['estado']) . ' · ' . e($r['nombre'] . ' ' . $r['apellido']) . ' · ' . e(fecha_hora($r['fecha'])) . '</small>'
                    . ($r['comentario'] ? '<br><small>' . nl2br(e($r['comentario'])) . '</small>' : '') . '</li>';
            }
            $html .= '</ul></details>';
        }
        $html .= '</div><div class="acciones">'
            . '<a class="btn btn-secundario btn-chico" target="_blank" rel="noopener" href="archivo.php?entrega=' . (int) $en['id_entrega'] . '" title="Ver">' . icono('ojo', 14) . '</a>'
            . '<a class="btn btn-secundario btn-chico" href="archivo.php?entrega=' . (int) $en['id_entrega'] . '&amp;descargar=1" title="Descargar">' . icono('descargar', 14) . '</a>'
            . '</div></li>';
    }
    return $html . '</ul>';
}


function tarjeta_incentivo(array $i): string
{
    $clase = ['Obtenido' => 'etiqueta-verde', 'Pendiente' => 'etiqueta-ambar', 'No obtenido' => 'etiqueta-gris'][$i['resultado']];
    $html = '<article class="d-incentivo e-incentivo ' . ($i['resultado'] === 'Obtenido' ? 'obtenido' : '') . '">'
        . '<div style="display:flex;gap:12px;align-items:center"><span class="d-medalla">' . e($i['icono'] ?: '🏆') . '</span>'
        . '<div><h3>' . e($i['nombre']) . '</h3><span class="etiqueta ' . $clase . '">' . e($i['resultado']) . '</span>'
        . ($i['valor'] ? ' <span class="etiqueta etiqueta-cafe">' . e($i['valor']) . '</span>' : '') . '</div></div>'
        . ($i['descripcion'] ? '<p>' . nl2br(e($i['descripcion'])) . '</p>' : '')
        . '<p><strong>Condición:</strong> ' . nl2br(e($i['criterio'])) . '</p>'
        . '<p><small>' . ($i['nombre_fase'] ? 'Fase ' . (int) $i['orden_fase'] . ' · ' . e($i['nombre_fase']) : 'Todo el proyecto')
        . ($i['titulo_tesis'] ? ' · Proyecto «' . e($i['titulo_tesis']) . '»' : '')
        . ($i['fecha_fin'] ? ' · hasta ' . e(fecha_corta($i['fecha_fin'])) : '')
        . ' · Docente: ' . e($i['creador_nombre'] . ' ' . $i['creador_apellido']) . '</small></p>';
    foreach ($i['otorgamientos'] as $o) {
        $html .= '<p class="e-otorgado">' . icono('check', 14) . ' ' . ($o['id_estudiante'] ? 'Lo obtuviste tú' : 'Lo obtuvo todo el grupo')
            . ' el ' . e(fecha_corta($o['fecha'])) . ($o['observacion'] ? ' · ' . e($o['observacion']) : '') . '</p>';
    }
    return $html . '</article>';
}


function lista_avisos(array $avisos): string
{
    if (!$avisos) return '<p class="vacio">No tiene avisos por ahora.</p>';
    $iconos = ['comentario' => 'comentario', 'revision' => 'ojo', 'avance' => 'avance', 'desbloqueo' => 'fases',
               'entrega' => 'archivo', 'incentivo' => 'incentivo', 'limite' => 'calendario', 'grupo' => 'grupo'];
    $html = '<ul class="d-actividad e-avisos">';
    foreach ($avisos as $a) {
        $html .= '<li class="act-' . e($a['tipo']) . ($a['nuevo'] ? ' nuevo' : '') . ($a['fijo'] ? ' fijo' : '') . '">'
            . '<span class="d-act-ico">' . icono($iconos[$a['tipo']] ?? 'campana', 16) . '</span>'
            . '<div><a href="' . e($a['url']) . '"><strong>' . e($a['titulo']) . '</strong></a>'
            . ($a['nuevo'] ? ' <span class="etiqueta etiqueta-roja">Nuevo</span>' : '')
            . '<small>' . e($a['texto']) . '</small>'
            . '<small>' . ($a['fijo'] ? 'Recordatorio' : e(hace($a['fecha']))) . '</small></div></li>';
    }
    return $html . '</ul>';
}
