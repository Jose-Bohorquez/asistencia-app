<?php
// Cargar configuración
require_once '../config/config.php';

// Inicializar la aplicación
require_once '../app/controllers/AppController.php';

$app = new AppController();
$app->start();