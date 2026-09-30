<?php
/**
 * Funciones auxiliares de presentación (escape, URLs, fechas, estados).
 */

/** Escapa texto para HTML. Úselo SIEMPRE al imprimir datos de la BD. */
function e($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Construye una URL absoluta dentro del proyecto. */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

/** Lee un entero positivo de $_GET o devuelve null. */
function get_id(string $clave): ?int
{
    $v = filter_input(INPUT_GET, $clave, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $v ?: null;
}

// --- Fechas en español (sin depender de la extensión intl) -------------
const MESES_ES = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                  'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

/** "25 de septiembre de 2026" */
function fecha_larga(?string $fecha, bool $conAnio = true): string
{
    if (!$fecha) return '—';
    $t = strtotime($fecha);
    $txt = date('j', $t) . ' de ' . MESES_ES[(int)date('n', $t)];
    return $conAnio ? $txt . ' de ' . date('Y', $t) : $txt;
}

/** "25/09/2026" */
function fecha_corta(?string $fecha): string
{
    return $fecha ? date('d/m/Y', strtotime($fecha)) : '—';
}

/** "25/09/2026 · 4:40 p. m." */
function fecha_hora(?string $fecha): string
{
    if (!$fecha) return '—';
    $t = strtotime($fecha);
    return date('d/m/Y', $t) . ' · ' . date('g:i', $t) . (date('H', $t) < 12 ? ' a. m.' : ' p. m.');
}

/** Días que faltan (negativo = vencido). */
function dias_restantes(?string $fecha): ?int
{
    if (!$fecha) return null;
    $hoy = new DateTime('today');
    $lim = new DateTime(date('Y-m-d', strtotime($fecha)));
    return (int)$hoy->diff($lim)->format('%r%a');
}

function texto_dias(?int $dias): string
{
    if ($dias === null) return '';
    if ($dias === 0) return 'Vence hoy';
    if ($dias === 1) return 'Falta 1 día';
    if ($dias > 1)  return "Faltan $dias días";
    return 'Vencida hace ' . abs($dias) . ($dias === -1 ? ' día' : ' días');
}

function iniciales(string $nombre, string $apellido = ''): string
{
    return mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1));
}

// --- Estados -> clase CSS ----------------------------------------------
/**
 * Devuelve la clase de badge para cualquier estado del sistema
 * (tesis_fase.estado, tesis.estado, documento.estado, correcciones.estado).
 */
function clase_estado(?string $estado): string
{
    $mapa = [
        'Completada'           => 'completada',
        'Aprobada'             => 'completada',
        'Aprobado'             => 'completada',
        'En progreso'          => 'progreso',
        'En revisión'          => 'revision',
        'Entregado'            => 'revision',
        'Borrador'             => 'pendiente',
        'Pendiente'            => 'pendiente',
        'Requiere corrección'  => 'correccion',
        'Requiere ajustes'     => 'correccion',
        'Atrasada'             => 'atrasada',
        'Rechazada'            => 'atrasada',
        'Activo'               => 'completada',
        'Inactivo'             => 'pendiente',
    ];
    return $mapa[$estado] ?? 'pendiente';
}

function badge(?string $estado): string
{
    $estado = $estado ?: 'Pendiente';
    return '<span class="badge badge-' . clase_estado($estado) . '">' . e($estado) . '</span>';
}
