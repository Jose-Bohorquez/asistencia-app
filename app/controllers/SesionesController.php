
// ... existing code ...
if (isset($_GET['activate']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    $id = intval($_GET['activate']);
    $stmt = $pdo->prepare("UPDATE sesiones SET estado = 'activa' WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?page=sesiones&success=Sesión activada');
    exit;
}

if (isset($_GET['deactivate']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    $id = intval($_GET['deactivate']);
    $stmt = $pdo->prepare("UPDATE sesiones SET estado = 'finalizada' WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php?page=sesiones&success=Sesión finalizada');
    exit;
}
// ... existing code ...

// ... existing code ...
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : null;
    $estado = $_POST['estado'];

    // Genera el token único
    $token = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales

    // Inserta la sesión en la base de datos (ajusta el nombre de tu conexión y consulta según tu estructura)
    $stmt = $pdo->prepare("INSERT INTO sesiones (curso_id, fecha, hora_inicio, hora_fin, estado, token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$curso_id, $fecha, $hora_inicio, $hora_fin, $estado, $token]);

    // Redirige o muestra mensaje de éxito
    header('Location: index.php?page=sesiones&success=Sesión creada correctamente');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    // Recoge los datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['h