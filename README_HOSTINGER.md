# 🎓 Sistema de Asistencia - Universidad del Tolima
## 📋 Guía de Instalación para Hostinger

### 📦 Archivos Incluidos
- `database_hostinger.sql` - DDL completo de la base de datos
- `install_hostinger.php` - Script de instalación automática
- `generar_hash.php` - Generador de hash para contraseñas
- `config/email.php` - Configuración de email

---

## 🚀 Instalación Paso a Paso

### 1️⃣ Subir Archivos a Hostinger
1. Comprime todos los archivos del proyecto
2. Sube el archivo comprimido a tu hosting de Hostinger
3. Extrae los archivos en la carpeta `public_html` (o la carpeta raíz de tu dominio)

### 2️⃣ Crear Base de Datos
1. Ve al **Panel de Control de Hostinger**
2. Busca **"Bases de datos MySQL"**
3. Crea una nueva base de datos:
   - Nombre: `asistencia_db` (o el que prefieras)
   - Usuario: crea un usuario con permisos completos
   - Anota: **nombre de BD**, **usuario** y **contraseña**

### 3️⃣ Configurar Base de Datos
1. Ve a **phpMyAdmin** desde el panel de Hostinger
2. Selecciona tu base de datos
3. Ve a la pestaña **"SQL"**
4. Copia y pega todo el contenido de `database_hostinger.sql`
5. Haz clic en **"Continuar"**

### 4️⃣ Configurar Conexión a BD
Edita el archivo `config/database.php` con tus datos:

```php
// En la sección "else" (línea ~15), cambia:
$this->host = 'localhost'; // Generalmente es localhost en Hostinger
$this->db_name = 'tu_nombre_de_bd'; // El nombre que creaste
$this->username = 'tu_usuario_bd'; // Usuario de la BD
$this->password = 'tu_password_bd'; // Contraseña de la BD
```

### 5️⃣ Ejecutar Instalación Automática
1. Ve a: `https://tudominio.com/install_hostinger.php`
2. Sigue los 4 pasos del instalador:
   - ✅ Probar conexión a BD
   - 👤 Crear usuario administrador
   - 📧 Configurar email
   - 📨 Probar envío de email

---

## 📧 Configuración de Email

### Opción 1: Gmail (Recomendado)
1. **Activa verificación en 2 pasos** en tu cuenta de Google
2. Ve a **"Contraseñas de aplicación"**
3. Genera una contraseña para **"Correo"**
4. Usa estos datos en el instalador:
   - Servidor SMTP: `smtp.gmail.com`
   - Puerto: `587`
   - Usuario: `tu-email@gmail.com`
   - Contraseña: `la-contraseña-de-aplicación-generada`
   - Encriptación: `TLS`

### Opción 2: Email del Dominio (Hostinger)
1. Crea una cuenta de email en tu panel de Hostinger
2. Usa estos datos:
   - Servidor SMTP: `smtp.hostinger.com`
   - Puerto: `587`
   - Usuario: `noreply@tudominio.com`
   - Contraseña: `contraseña-del-email`
   - Encriptación: `TLS`

---

## 🔐 Credenciales por Defecto

**Usuario:** `superadmin`  
**Contraseña:** `SuperAdmin2024!` (puedes cambiarla en el instalador)

---

## 🛠️ Configuración Manual (Alternativa)

### Si prefieres no usar el instalador automático:

#### 1. Generar Hash de Contraseña
```bash
# Ve a: https://tudominio.com/generar_hash.php
# Copia el hash generado
```

#### 2. Crear Usuario Manualmente
```sql
INSERT INTO usuarios (username, password, nombre, email, rol) VALUES 
('superadmin', 'EL_HASH_GENERADO', 'Super Administrador', 'admin@tudominio.com', 'super_admin');
```

#### 3. Configurar Email Manualmente
```sql
INSERT INTO configuracion (clave, valor) VALUES 
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', 'tu-email@gmail.com'),
('smtp_password', 'tu-app-password'),
('smtp_encryption', 'tls'),
('email_from', 'noreply@tudominio.com'),
('email_from_name', 'Sistema de Asistencia - Universidad del Tolima');
```

---

## 🔒 Seguridad Post-Instalación

### ⚠️ IMPORTANTE: Después de la instalación
1. **Elimina** `install_hostinger.php`
2. **Elimina** `generar_hash.php`
3. **Cambia** la contraseña del administrador
4. **Verifica** que los archivos de configuración no sean accesibles públicamente

---

## 🧪 Probar la Instalación

1. Ve a: `https://tudominio.com/public/`
2. Inicia sesión con las credenciales del administrador
3. Crea un programa académico de prueba
4. Crea un curso de prueba
5. Crea una sesión de prueba
6. Prueba la funcionalidad de exportar y enviar por correo

---

## 🆘 Solución de Problemas

### Error de Conexión a BD
- Verifica que los datos en `config/database.php` sean correctos
- Asegúrate de que el usuario tenga permisos completos
- Contacta al soporte de Hostinger si persiste

### Error de Envío de Email
- Verifica que la configuración SMTP sea correcta
- Para Gmail, asegúrate de usar "Contraseña de aplicación"
- Revisa los logs de error del servidor

### Error 500
- Verifica que todos los archivos se hayan subido correctamente
- Revisa los permisos de archivos (644 para archivos, 755 para carpetas)
- Verifica que PHP esté habilitado

### Composer/PHPMailer
- Si hay errores con PHPMailer, ejecuta: `composer install` en el servidor
- O sube manualmente la carpeta `vendor/` incluida en el proyecto

---

## 📞 Soporte

Si necesitas ayuda adicional:
1. Revisa los logs de error del servidor
2. Verifica la documentación de Hostinger
3. Contacta al desarrollador del sistema

---

## ✅ Checklist de Instalación

- [ ] Archivos subidos a Hostinger
- [ ] Base de datos creada
- [ ] SQL ejecutado en phpMyAdmin
- [ ] `config/database.php` configurado
- [ ] Instalador ejecutado exitosamente
- [ ] Email configurado y probado
- [ ] Usuario administrador creado
- [ ] Archivos de instalación eliminados
- [ ] Sistema probado y funcionando

¡Listo! Tu sistema de asistencia está funcionando en Hostinger. 🎉