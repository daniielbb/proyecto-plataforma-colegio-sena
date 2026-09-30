<?php
/**
 * Plantilla (barra lateral + encabezado) y componentes visuales del estudiante.
 * No contiene formularios: la interfaz del estudiante es solo de consulta.
 */

const MENU_ESTUDIANTE = [
    'inicio.php'       => ['Inicio', 'inicio'],
    'proyecto.php'     => ['Mi proyecto', 'proyecto'],
    'fases.php'        => ['Fases', 'carpeta'],
    'documentos.php'   => ['Documentos', 'documento'],
    'correcciones.php' => ['Correcciones', 'correccion'],
    'comentarios.php'  => ['Comentarios', 'comentario'],
    'grupo.php'        => ['Mi grupo', 'grupo'],
    'perfil.php'       => ['Perfil', 'perfil'],
];

function layout_inicio(string $titulo, string $activo, array $ctx): void
{
    $u      = $ctx['usuario'];
    $g      = $ctx['grupo'];
    $t      = $ctx['tesis'];
    $nov    = $ctx['novedades'];
    $sinGrupo = $g === null;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> · <?= e(APP_NOMBRE) ?></title>
<link rel="stylesheet" href="<?= e(url('css/estilos.css')) ?>">
</head>
<body class="app">
<input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Abrir menú">

<aside class="sidebar" aria-label="Menú principal">
  <div class="sidebar-marca">
    <span class="marca-logo"><?= icono('birrete') ?></span>
    <div><strong><?= e(APP_NOMBRE) ?></strong><small><?= e(APP_SUBTITULO) ?></small></div>
    <label for="nav-toggle" class="sidebar-cerrar" title="Cerrar menú"><?= icono('cerrar') ?></label>
  </div>

  <div class="sidebar-usuario">
    <span class="rol-etiqueta">Estudiante</span>
    <div class="avatar avatar-grande"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></div>
    <strong><?= e($u['nombre'] . ' ' . $u['apellido']) ?></strong>
    <?php if ($g): ?>
      <small><?= e($g['nombre_curso']) ?></small>
      <small><?= e($g['nombre_grupo']) ?><?= $g['ficha'] ? ' · Ficha ' . e($g['ficha']) : '' ?></small>
    <?php else: ?>
      <small>Sin grupo asignado</small>
    <?php endif; ?>
  </div>

  <nav class="sidebar-nav">
    <?php foreach (MENU_ESTUDIANTE as $archivo => [$texto, $ico]):
        $bloqueado = $sinGrupo && $archivo !== 'perfil.php';
        $href = $bloqueado ? 'estudiante/sin_grupo.php' : 'estudiante/' . $archivo; ?>
      <a href="<?= e(url($href)) ?>" class="<?= $activo === $archivo ? 'activo' : '' ?><?= $bloqueado ? ' deshabilitado' : '' ?>"
         <?= $activo === $archivo ? 'aria-current="page"' : '' ?>>
        <?= icono($ico) ?><span><?= e($texto) ?></span>
        <?php if ($archivo === 'correcciones.php' && $nov['total'] > 0): ?><em class="punto" title="Novedades"></em><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <a class="sidebar-salir" href="<?= e(url('logout.php?t=' . token_csrf())) ?>"><?= icono('salir') ?><span>Cerrar sesión</span></a>
</aside>
<label for="nav-toggle" class="nav-overlay" aria-hidden="true"></label>

<div class="principal">
  <header class="topbar">
    <label for="nav-toggle" class="topbar-menu" title="Menú"><?= icono('menu') ?></label>
    <div class="topbar-titulo">
      <small>Hola, <?= e($u['nombre']) ?></small>
      <strong><?= $t ? e($t['titulo']) : e($titulo) ?></strong>
    </div>

    <details class="notificaciones">
      <summary title="Notificaciones">
        <?= icono('campana') ?>
        <?php if ($nov['total'] > 0): ?><span class="notif-contador"><?= (int)$nov['total'] ?></span><?php endif; ?>
      </summary>
      <div class="notif-panel">
        <p class="notif-titulo">Novedades desde tu último ingreso</p>
        <?php if (!$nov['items']): ?>
          <p class="texto-suave">No hay correcciones ni comentarios nuevos.</p>
        <?php endif; ?>
        <?php foreach ($nov['items'] as $n):
            $href = $n['tipo'] === 'correccion' ? 'estudiante/correccion.php?id=' . (int)$n['id'] : 'estudiante/comentarios.php'; ?>
          <a class="notif-item" href="<?= e(url($href)) ?>">
            <?= icono($n['tipo'] === 'correccion' ? 'correccion' : 'comentario') ?>
            <span>
              <strong><?= $n['tipo'] === 'correccion' ? 'Nueva corrección' : 'Nuevo comentario' ?></strong>
              <small><?= e($n['referencia']) ?> · <?= e(fecha_corta($n['fecha'])) ?></small>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </details>

    <a class="topbar-perfil" href="<?= e(url('estudiante/perfil.php')) ?>" title="Mi perfil">
      <span class="avatar"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></span>
      <span class="topbar-perfil-nombre"><?= e($u['nombre'] . ' ' . $u['apellido']) ?><small>Estudiante</small></span>
    </a>
  </header>

  <main class="contenido">
    <?php
}

function layout_fin(): void
{
    ?>
  </main>
  <footer class="pie">
    <?= e(APP_NOMBRE) ?> · Interfaz del estudiante (solo consulta) · <?= date('Y') ?>
  </footer>
</div>
</body>
</html>
    <?php
}

// ---------------------------------------------------------------------
// Componentes
// ---------------------------------------------------------------------
function encabezado_pagina(string $titulo, string $subtitulo = '', string $volver = ''): void
{
    echo '<div class="pagina-encabezado">';
    if ($volver) {
        echo '<a class="enlace-volver" href="' . e(url($volver)) . '">' . icono('volver') . ' Volver</a>';
    }
    echo '<h1>' . e($titulo) . '</h1>';
    if ($subtitulo) echo '<p>' . e($subtitulo) . '</p>';
    echo '</div>';
}

function estado_vacio(string $ico, string $titulo, string $texto): void
{
    echo '<div class="vacio">' . icono($ico) . '<h3>' . e($titulo) . '</h3><p>' . e($texto) . '</p></div>';
}

function barra_progreso(int $pct, string $texto = ''): void
{
    $pct = max(0, min(100, $pct));
    echo '<div class="progreso" role="progressbar" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100">'
       . '<div class="progreso-relleno" style="width:' . $pct . '%"></div></div>';
    if ($texto) echo '<p class="progreso-texto">' . e($texto) . '</p>';
}

/** Tarjeta tipo carpeta para una fase. */
function carpeta_fase(array $f, ?int $idActual): void
{
    $clase  = clase_estado($f['estado']);
    $actual = $idActual !== null && (int)$f['id_fase'] === $idActual;
    $ico    = $f['estado'] === 'Pendiente' ? 'carpeta' : 'carpeta-abierta';
    ?>
    <a class="carpeta carpeta-<?= $clase ?><?= $actual ? ' carpeta-actual' : '' ?>" href="<?= e(url('estudiante/fase.php?id=' . (int)$f['id_fase'])) ?>">
      <span class="carpeta-pestana">Fase <?= (int)$f['orden'] ?></span>
      <div class="carpeta-cuerpo">
        <div class="carpeta-cabecera">
          <span class="carpeta-icono"><?= icono($ico) ?></span>
          <?= badge($f['estado']) ?>
        </div>
        <h3><?= e($f['nombre_fase']) ?></h3>
        <p><?= e($f['descripcion']) ?></p>
        <ul class="carpeta-datos">
          <li title="Fecha de entrega"><?= icono('calendario') ?>
            <?php if ($f['estado_bd'] === 'Completada'): ?>
              Completada el <?= e(fecha_corta($f['fecha_completada'])) ?>
            <?php elseif ($f['fecha_entrega']): ?>
              Entrega <?= e(fecha_corta($f['fecha_entrega'])) ?><?= $f['fecha_estimada'] ? ' (estimada)' : '' ?>
            <?php else: ?>
              Sin fecha asignada
            <?php endif; ?>
          </li>
          <li><?= icono('documento') ?> <?= (int)$f['n_documentos'] ?> doc.</li>
          <li><?= icono('correccion') ?> <?= (int)$f['n_correcciones'] ?> corr.</li>
          <li><?= icono('comentario') ?> <?= (int)$f['n_comentarios'] ?> com.</li>
        </ul>
        <?php if ($actual): ?><span class="carpeta-marca">Fase actual</span><?php endif; ?>
      </div>
    </a>
    <?php
}

/** Tarjeta de corrección del docente. */
function tarjeta_correccion(array $c, bool $conEnlace = true): void
{
    ?>
    <article class="correccion correccion-<?= clase_estado($c['estado']) ?>">
      <header>
        <div>
          <span class="etiqueta-mini">Documento</span>
          <h3><?= e($c['nombre_documento'] ?: 'Revisión general de la fase') ?></h3>
        </div>
        <?= badge($c['estado']) ?>
      </header>
      <p class="correccion-obs"><?= nl2br(e($c['observacion'])) ?></p>
      <ul class="meta">
        <li><?= icono('perfil') ?> <?= e($c['prof_nombre'] . ' ' . $c['prof_apellido']) ?></li>
        <li><?= icono('calendario') ?> <?= e(fecha_larga($c['fecha'])) ?></li>
        <?php if ($c['nombre_fase']): ?><li><?= icono('carpeta') ?> Fase <?= (int)$c['orden'] ?> · <?= e($c['nombre_fase']) ?></li><?php endif; ?>
        <?php if ($c['calificacion'] !== null): ?><li><?= icono('medalla') ?> Nota: <?= e($c['calificacion']) ?></li><?php endif; ?>
      </ul>
      <?php if ($conEnlace): ?>
        <a class="boton boton-claro boton-chico" href="<?= e(url('estudiante/correccion.php?id=' . (int)$c['id_correccion'])) ?>"><?= icono('ojo') ?> Ver corrección</a>
      <?php endif; ?>
    </article>
    <?php
}

/** Comentario del docente (solo lectura: sin responder, editar ni eliminar). */
function tarjeta_comentario(array $c): void
{
    ?>
    <article class="comentario">
      <div class="comentario-autor">
        <span class="avatar avatar-docente"><?= e(iniciales($c['prof_nombre'], $c['prof_apellido'])) ?></span>
        <div>
          <strong><?= e($c['prof_nombre'] . ' ' . $c['prof_apellido']) ?></strong>
          <small>Docente · <?= e(fecha_hora($c['fecha'])) ?></small>
        </div>
      </div>
      <blockquote><?= nl2br(e($c['comentario'])) ?></blockquote>
      <ul class="meta">
        <li><?= icono('carpeta') ?> <?= $c['nombre_fase'] ? 'Fase ' . (int)$c['orden'] . ' · ' . e($c['nombre_fase']) : 'Comentario general del proyecto' ?></li>
        <?php if ($c['nombre_documento']): ?><li><?= icono('documento') ?> <?= e($c['nombre_documento']) ?></li><?php endif; ?>
      </ul>
    </article>
    <?php
}

/** Fila de documento (sin acciones de edición). */
function fila_documento(array $d): void
{
    ?>
    <li class="doc">
      <span class="doc-icono"><?= icono('documento') ?></span>
      <div class="doc-info">
        <strong><?= e($d['nombre_documento']) ?></strong>
        <small><?= e($d['tipo_documento']) ?> · Subido el <?= e(fecha_corta($d['fecha_subida'])) ?>
          <?php if ($d['fecha_modificacion']): ?> · Modificado <?= e(fecha_corta($d['fecha_modificacion'])) ?><?php endif; ?></small>
        <small>
          <?php if ($d['id_correccion']): ?>
            Última revisión: <?= e(fecha_corta($d['corr_fecha'])) ?> por <?= e($d['revisor_nombre'] . ' ' . $d['revisor_apellido']) ?>
            · <a href="<?= e(url('estudiante/correccion.php?id=' . (int)$d['id_correccion'])) ?>">Ver corrección</a>
          <?php else: ?>
            Aún sin revisión del docente
          <?php endif; ?>
        </small>
      </div>
      <div class="doc-acciones">
        <?= badge($d['estado']) ?>
        <?php if ($d['ruta_archivo']): ?>
          <a class="boton-icono" href="<?= e(url('estudiante/descargar.php?id=' . (int)$d['id_documento'])) ?>" title="Ver / descargar"><?= icono('descargar') ?></a>
        <?php endif; ?>
      </div>
    </li>
    <?php
}
