# Arquitectura del Sistema — Sistema de Gestión de Asistencia

**Versión:** 1.0 | **Fecha:** 2026-06-04

---

## Patrón de diseño: MVC personalizado en PHP puro

El sistema usa **Model-View-Controller** sin ningún framework externo. Todo el código de la arquitectura está escrito a medida dentro del proyecto.

---

## Las 7 capas — de la petición a la respuesta

```
NAVEGADOR
    │
    │  http://localhost:8080/public/?page=sesiones
    ▼
┌──────────────────────────────────────────────────────────────┐
│  1. ENTRADA                                                  │
│                                                              │
│  public/.htaccess ──► Redirige todas las URLs a index.php   │
│  public/index.php ──► Punto único de entrada                 │
│       • ob_start()                (buffer anti-errores)      │
│       • Headers de seguridad HTTP (X-Frame, CSP, etc.)       │
│       • Configuración de cookies de sesión (httponly, etc.)  │
│       • require config/config.php (constantes + session)     │
└──────────────────────┬───────────────────────────────────────┘
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  2. ARRANQUE DE LA APLICACIÓN                                │
│                                                              │
│  AppController.php                                           │
│       • checkRememberToken() ──► intenta auto-login          │
│       • new Router($middlewareManager)                       │
│       • router->dispatch()                                   │
└──────────────────────┬───────────────────────────────────────┘
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  3. ENRUTAMIENTO  (app/core/Router.php)                      │
│                                                              │
│  Lee $_GET['page']  →  busca en tabla de rutas               │
│                                                              │
│  Por cada petición verifica EN ORDEN:                        │
│    ① ¿Requiere HTTPS?       → redirect si no                 │
│    ② ¿Usuario autenticado?  → redirect a /login si no        │
│    ③ ¿Tiene el permiso?     → 403 si no                      │
│    ④ ¿Tiene el rol?         → 403 si no                      │
│    ⑤ ¿Token CSRF válido?    → 403 si no (solo en POST)       │
│    ⑥ ¿Rate limit OK?        → 429 si superado                │
│                                                              │
│  → Si todo OK: instancia el controlador y llama el método    │
└──────────────────────┬───────────────────────────────────────┘
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  4. MIDDLEWARE  (app/middleware/)                             │
│                                                              │
│  Middleware.php         → clase base abstracta               │
│  AuthMiddleware.php     → valida sesión, timeout, regenera   │
│  RoleMiddleware.php     → verifica permiso en $rolePermisos  │
│  MiddlewareManager.php  → orquesta y expone checkPermission()│
└──────────────────────┬───────────────────────────────────────┘
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  5. CONTROLADORES  (app/controllers/)                        │
│                                                              │
│  BaseController.php        → clase base con helpers comunes  │
│  │  • render($vista, $data)    • redirect($url)              │
│  │  • jsonResponse($data)      • sanitizeInput($val)         │
│  │  • generateCSRFToken()      • verifyCSRFToken()           │
│  │  • hasPermission($perm)     • logActivity(...)            │
│  │                                                           │
│  ├─ AuthController.php      login, logout, reset password    │
│  ├─ AdminController.php     dashboard, cursos                │
│  ├─ SesionesController.php  CRUD sesiones + activar/finalizar│
│  ├─ AsistenciaController.php registro público, reportes      │
│  ├─ UsuariosController.php  CRUD usuarios                    │
│  ├─ ProgramasController.php CRUD programas                   │
│  ├─ ExportController.php    descargas Excel/PDF/CSV          │
│  └─ EmailController.php     envío SMTP con PHPMailer         │
└──────────────────────┬───────────────────────────────────────┘
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  6. MODELOS  (app/models/)                                   │
│                                                              │
│  BaseModel.php    → find, all, create, update, delete,       │
│                     query, validate, logActivity             │
│  │                                                           │
│  ├─ Usuario.php      authenticate + remember-me + lockout    │
│  ├─ Sesion.php       genera token, estados, filtros por rol  │
│  ├─ Asistencia.php   registrar, yaRegistro, estadísticas     │
│  ├─ Curso.php        inscribir/desinscribir estudiantes      │
│  ├─ Programa.php     CRUD + stats asociados                  │
│  ├─ Estudiante.php   búsqueda por documento/curso            │
│  └─ Email.php        datos para reportes por correo          │
└──────────────────────┬───────────────────────────────────────┘
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  6b. CAPA DE DATOS                                           │
│                                                              │
│  config/database.php  → crea conexión MySQLi                 │
│  config/env.local.php → credenciales (NO versionado)         │
│  MySQL 8.0            → base de datos asistencia_db          │
└──────────────────────┬───────────────────────────────────────┘
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  7. VISTAS  (app/views/)                                     │
│                                                              │
│  layouts/
│  ├─ header.php          → <head> + Tailwind CDN + FA         │
│  ├─ navbar.php          → menú según rol del usuario         │
│  ├─ base.php            → estructura HTML completa           │
│  └─ footer.php / footer_content.php                          │
│                                                              │
│  admin/
│  ├─ dashboard.php       → estadísticas y accesos rápidos     │
│  ├─ cursos.php          → tabla + formulario de cursos       │
│  ├─ sesiones.php        → tabla + formulario de sesiones     │
│  ├─ usuarios.php        → tabla + formulario de usuarios     │
│  ├─ programas.php       → tabla + formulario de programas    │
│  └─ exportar.php        → selección de exportación           │
│                                                              │
│  asistencia/
│  └─ registro.php        → formulario público con firma       │
│                                                              │
│  auth/
│  └─ login.php           → formulario de login                │
│                                                              │
│  components/            → form.php, button.php, table.php,   │
│                           modal.php, card.php, alert.php     │
└──────────────────────────────────────────────────────────────┘
```

---

## Cómo se construye una respuesta HTML

```
Controller::render('admin/sesiones', ['sesiones' => [...]])
    │
    ▼  BaseController::render()
    │   extract($data)  ← variables PHP disponibles en la vista
    │
    ├── include views/layouts/header.php   ← <html><head> + estilos
    ├── include views/layouts/navbar.php   ← menú según $_SESSION['rol']
    ├── include views/admin/sesiones.php   ← contenido de la página
    └── include views/layouts/footer.php   ← cierre + scripts JS
```

## Cómo se construye una respuesta JSON (AJAX)

```
Controller::jsonResponse(['success' => true, 'id' => 42], 200)
    │
    ├── http_response_code(200)
    ├── header('Content-Type: application/json')
    ├── echo json_encode($data)
    └── exit()
```

---

## Mapa de archivos del proyecto

```
asistencia-app/
│
├── public/                  ← única carpeta accesible desde el navegador
│   ├── index.php            ← ÚNICO punto de entrada
│   ├── .htaccess            ← redirige todo a index.php
│   └── assets/img/          ← logo.png y recursos estáticos
│
├── app/
│   ├── core/
│   │   └── Router.php       ← enrutamiento + seguridad por ruta
│   ├── middleware/
│   │   ├── Middleware.php          ← clase base abstracta
│   │   ├── AuthMiddleware.php      ← valida sesión y timeout
│   │   ├── RoleMiddleware.php      ← permisos por rol
│   │   └── MiddlewareManager.php   ← orquestador
│   ├── controllers/
│   │   ├── BaseController.php      ← helpers compartidos
│   │   ├── AppController.php       ← arranca la app
│   │   ├── AuthController.php
│   │   ├── AdminController.php
│   │   ├── SesionesController.php
│   │   ├── AsistenciaController.php
│   │   ├── UsuariosController.php
│   │   ├── ProgramasController.php
│   │   ├── ExportController.php
│   │   └── EmailController.php
│   ├── models/
│   │   ├── BaseModel.php           ← CRUD base
│   │   ├── Usuario.php
│   │   ├── Sesion.php
│   │   ├── Asistencia.php
│   │   ├── Curso.php
│   │   ├── Programa.php
│   │   ├── Estudiante.php
│   │   └── Email.php
│   ├── utils/
│   │   └── ExportHelper.php        ← genera Excel, PDF, CSV
│   └── views/
│       ├── layouts/                ← header, navbar, footer, base
│       ├── admin/                  ← vistas del panel
│       ├── asistencia/             ← formulario público
│       ├── auth/                   ← login
│       └── components/             ← piezas reutilizables
│
├── config/
│   ├── config.php           ← constantes + session_start()
│   ├── database.php         ← conexión adaptativa por entorno
│   ├── email.php            ← configuración SMTP
│   ├── env.example.php      ← plantilla de credenciales (versionado)
│   └── env.local.php        ← credenciales reales (NO versionado)
│
├── docs/                    ← documentación técnica
├── vendor/                  ← PHPMailer (Composer)
├── asistencia_db.sql        ← esquema completo + datos de prueba
├── migration.sql            ← cambios incrementales de BD
├── docker-compose.yml       ← 3 servicios: app, db, phpmyadmin
└── Dockerfile               ← imagen PHP 8.2-Apache + Composer
```

---

## Tecnologías y versiones

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Backend | PHP | 8.2+ |
| Base de datos | MySQL | 8.0 |
| Servidor web | Apache | 2.4 |
| CSS Framework | Tailwind CSS (CDN) | 3 |
| Firma digital | Signature Pad (CDN) | 4.0 |
| Alertas UI | SweetAlert2 (CDN) | 11 |
| Iconos | Font Awesome (CDN) | 6 |
| Email | PHPMailer | 6.10+ |
| Contenedores | Docker + Compose | v2 |
