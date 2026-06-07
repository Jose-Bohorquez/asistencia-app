# Flujos de Petición — Sistema de Gestión de Asistencia

**Versión:** 1.0 | **Fecha:** 2026-06-04

Cada flujo muestra exactamente qué archivos se ejecutan, en qué orden,
qué hace cada uno y qué devuelve al navegador.

---

## FLUJO 1 — Login

**Petición:** `GET/POST http://localhost:8080/public/?page=login`

```
ARCHIVO                  QUÉ HACE
──────────────────────────────────────────────────────────────────
public/.htaccess         Redirige la URL → public/index.php
public/index.php         ob_start() · headers seguridad · cookies sesión
config/config.php        session_start() · define APP_NAME, APP_URL
AppController.php        checkRememberToken() → no hay cookie → sigue
Router.php               page = "login" · auth=false (no requiere sesión)
                         csrf=true → valida token en POST
                         → ejecuta AuthController::login()

  ── Petición GET (mostrar formulario) ──────────────────────────
AuthController.php       render('auth/login', ['error' => ''])
views/auth/login.php     Muestra formulario con usuario + contraseña

  ── Petición POST (procesar login) ─────────────────────────────
AuthController.php       verifyCSRFToken($_POST['csrf_token']) ✓
                         sanitizeInput($username)
                         Usuario::authenticate($username, $password)

  Usuario.php            isIpBlocked($ip)
                         → bloqueada: return ['errors' => ['Bloqueada 15 min']]
                         SELECT * FROM usuarios WHERE username=? AND activo=1
                         password_verify($pass, $hash_bcrypt)
                         → FALLO:  recordFailedAttempt($ip)
                                   return ['errors' => ['Usuario o contraseña incorrectos']]
                         → ÉXITO:  clearFailedAttempts($ip)
                                   UPDATE usuarios SET ultimo_acceso=NOW()
                                   INSERT logs_sistema (accion='login')
                                   return $userData (sin campo password)

AuthController.php       → FALLO:  render login con $error visible
                         → ÉXITO:  $_SESSION['user_id'] = X
                                   $_SESSION['user_rol'] = 'admin'
                                   session_regenerate_id(true)
                                   si "recordarme": saveRememberToken() en BD + cookie 30 días
                                   INSERT logs_sistema (accion='login_success')
                                   redirect → ?page=dashboard

RESPUESTA:  HTML (formulario) en GET  |  Redirect 302 a dashboard en POST exitoso
```

---

## FLUJO 2 — Dashboard

**Petición:** `GET ?page=dashboard`

```
ARCHIVO                  QUÉ HACE
──────────────────────────────────────────────────────────────────
Router.php               page = "dashboard" · auth=true
                         isAuthenticated() → $_SESSION['user_id'] ✓
                         permissions = [] → sin permiso extra
                         → ejecuta AdminController::dashboard()

BaseController.php       __construct():
                           new Database() → config/database.php → MySQL
                           new MiddlewareManager($db)
                           currentUser = datos de $_SESSION
                           generateCSRFToken()
                           getUserPermissions()

AdminController.php      Según rol:
                           admin/super_admin → SELECT COUNT(*) de todas las tablas
                           profesor         → SELECT COUNT(*) filtrando por profesor_id
                         render('admin/dashboard', $estadisticas)

views/admin/dashboard.php  Muestra: cursos activos, sesiones hoy,
                             estudiantes, usuarios, accesos rápidos

RESPUESTA:  HTML con panel de estadísticas
```

---

## FLUJO 3 — Crear sesión de clase

**Petición:** `POST ?page=sesiones&action=create`

```
ARCHIVO                  QUÉ HACE
──────────────────────────────────────────────────────────────────
Router.php               page = "sesiones" · permissions=['sesiones_view']
                         MiddlewareManager::checkPermission('sesiones_view')
                           → RoleMiddleware: busca en $rolePermissions[rol]
                           → admin ✓ / profesor ✓ (permisos _own)
                         → ejecuta SesionesController::handleRequest()

SesionesController.php   action = "create" → create()
                         hasPermission('sesiones_create') ✓
                         REQUEST_METHOD === 'POST' ✓
                         processSessionForm()
                           verifyCSRFToken() ✓
                           sanitizeInput de todos los campos
                           Valida: fecha, hora_inicio, curso_id obligatorios

  Sesion.php             validate($data, rules)
                         token = bin2hex(random_bytes(32))   ← 64 chars únicos
                         INSERT INTO sesiones (..., token, estado='programada')
                         INSERT logs_sistema (accion='create')
                         return $insertId

SesionesController.php   INSERT logs_sistema (accion='sesion_created')
                         jsonResponse(['success'=>true, 'sesion_id'=>42])

RESPUESTA:  JSON  →  {"success": true, "sesion_id": 42}
```

---

## FLUJO 4 — Activar sesión (para que los estudiantes puedan registrarse)

**Petición:** `POST ?page=sesiones&action=activate`

```
ARCHIVO                  QUÉ HACE
──────────────────────────────────────────────────────────────────
SesionesController.php   action = "activate" → activate()
                         hasPermission('sesiones_activate') ✓
                         $id = intval($_POST['id'])
                         sesionModel->find($id) → verifica que existe
                         Profesor: verifica que sea su sesión
                         sesionModel->update($id, ['estado' => 'activa'])
                           UPDATE sesiones SET estado='activa' WHERE id=?
                         logActivity('sesion_activated', $id)
                         jsonResponse(['success'=>true, 'token' => $sesion['token']])

RESPUESTA:  JSON con el token para compartir con estudiantes
            {"success": true, "token": "a3f9b2e1c4d5..."}
```

---

## FLUJO 5 — Registro de asistencia (estudiante SIN login)

**Petición:** `GET/POST ?page=asistencia&token=a3f9b2e1...`

```
ARCHIVO                  QUÉ HACE
──────────────────────────────────────────────────────────────────
Router.php               page = "asistencia" · auth=FALSE (ruta pública)
                         → ejecuta AsistenciaController::handleRequest()

  ── Petición GET (mostrar formulario) ──────────────────────────
AsistenciaController.php getSesionInfo("a3f9b2e1...")
  Sesion.php             SELECT s.*, c.nombre, c.area, p.nombre
                           FROM sesiones s
                           JOIN cursos c ON s.curso_id = c.id
                           JOIN programas p ON c.programa_id = p.id
                           WHERE s.token = ?

AsistenciaController.php sesion['estado'] === 'activa' ✓
                         isSessionTimeValid():
                           ahora >= hora_inicio - 15min ✓
                           ahora <= hora_fin + 30min ✓
                         render('asistencia/registrar', $sesion)
views/asistencia/
  registrar.php          Muestra: nombre del curso, fecha, aula
                         Campos: nombre, documento, código, firma (canvas)

  ── Petición POST (guardar asistencia) ──────────────────────────
AsistenciaController.php verifyCSRFToken() ✓
                         sanitizeInput: documento, nombre, codigo
                         estudianteModel->findByDocumento($documento)

  Estudiante.php         SELECT * FROM estudiantes WHERE documento = ?
                         → no existe: INSERT INTO estudiantes (...)
                         → existe: UPDATE nombre/telefono/correo si cambiaron

AsistenciaController.php asistenciaModel->yaRegistroAsistencia($sesion_id, $est_id)
  Asistencia.php         SELECT id FROM asistencias
                           WHERE sesion_id=? AND estudiante_id=?
                         → ya existe: return ['error' => 'Ya registraste']
                         → no existe: continúa

  Asistencia.php         INSERT INTO asistencias (
                           sesion_id, estudiante_id, hora_registro,
                           estado_asistencia='presente',
                           firma=<base64_png>,
                           ip_registro, user_agent
                         )

AsistenciaController.php estudianteModel->estaInscritoEnCurso() → no inscrito:
  Estudiante.php           INSERT INTO cursos_estudiantes (curso_id, estudiante_id)
AsistenciaController.php logActivity('asistencia_registrada')
                         return ['success' => '¡Bienvenido/a {nombre}!']
                         render con mensaje de confirmación

RESPUESTA:  HTML con "Asistencia registrada correctamente. ¡Bienvenido/a Juan!"
```

---

## FLUJO 6 — Exportar asistencias a Excel

**Petición:** `GET ?page=exportar&tipo=asistencias&formato=excel&curso_id=5`

```
ARCHIVO                  QUÉ HACE
──────────────────────────────────────────────────────────────────
Router.php               page = "exportar"
                         permissions = ['reportes_export'] ✓
                         rate_limit = {max:10, window:3600}
                           Lee /tmp/rate_limit_<md5(user_id)>.json
                           Cuenta peticiones en última hora → < 10 ✓

ExportController.php     tipo = "asistencias" (en lista blanca) ✓
                         formato = "excel" (en lista blanca) ✓
                         hasPermission('reportes_export') ✓
                         obtenerDatos('asistencias')
                           hasPermission('asistencias_view') ✓
                           new Asistencia()

  Asistencia.php         SELECT a.*, e.nombre, e.documento,
                                s.fecha, s.hora_inicio, c.nombre as curso
                           FROM asistencias a
                           JOIN estudiantes e ON a.estudiante_id = e.id
                           JOIN sesiones s ON a.sesion_id = s.id
                           JOIN cursos c ON s.curso_id = c.id
                           WHERE c.id = 5
                           [si profesor: AND c.profesor_id = <mi_id>]

ExportController.php     realizarExportacion($datos, 'asistencias', 'excel')
app/utils/ExportHelper.php  prepareAsistenciaData() → formatea filas
                             exportToExcel():
                               header('Content-Type: application/vnd.ms-excel')
                               header('Content-Disposition: attachment; filename=...')
                               genera tabla HTML con estilos
                               echo $contenido

RESPUESTA:  Descarga directa del archivo Excel en el navegador
```

---

## FLUJO 7 — Gestionar usuarios (crear, solo super_admin)

**Petición:** `POST ?page=usuarios&action=create`

```
ARCHIVO                  QUÉ HACE
──────────────────────────────────────────────────────────────────
Router.php               permissions = ['usuarios_view']
                         roles = ['super_admin', 'admin']
                           $_SESSION['user_rol'] en la lista ✓

UsuariosController.php   action = "create" → create()
                         hasPermission('usuarios_create')
                           → admin: NO tiene este permiso → 403
                           → super_admin: SÍ ✓
                         processUserForm()
                           verifyCSRFToken() ✓
                           sanitizeInput: username, nombre, email, rol
                           strlen($password) >= 8 ✓
                           usuarioModel->findByUsername() → username libre ✓

  Usuario.php            password_hash($pass, PASSWORD_DEFAULT)  ← bcrypt
                         validate($data, rules)
                         usernameExists() → libre ✓
                         emailExists() → libre ✓
                         INSERT INTO usuarios (username, password, nombre, email, rol, activo=1)
                         INSERT logs_sistema (accion='create')
                         return $insertId

UsuariosController.php   INSERT logs_sistema (accion='usuario_created')
                         jsonResponse(['success'=>true, 'usuario_id'=>7])

RESPUESTA:  JSON  →  {"success": true, "usuario_id": 7}
```

---

## FLUJO 8 — Logout

**Petición:** `GET ?page=logout`

```
ARCHIVO                  QUÉ HACE
──────────────────────────────────────────────────────────────────
Router.php               page = "logout" · auth=true · csrf=true (solo POST)
                         → ejecuta AuthController::logout()

AuthController.php       INSERT logs_sistema (accion='logout')
                         if (cookie 'remember_token'):
                           Usuario::clearRememberToken($token)
                             UPDATE usuarios SET remember_token=NULL
                               WHERE remember_token = sha256($token)
                           setcookie('remember_token', '', time()-3600)
                         session_unset()
                         session_destroy()
                         setFlashMessage('Sesión cerrada', 'success')
                         redirect → ?page=login

RESPUESTA:  Redirect 302 a /login
```

---

## Mapa rápido de URLs → Archivos

```
URL (?page=X)          CONTROLADOR              MÉTODO LLAMADO
─────────────────────────────────────────────────────────────────
login                  AuthController           login()
logout                 AuthController           logout()
dashboard              AdminController          dashboard()
cursos                 AdminController          cursos()
sesiones               SesionesController       handleRequest() → action
sesiones + activate    SesionesController       activate()
sesiones + create      SesionesController       create()
sesiones + edit        SesionesController       edit()
sesiones + delete      SesionesController       delete()
asistencia             AsistenciaController     handleRequest() → action
asistencia + listar    AsistenciaController     listarAsistencias()
asistencia + exportar  AsistenciaController     exportarAsistencias()
usuarios               UsuariosController       handleRequest() → action
programas              ProgramasController      handleRequest() → action
exportar               ExportController         handleRequest() → export()
enviar_correo          EmailController          handleRequest() → action
─────────────────────────────────────────────────────────────────
Todas las rutas pasan por:
  public/index.php → AppController → Router → Middlewares → Controlador
```
