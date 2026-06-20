# CLAUDE.md

## Proyecto
Sistema de Control de Asistencia (SGAA) — Universidad del Tolima

Stack:
- PHP 8.x MVC sin framework
- MySQL 8 / MariaDB
- Tailwind CSS
- Dev con Docker
- URL dev: http://localhost:8080/public
- Prod: https://ut-edu.space/SGAA/public

## Regla principal de trabajo
Antes de modificar cualquier archivo:
1. Leer completo el controlador, modelo, vista y JS relacionados.
2. Explicar en máximo 5 líneas cuál es la causa raíz probable.
3. Listar exactamente qué archivos tocarás.
4. Indicar cómo validarás manualmente el cambio en dev.
5. No escribir código hasta tener claro el flujo completo.

## Prohibiciones
- No asumir lógica de negocio no escrita.
- No rehacer vistas completas si el bug es backend, routing o JS puntual.
- No mezclar refactor visual con corrección funcional en el mismo paso.
- No cambiar nombres de variables, rutas, parámetros o acciones sin justificarlo.
- No envolver $customJS en tags <script>; layouts/base.php ya lo inyecta envuelto.
- No usar flash['message']; usar flash['text'].
- No tocar env.local.php ni inventar credenciales.
- No cambiar exportación PDF/Excel fuera de su flujo actual.
- No bloquear reactivación de sesiones finalizadas; Sesion::activar() ya no debe impedirlo.

## Arquitectura
Routing:
- public/index.php -> AppController
- index.php?page=X&action=Y -> Router -> Controller -> render('admin/vista', $data)

Estructura:
- public/index.php
- config/config.php
- config/database.php
- app/controllers/
- app/models/
- app/views/admin/
- app/utils/

## Autenticación y permisos
- Autenticación con sesión PHP
- RBAC con roles/permisos/rol_permisos
- Roles: super_admin, admin, profesor
- Middleware usa hasPermission('modulo_accion')

## Módulo sesiones: reglas importantes
- Tabla sesiones: estado enum(activa, finalizada, cancelada)
- FK curso_id
- Tabla asistencias depende de sesion_id con RESTRICT
- Sesion::delete() borra asistencias primero dentro de transacción
- Sesion::activar() sí puede reactivar sesiones finalizadas
- hora_fin en dev puede venir corrupta; la vista JS solo debe prellenar si hora_fin > hora_inicio

## Bug actual prioritario
Revisar por qué desde la vista de sesiones los botones "finalizar" y "activar" no funcionan correctamente.

Debes revisar de forma integral:
1. HTML del botón/enlace/formulario en la vista
2. URL generada (page/action/id/token si aplica)
3. Método HTTP esperado
4. Router / AppController
5. SesionesController
6. Método del modelo Sesion
7. Permisos RBAC
8. JS que intercepte click o submit
9. Flash messages y redirección final
10. Estado real persistido en BD

## Método obligatorio para este bug
Para este bug, sigue este orden exacto:
1. Trazar el flujo completo desde el botón hasta la actualización en BD.
2. Identificar si el problema es:
   - vista/HTML,
   - JS,
   - ruta,
   - permisos,
   - controlador,
   - modelo,
   - SQL/estado,
   - redirección/flash.
3. Proponer el cambio mínimo posible.
4. Aplicar el fix.
5. Probar manualmente al menos estos escenarios:
   - activar sesión cancelada o finalizada si la lógica lo permite
   - finalizar sesión activa
   - intentar acción sin permiso
   - verificar mensaje flash correcto
   - verificar persistencia en BD
6. Entregar reporte final con:
   - causa raíz
   - archivos modificados
   - flujo corregido
   - escenarios probados
   - pendientes/riesgos

## Convención para vistas y UX
- Mantener español en labels, textos y mensajes.
- Mejorar UX/UI solo después de dejar estable la funcionalidad.
- Para mejoras visuales, trabajar por pantalla, no rehacer todo el sistema de una vez.
- Priorizar claridad de tablas, formularios, badges de estado, botones de acción y feedback visual.
- No dañar responsive ni accesibilidad básica.
- Mantener consistencia con Tailwind existente.

## Validación manual mínima
Antes de dar la tarea por terminada, ejecutar:
- revisión del flujo en navegador
- revisión de logs PHP si falla
- revisión de request/response en Network
- verificación del cambio real en BD
- revisión visual desktop y móvil de la vista tocada

## Entrega obligatoria
Toda entrega final debe incluir:
- Diagnóstico
- Causa raíz
- Archivos modificados
- Qué cambió
- Cómo se probó
- Qué falta por validar manualmente
