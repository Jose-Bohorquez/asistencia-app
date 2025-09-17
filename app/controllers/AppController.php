<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/AdminController.php';
require_once __DIR__ . '/AsistenciaController.php';
require_once __DIR__ . '/UsuariosController.php';
require_once __DIR__ . '/ProgramasController.php';
require_once __DIR__ . '/SesionesController.php';
require_once __DIR__ . '/EmailController.php';
require_once __DIR__ . '/ExportController.php';
require_once __DIR__ . '/../core/Router.php';

/**
 * Controlador principal de la aplicación
 * Maneja el enrutamiento y la inicialización de la aplicación usando el nuevo sistema Router
 */
class AppController extends BaseController {
    private $router;
    
    public function __construct() {
        parent::__construct();
        $this->router = new Router($this->middlewareManager);
    }
    
    /**
     * Iniciar la aplicación usando el nuevo sistema de rutas
     */
    public function start() {
        try {
            // Delegar al router para manejar la solicitud
            $this->router->dispatch();
            
        } catch (Exception $e) {
            $this->handleAppError($e, 'Error en la aplicación');
        }
    }
    
    /**
     * Implementación del método abstracto handleRequest
     * Maneja las peticiones HTTP usando el sistema de rutas
     */
    public function handleRequest() {
        $this->start();
    }
    
    /**
     * Generar token CSRF para formularios
     */
    public function getCsrfToken() {
        return Router::generateCsrfToken();
    }
    
    /**
     * Manejar errores de la aplicación
     */
    protected function handleAppError($exception, $userMessage = 'Ha ocurrido un error') {
        // Log del error
        error_log('AppController Error: ' . $exception->getMessage());
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => $userMessage], 500);
        } else {
            $this->setFlashMessage($userMessage, 'error');
            
            if ($this->isAuthenticated()) {
                $this->redirect('index.php?page=dashboard');
            } else {
                $this->redirect('index.php?page=login');
            }
        }
    }
}