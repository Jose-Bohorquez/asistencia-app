# Sistema de Gestión de Asistencia

Sistema web para el registro y control de asistencia de estudiantes mediante firma digital, diseñado para instituciones educativas. Elimina el uso de papel y optimiza el proceso de registro.

---

## Tabla de contenido

- [Requisitos previos](#requisitos-previos)
- [Opción A — Docker (recomendado)](#opción-a--docker-recomendado)
- [Opción B — XAMPP local](#opción-b--xampp-local)
- [Credenciales por defecto](#credenciales-por-defecto)
- [Configurar correo electrónico](#configurar-correo-electrónico)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Documentación técnica](#documentación-técnica)
- [Solución de problemas comunes](#solución-de-problemas-comunes)

---

## Requisitos previos

### Para Docker
| Herramienta | Versión mínima |
|-------------|---------------|
| Docker Desktop | 4.x |
| Docker Compose | v2 (incluido en Docker Desktop) |
| Git | cualquier versión |

### Para XAMPP
| Herramienta | Versión mínima |
|-------------|---------------|
| XAMPP | 8.2+ (PHP 8.2 + Apache + MySQL) |
| Composer | 2.x |
| Git | cualquier versión |

---

## Opción A — Docker (recomendado)

> No necesita instalar PHP, MySQL ni Apache en tu máquina.

### Paso 1 — Clonar el repositorio

```bash
git clone https://github.com/Jose-Bohorquez/asistencia-app.git
cd asistencia-app
```

### Paso 2 — Levantar los servicios

```bash
docker compose up -d
```

Esto levanta tres contenedores:

| Servicio | URL | Descripción |
|----------|-----|-------------|
| **App** | http://localhost:8080 | Aplicación principal |
| **phpMyAdmin** | http://localhost:8081 | Gestión visual de la BD |
| **MySQL** | localhost:3306 | Base de datos (acceso interno) |

> La primera vez tarda ~2 minutos porque descarga las imágenes e importa la base de datos automáticamente.

### Paso 3 — Verificar que todo esté corriendo

```bash
docker compose ps
```

Deberías ver los tres servicios con estado `running` o `healthy`.

### Paso 4 — Abrir la aplicación

Abre tu navegador en **http://localhost:8080**

Ingresa con las [credenciales por defecto](#credenciales-por-defecto).

### Comandos útiles de Docker

```bash
# Ver logs en tiempo real
docker compose logs -f

# Ver logs solo de la app
docker compose logs -f app

# Detener los contenedores (conserva los datos)
docker compose stop

# Detener y eliminar contenedores (conserva el volumen mysql_data)
docker compose down

# Eliminar TODO incluyendo la base de datos
docker compose down -v

# Reconstruir la imagen (después de cambios en Dockerfile)
docker compose up -d --build
```

### Credenciales de la base de datos (Docker)

| Parámetro | Valor |
|-----------|-------|
| Host | `db` (interno) / `localhost:3306` (externo) |
| Base de datos | `asistencia_db` |
| Usuario | `developer` |
| Contraseña | `developer` |
| Root password | `root_secret` |

---

## Opción B — XAMPP local

### Paso 1 — Clonar el repositorio

Clona el repositorio dentro del directorio `htdocs` de XAMPP:

```bash
# Windows
cd C:\xampp\htdocs
git clone https://github.com/Jose-Bohorquez/asistencia-app.git

# macOS
cd /Applications/XAMPP/htdocs
git clone https://github.com/Jose-Bohorquez/asistencia-app.git

# Linux
cd /opt/lampp/htdocs
git clone https://github.com/Jose-Bohorquez/asistencia-app.git
```

### Paso 2 — Instalar dependencias PHP

```bash
cd asistencia-app
composer install
```

### Paso 3 — Crear la base de datos

1. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
2. Abre **phpMyAdmin** en http://localhost/phpmyadmin
3. Crea una nueva base de datos llamada `asistencia_db` con cotejamiento `utf8mb4_unicode_ci`.
4. Selecciona la base de datos `asistencia_db`.
5. Ve a **Importar** → selecciona el archivo `asistencia_db.sql` → clic en **Continuar**.

### Paso 4 — Configurar credenciales locales

Copia la plantilla de configuración:

```bash
cp config/env.example.php config/env.local.php
```

Edita `config/env.local.php` con tus datos:

```php
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'asistencia_db',
    'DB_USER' => 'root',      // usuario de tu MySQL local
    'DB_PASS' => '',          // contraseña de tu MySQL local (XAMPP = vacía por defecto)
];
```

> `config/env.local.php` está en `.gitignore` y **nunca se sube al repositorio**.

### Paso 5 — Abrir la aplicación

Abre tu navegador en:

```
http://localhost/asistencia-app/public/
```

---

## Opción C — Hostinger (Producción)

> Shared hosting con hPanel. PHP 8.2 requerido.

### Paso 1 — Configurar el dominio y PHP

1. En **hPanel → Hosting → Gestionar → PHP**:  selecciona **PHP 8.2** (o superior).
2. En **hPanel → Dominios → Gestionar → Directorio raíz**, cambia el *Document Root* del dominio a:
   ```
   public_html/asistencia-app/public
   ```
   > Esto es crítico. La carpeta `public/` debe ser la raíz web; el resto del código **no** debe ser accesible desde el navegador.

### Paso 2 — Subir el código

**Si tienes SSH (plan Business o superior):**

```bash
# Conectarse al servidor
ssh usuario@tudominio.com

# Ir al directorio raíz
cd ~/public_html

# Clonar el repositorio
git clone https://github.com/Jose-Bohorquez/asistencia-app.git

# Instalar dependencias (sin paquetes de desarrollo)
cd asistencia-app
composer install --no-dev --optimize-autoloader

# Permisos del directorio de fotos de perfil
chmod 775 storage/perfiles/
```

**Si no tienes SSH (solo FTP / File Manager):**

1. Localmente, ejecuta `composer install --no-dev --optimize-autoloader` para generar `vendor/`.
2. Comprime todo el proyecto en un `.zip`.
3. Súbelo a **hPanel → File Manager → public_html/** y extrae.
4. La estructura debe quedar como `public_html/asistencia-app/`.

### Paso 3 — Crear la base de datos

1. Ve a **hPanel → Bases de datos → Bases de datos MySQL**.
2. Crea una nueva base de datos, p.ej. `u12345_asistencia`.
3. Crea un usuario y asígnalo a la base de datos con **todos los privilegios**.
4. Abre **phpMyAdmin → selecciona la BD → Importar → elige `asistencia_db.sql`**.

### Paso 4 — Configurar las credenciales de producción

Crea el archivo `config/env.local.php` en el servidor (vía File Manager o SFTP):

```php
<?php
return [
    // Base de datos (valores del Paso 3)
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'u12345_asistencia',
    'DB_USER' => 'u12345_usuario',
    'DB_PASS' => 'contraseña-segura',

    // URL pública del sitio (sin barra final)
    'APP_URL'  => 'https://tudominio.com',
    'APP_NAME' => 'Sistema de Asistencia',

    // SMTP — usa el correo de Hostinger o Gmail con App Password
    'SMTP_HOST'       => 'mail.tudominio.com',   // o smtp.gmail.com
    'SMTP_PORT'       => '465',                  // 587 para Gmail TLS
    'SMTP_USER'       => 'sistema@tudominio.com',
    'SMTP_PASS'       => 'contraseña-smtp',
    'SMTP_ENCRYPTION' => 'ssl',                  // 'tls' para Gmail
    'SMTP_FROM_EMAIL' => 'sistema@tudominio.com',
    'SMTP_FROM_NAME'  => 'Sistema de Asistencia',
];
```

> Este archivo está en `.gitignore` y **nunca** se versiona.

### Paso 5 — Verificar la instalación

Abre `https://tudominio.com` en el navegador. Deberías ver la pantalla de inicio de sesión.

Si ves un error 500, revisa los logs en **hPanel → Registros de errores** o activa temporalmente:
```php
// Al inicio de public/index.php — SOLO para diagnóstico, quitar después
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Lista de verificación pre-producción

- [ ] PHP 8.2 activo en hPanel
- [ ] Document Root apunta a `public_html/asistencia-app/public`
- [ ] `config/env.local.php` creado con datos reales
- [ ] Base de datos importada (`asistencia_db.sql`)
- [ ] `storage/perfiles/` tiene permisos de escritura (`chmod 775`)
- [ ] SSL/HTTPS activo en el dominio (hPanel → SSL)
- [ ] Contraseña del administrador cambiada desde la por defecto

---

## Credenciales por defecto

> Cambia la contraseña del administrador inmediatamente después del primer acceso.

| Campo | Valor |
|-------|-------|
| Usuario | `admin` |
| Contraseña | `admin123` |
| Rol | Administrador |

También están cargados en el SQL de ejemplo:

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| `admin` | `admin123` | Administrador |
| `superadmin` | `admin123` | Super Administrador |
| `profesor1` | `admin123` | Profesor |

---

## Configurar correo electrónico

El sistema puede enviar reportes de asistencia por correo. Para configurarlo:

1. Inicia sesión como administrador.
2. Ve a **Correo** → **Configuración SMTP**.
3. Completa los datos de tu proveedor:

### Gmail (recomendado)

| Campo | Valor |
|-------|-------|
| Host SMTP | `smtp.gmail.com` |
| Puerto | `587` |
| Encriptación | `TLS` |
| Usuario | tu-correo@gmail.com |
| Contraseña | Contraseña de aplicación de Google* |

> *Requiere activar verificación en 2 pasos y generar una **contraseña de aplicación** en https://myaccount.google.com/apppasswords

### Hostinger / cPanel

| Campo | Valor |
|-------|-------|
| Host SMTP | `mail.tudominio.com` |
| Puerto | `465` |
| Encriptación | `SSL` |
| Usuario | correo@tudominio.com |
| Contraseña | contraseña del buzón |

---

## Estructura del proyecto

```
asistencia-app/
├── app/
│   ├── controllers/        # Lógica de negocio (MVC)
│   ├── core/               # Router y sistema de rutas
│   ├── middleware/         # Autenticación, roles y CSRF
│   ├── models/             # Acceso a datos (BaseModel + modelos)
│   ├── utils/              # ExportHelper (Excel, PDF, CSV)
│   └── views/              # Plantillas HTML con Tailwind CSS
│       ├── admin/          # Vistas del panel administrativo
│       ├── asistencia/     # Formulario público de registro
│       ├── auth/           # Login y recuperación de contraseña
│       ├── components/     # Componentes reutilizables
│       └── layouts/        # Header, navbar, footer, base
├── config/
│   ├── config.php          # Constantes globales de la app
│   ├── database.php        # Conexión adaptativa (Docker/XAMPP/Hostinger)
│   ├── email.php           # Configuración de proveedores SMTP
│   ├── env.example.php     # Plantilla de credenciales (versionar)
│   └── env.local.php       # Credenciales reales (NO versionar, en .gitignore)
├── docs/
│   ├── casos-de-uso.md     # 11 casos de uso documentados
│   ├── requisitos.md       # Requisitos funcionales y no funcionales
│   ├── diagrama-bd.md      # ERD en Mermaid + índices + relaciones
│   └── diccionario-datos.md# Descripción de cada tabla y columna
├── docker/
│   └── init.sql            # SQL de inicialización (backup)
├── public/
│   ├── index.php           # Único punto de entrada (front controller)
│   ├── .htaccess           # Rewrite rules para URLs limpias
│   └── assets/
│       └── img/            # Imágenes estáticas (logo, etc.)
├── vendor/                 # Dependencias de Composer (PHPMailer)
├── asistencia_db.sql       # Esquema completo de la base de datos
├── migration.sql           # Scripts de migración incremental
├── composer.json           # Dependencias PHP
├── Dockerfile              # Imagen Docker de la app
└── docker-compose.yml      # Orquestación de servicios
```

---

## Documentación técnica

| Documento | Descripción |
|-----------|-------------|
| [docs/roles-usuarios.md](docs/roles-usuarios.md) | Roles, usuarios de prueba, permisos, acceso de estudiantes |
| [docs/arquitectura.md](docs/arquitectura.md) | Capas MVC, mapa de archivos, tecnologías |
| [docs/flujos.md](docs/flujos.md) | Flujos detallados por petición (login, sesión, asistencia, exportar...) |
| [docs/casos-de-uso.md](docs/casos-de-uso.md) | 11 casos de uso con flujos principales y alternativos |
| [docs/requisitos.md](docs/requisitos.md) | Requisitos funcionales y no funcionales |
| [docs/diagrama-bd.md](docs/diagrama-bd.md) | ERD renderizable en Mermaid |
| [docs/diccionario-datos.md](docs/diccionario-datos.md) | Tablas, columnas, tipos y restricciones |

---

## Solución de problemas comunes

### Docker: el contenedor de la app no conecta con la BD

```bash
# Ver qué está pasando
docker compose logs db
docker compose logs app

# Esperar a que MySQL esté healthy y reiniciar la app
docker compose restart app
```

### Docker: puerto 8080 ocupado

Edita `docker-compose.yml` y cambia el puerto de la app:
```yaml
ports:
  - "9090:80"   # cambia 8080 por cualquier puerto libre
```

### XAMPP: página en blanco o error 500

1. Activa `display_errors` temporalmente en `php.ini` para ver el error.
2. Verifica que `mod_rewrite` esté habilitado en Apache.
3. Revisa que `config/env.local.php` exista y tenga las credenciales correctas.

### XAMPP: error "mod_rewrite not enabled"

En XAMPP Windows, edita `C:\xampp\apache\conf\httpd.conf` y descomenta:
```
LoadModule rewrite_module modules/mod_rewrite.so
```

### Error al importar el SQL: "Unknown character set utf8mb4"

Tu MySQL es muy antiguo. Usa MySQL 5.7+ o reemplaza `utf8mb4` por `utf8` en `asistencia_db.sql`.

### Firma digital no aparece en móvil

El canvas de firma requiere HTTPS en algunos navegadores móviles modernos. En desarrollo local esto es normal; en producción configura un certificado SSL.

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.2, patrón MVC sin framework |
| Base de datos | MySQL 8.0 + MySQLi con Prepared Statements |
| Frontend | Tailwind CSS 3 (CDN), JavaScript vanilla |
| Firma digital | Signature Pad 4.0 |
| Alertas UI | SweetAlert2 11 |
| Iconos | Font Awesome 6 |
| Email | PHPMailer 6.10 (SMTP) |
| Contenedores | Docker + Docker Compose |

---

## Contacto

- **Autor:** Jose Bohorquez
- **Email:** jose.bohorquez@servitel.co
- **GitHub:** [github.com/Jose-Bohorquez](https://github.com/Jose-Bohorquez/asistencia-app)
