
# Project Title

este proyecto nace de la idea de mitigar el gasto de papel, considrando a demas el tema de la asistencia en una entidad educativa, ademas mejorando y optimizando el proceso de registro de asistencia y claro apoyando a la vision de la sostenibilidad en cuanto a la reduccion del uso de papel 

# 📊 Sistema de Gestión de Asistencia

<div align="center">

![Logo del Sistema](https://github.com/Jose-Bohorquez/asistencia-app/blob/main/public/assets/img/logo.png)

*Sistema web para el registro y control de asistencia de estudiantes*

</div>

## 📋 Descripción

Sistema web diseñado específicamente para instituciones educativas que permite a profesores y administradores gestionar cursos, sesiones y registrar la asistencia de estudiantes con firma digital.

---

## ✨ Características Actuales

### 👥 Gestión de Usuarios
- **Roles de usuario**: Administrador y Profesor
- **Autenticación**: Sistema de login seguro con contraseñas encriptadas
- **Control de acceso**: Funcionalidades específicas según el rol del usuario

### 📚 Gestión de Cursos
- **Creación y edición**: Información detallada de cada curso
- **Campos disponibles**: Código, nombre, programa, área, semestre, grupo, aula y sede
- **Listado de cursos**: Visualización de todos los cursos registrados

### 🗓️ Gestión de Sesiones
- **Programación**: Creación de sesiones con fecha y hora
- **Estados**: Activa, finalizada o cancelada
- **Control**: Activación/desactivación según necesidad

### ✍️ Registro de Asistencia
- **Formulario**: Captura de datos completos del estudiante
- **Firma digital**: Registro con firma del estudiante
- **Validación**: Prevención de registros duplicados

### 📊 Exportación de Datos
- **Formatos**: Visualización en pantalla, PDF y Excel
- **Plantillas**: Formato institucional oficial
- **Impresión**: Directamente desde el navegador

### 📈 Dashboard
- **Estadísticas**: Total de cursos, sesiones y estudiantes
- **Sesiones activas**: Visualización rápida
- **Accesos rápidos**: Enlaces a funcionalidades principales

---

### 🛠️ Características Técnicas

- **Arquitectura MVC**: Separación de lógica de negocio, datos y presentación
- **Responsive Design**: Interfaz adaptable usando Tailwind CSS
- **Base de datos**: MySQL para almacenamiento de datos
- **Seguridad**: Protección contra inyección SQL y XSS
- **Dockerización**: Configuración para despliegue con Docker

---

## 💻 Requisitos del Sistema

- PHP 8.0 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Navegador web moderno

---

## 🚀 Instalación

### 📦 Instalación Local (XAMPP)

1. **Clonar el repositorio** en la carpeta `htdocs` de XAMPP:
   ```bash
   git clone https://github.com/tu-usuario/asistencia-app.git
   cd asistencia-app
   ```

2. **Configurar la base de datos**:
   - Iniciar MySQL y Apache en XAMPP
   - Abrir phpMyAdmin en [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Crear una nueva base de datos llamada `asistencia_db`
   - Importar el archivo `database.sql` ubicado en la carpeta `database`

3. **Configurar la conexión a la base de datos**:
   - Abrir el archivo `config/database.php`
   - Modificar las constantes de conexión según tu entorno local

4. **Configurar permisos de carpetas**:
   - Asegurarse que la carpeta `uploads` y sus subcarpetas tengan permisos de escritura

5. **Acceder a la aplicación**:
   - Navegador: [http://localhost/asistencia-app](http://localhost/asistencia-app)
   - Credenciales por defecto: `admin / admin123`

### 🐳 Instalación con Docker

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/asistencia-app.git
   cd asistencia-app
   ```

2. Construir la imagen de Docker:
   ```bash
   docker build -t asistencia-app .
   ```

3. La aplicación estará disponible en:
   - Aplicación web: [http://localhost:8080](http://localhost:8080)
   - phpMyAdmin: [http://localhost:8081](http://localhost:8081)

---

## 🧭 Primer Uso

1. Iniciar sesión con las credenciales por defecto
2. Cambiar contraseña desde Perfil > Cambiar contraseña
3. Configuración inicial desde Configuración > General
4. Crear cursos y sesiones
5. Registrar asistencia y compartir enlace a estudiantes
6. Exportar reportes en formato deseado

---

## 📁 Estructura del Proyecto

- `app/`: Lógica de la aplicación
- `config/`: Configuración
- `controllers/`: Controladores
- `database/`: Archivos SQL
- `models/`: Modelos
- `public/`: Archivos públicos (CSS, JS, imágenes)
- `views/`: Vistas

---

## 🧩 Futuras Implementaciones

- Notificaciones por correo electrónico
- App móvil para estudiantes
- Integración con sistemas académicos
- Reconocimiento facial
- Justificación de inasistencias
- Reportes estadísticos avanzados
- API REST

---

## 🛠️ Solución de Problemas

### 1. Error de conexión a la base de datos
- Verificar servicio MySQL
- Revisar credenciales en `config/database.php`
- Confirmar existencia de la base de datos

### 2. Problemas de permisos en `uploads`
- Dar permisos de escritura

### 3. Página en blanco
- Revisar logs en `xampp/php/logs`
- Verificar `mod_rewrite` esté activo

---

## 🤝 Contribución

1. Haz un fork del repositorio
2. Crea una rama para tu funcionalidad
3. Realiza tus cambios y haz commit
4. Envía un pull request

---

## 📜 Licencia

Este proyecto está licenciado bajo la Licencia MIT.

---

## 📬 Contacto

- Email: soporte@asistencia-app.com
- GitHub: [https://github.com/tu-usuario/asistencia-app](https://github.com/tu-usuario/asistencia-app)