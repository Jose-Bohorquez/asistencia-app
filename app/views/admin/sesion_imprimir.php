<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Asistencia — <?= htmlspecialchars($sesion['curso_nombre'] ?? '') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }

        /* ===== BARRA DE PREVISUALIZACIÓN ===== */
        .no-print {
            background: #1e293b;
            color: #e2e8f0;
            padding: 0;
            display: flex;
            align-items: stretch;
            min-height: 56px;
            border-bottom: 3px solid #2563eb;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .no-print .bar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px;
            flex: 1;
            min-width: 0;
        }
        .no-print .bar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 16px;
            border-left: 1px solid #334155;
        }
        .no-print .preview-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #2563eb;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 4px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .no-print .doc-title {
            font-size: 13px;
            font-weight: 600;
            color: #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .no-print .doc-meta {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .no-print .separator { color: #475569; margin: 0 2px; }
        .no-print .btn-volver {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 500;
            color: #94a3b8;
            background: transparent;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 6px 14px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }
        .no-print .btn-volver:hover { background: #334155; color: #f1f5f9; }
        .no-print .btn-imprimir {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            background: #2563eb;
            border: none;
            border-radius: 6px;
            padding: 8px 20px;
            cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
            letter-spacing: 0.01em;
        }
        .no-print .btn-imprimir:hover { background: #1d4ed8; }
        .no-print .btn-imprimir svg { width: 14px; height: 14px; fill: currentColor; }

        .page { padding: 16px 20px; }

        /* Tabla principal del formato */
        .formato table { width: 100%; border-collapse: collapse; }
        .formato td, .formato th {
            border: 1.2px solid #000;
            padding: 3px 5px;
            vertical-align: middle;
        }

        /* Cabecera institucional */
        .header-logo { width: 18%; text-align: center; vertical-align: middle; }
        .header-logo img { height: 56px; display: block; margin: 0 auto 2px; }
        .header-logo span { font-size: 9px; display: block; }
        .header-title { width: 62%; vertical-align: middle; padding: 0 !important; }
        .header-title table { border-collapse: collapse; width: 100%; }
        .header-title td { border: none; border-bottom: 1.2px solid #000; text-align: center; padding: 5px 0; font-weight: bold; }
        .header-title td:last-child { border-bottom: none; }
        .header-codigo { width: 20%; vertical-align: top; padding: 0 !important; }
        .header-codigo table { border-collapse: collapse; width: 100%; height: 100%; }
        .header-codigo td { border: none; border-bottom: 1.2px solid #000; text-align: center; padding: 3px 4px; font-size: 10px; }
        .header-codigo tr:last-child td { border-bottom: none; }

        /* Filas de datos de sesión */
        .info-row td { font-size: 11px; padding: 3px 6px; }
        .info-row .label { font-weight: bold; }

        /* Tabla de asistencia */
        .attendance-table { width: 100%; border-collapse: collapse; margin-top: 0; }
        .attendance-table th {
            background: #fff;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            border: 1.2px solid #000;
            padding: 4px 3px;
        }
        .attendance-table td {
            border: 1.2px solid #000;
            padding: 2px 4px;
            font-size: 10px;
            height: 22px;
            vertical-align: middle;
        }
        .firma-img { max-height: 18px; max-width: 120px; display: block; margin: 0 auto; }

        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; }
            .page { padding: 6px 8px; }
            @page {
                size: landscape;
                margin: 0.5cm;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <div class="bar-left">
        <span class="preview-badge">
            <svg viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px;"><path d="M2 2h12v1H2zM1 4h14v8H1zm2 2v4h10V6zm-2 9h14v1H1z"/></svg>
            Vista Previa &mdash; FO-P06-F08
        </span>
        <div style="min-width:0;overflow:hidden;">
            <p class="doc-title">
                Control Asistencia &mdash; <?= htmlspecialchars($sesion['curso_nombre'] ?? '') ?>
            </p>
            <p class="doc-meta">
                <?php
                    $partes = [];
                    if (!empty($sesion['fecha'])) $partes[] = date('d/m/Y', strtotime($sesion['fecha']));
                    $aula = $sesion['aula'] ?? '';
                    $sede = $sesion['sede'] ?? '';
                    if (!empty($aula)) $partes[] = 'Aula ' . htmlspecialchars($aula);
                    if (!empty($sede)) $partes[] = htmlspecialchars($sede);
                    $totalAsis = isset($asistencias) ? count($asistencias) : 0;
                    $partes[] = $totalAsis . ' asistente' . ($totalAsis !== 1 ? 's' : '');
                    echo implode(' &bull; ', $partes);
                ?>
            </p>
        </div>
    </div>
    <div class="bar-right">
        <a href="javascript:history.back()" class="btn-volver">
            &#8592; Volver
        </a>
        <button onclick="window.print()" class="btn-imprimir">
            <svg viewBox="0 0 20 20"><path d="M5 4v3H4a2 2 0 00-2 2v6h3v2h10v-2h3V9a2 2 0 00-2-2h-1V4H5zm2 0h6v3H7V4zm-3 5h12a1 1 0 011 1v3h-2v-1H5v1H3V10a1 1 0 011-1zm9 5v2H5v-2h8z"/></svg>
            Imprimir
        </button>
    </div>
</div>

<div class="page formato">

    <!-- ===== ENCABEZADO FO-P06-F08 ===== -->
    <table>
        <tr>
            <!-- Logo -->
            <td class="header-logo">
                <img src="<?= APP_URL ?>/assets/img/logo.png" alt="Logo Universidad del Tolima">
                <span>Universidad del Tolima</span>
            </td>
            <!-- Título -->
            <td class="header-title">
                <table>
                    <tr><td style="font-size:13px; font-weight:bold; border-bottom:1.2px solid #000;">
                        PLANIFICACIÓN, DESARROLLO Y VERIFICACIÓN DE LA LABOR ACADÉMICA
                    </td></tr>
                    <tr><td style="font-size:12px; font-weight:bold;">
                        CONTROL ASISTENCIA ESTUDIANTES
                    </td></tr>
                </table>
            </td>
            <!-- Código/Versión -->
            <td class="header-codigo">
                <table>
                    <tr><td>Página 1 de 1</td></tr>
                    <tr><td><strong>Código: FO-P06-F08</strong></td></tr>
                    <tr><td><strong>Versión: 05</strong></td></tr>
                    <tr><td>Fecha Aprobación:<br><strong>27-01-2017</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ===== FILA: ÁREA / PROGRAMA / CÓDIGO ===== -->
    <table class="info-row" style="margin-top:0;">
        <tr>
            <td style="width:33%;"><span class="label">ÁREA: </span><?= htmlspecialchars($sesion['curso_area'] ?? '') ?></td>
            <td style="width:33%;"><span class="label">PROGRAMA: </span><?= htmlspecialchars($sesion['curso_programa'] ?? $sesion['programa_nombre'] ?? '') ?></td>
            <td style="width:34%;"><span class="label">CÓDIGO: </span><?= htmlspecialchars($sesion['curso_codigo'] ?? '') ?></td>
        </tr>
    </table>

    <!-- ===== FILA: SEMESTRE / GRUPO / AULA / SEDE / FECHA ===== -->
    <table class="info-row" style="margin-top:0;">
        <tr>
            <td style="width:20%;"><span class="label">SEMESTRE: </span><?= htmlspecialchars($sesion['semestre'] ?? '') ?></td>
            <td style="width:20%;"><span class="label">GRUPO: </span><?= htmlspecialchars($sesion['grupo'] ?? '') ?></td>
            <td style="width:20%;"><span class="label">AULA No. </span><?= htmlspecialchars($sesion['aula'] ?? '') ?></td>
            <td style="width:20%;"><span class="label">SEDE: </span><?= htmlspecialchars($sesion['sede'] ?? '') ?></td>
            <td style="width:20%;"><span class="label">FECHA: </span><?= !empty($sesion['fecha']) ? date('d/m/Y', strtotime($sesion['fecha'])) : '' ?></td>
        </tr>
    </table>

    <!-- ===== TABLA DE ASISTENCIA ===== -->
    <table class="attendance-table">
        <thead>
            <tr>
                <th style="width:22%;">NOMBRE ESTUDIANTE</th>
                <th style="width:11%;">DOCUMENTO<br>IDENTIFICACIÓN</th>
                <th style="width:11%;">CÓDIGO</th>
                <th style="width:10%;">TELÉFONO</th>
                <th style="width:18%;">DIRECCIÓN</th>
                <th style="width:16%;">CORREO ELECTRÓNICO</th>
                <th style="width:12%;">FIRMA</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total   = count($asistencias);
            $minRows = max(30, $total);
            for ($i = 0; $i < $minRows; $i++):
                $a = $asistencias[$i] ?? null;
            ?>
            <tr>
                <td><?= $a ? htmlspecialchars($a['estudiante_nombre'] ?? '') : '' ?></td>
                <td style="text-align:center;"><?= $a ? htmlspecialchars($a['estudiante_documento'] ?? '') : '' ?></td>
                <td style="text-align:center;"><?= $a ? htmlspecialchars($a['estudiante_codigo'] ?? '') : '' ?></td>
                <td style="text-align:center;"><?= $a ? htmlspecialchars($a['estudiante_telefono'] ?? '') : '' ?></td>
                <td><?= $a ? htmlspecialchars($a['estudiante_direccion'] ?? '') : '' ?></td>
                <td><?= $a ? htmlspecialchars($a['estudiante_correo'] ?? '') : '' ?></td>
                <td style="text-align:center;">
                    <?php
                        $firmaSrc = null;
                        if (!empty($a['firma_path'])) {
                            $firmaSrc = '/' . htmlspecialchars($a['firma_path']);
                        } elseif (!empty($a['firma']) && str_starts_with($a['firma'], 'data:image/')) {
                            $firmaSrc = htmlspecialchars($a['firma']);
                        }
                    ?>
                    <?php if ($firmaSrc): ?>
                        <img src="<?= $firmaSrc ?>" alt="Firma" class="firma-img">
                    <?php endif; ?>
                </td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>

</div>
</body>
</html>
