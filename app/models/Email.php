<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo Email
 * Maneja la funcionalidad de correos electrónicos
 */
class Email extends BaseModel {
    protected $table = 'emails';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'destinatario',
        'asunto',
        'mensaje',
        'tipo',
        'estado',
        'fecha_envio',
        'intentos',
        'error_mensaje',
        'usuario_id'
    ];
    
    protected $rules = [
        'destinatario' => 'required|email|max:255',
        'asunto' => 'required|max:255',
        'mensaje' => 'required',
        'tipo' => 'required|in:notificacion,recordatorio,bienvenida,recuperacion',
        'estado' => 'in:pendiente,enviado,fallido',
        'usuario_id' => 'integer'
    ];
    
    /**
     * Obtener emails por estado
     */
    public function getByEstado($estado) {
        $sql = "SELECT * FROM {$this->table} WHERE estado = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener emails pendientes
     */
    public function getPendientes() {
        return $this->getByEstado('pendiente');
    }
    
    /**
     * Marcar email como enviado
     */
    public function marcarEnviado($id) {
        return $this->update($id, [
            'estado' => 'enviado',
            'fecha_envio' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Marcar email como fallido
     */
    public function marcarFallido($id, $errorMensaje = null) {
        $data = [
            'estado' => 'fallido',
            'intentos' => $this->db->query("SELECT intentos FROM {$this->table} WHERE id = {$id}")->fetchColumn() + 1
        ];
        
        if ($errorMensaje) {
            $data['error_mensaje'] = $errorMensaje;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Obtener estadísticas de emails
     */
    public function getEstadisticas() {
        $sql = "SELECT 
                    estado,
                    COUNT(*) as total
                FROM {$this->table} 
                GROUP BY estado";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $estadisticas = [
            'pendiente' => 0,
            'enviado' => 0,
            'fallido' => 0
        ];
        
        foreach ($resultados as $resultado) {
            $estadisticas[$resultado['estado']] = (int)$resultado['total'];
        }
        
        return $estadisticas;
    }
    
    /**
     * Limpiar emails antiguos
     */
    public function limpiarAntiguos($dias = 30) {
        $sql = "DELETE FROM {$this->table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND estado = 'enviado'";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$dias]);
    }
    
    /**
     * Obtener emails por usuario
     */
    public function getByUsuario($usuarioId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE usuario_id = ? 
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear email de notificación
     */
    public function crearNotificacion($destinatario, $asunto, $mensaje, $usuarioId = null) {
        return $this->create([
            'destinatario' => $destinatario,
            'asunto' => $asunto,
            'mensaje' => $mensaje,
            'tipo' => 'notificacion',
            'estado' => 'pendiente',
            'usuario_id' => $usuarioId,
            'intentos' => 0
        ]);
    }
    
    /**
     * Crear email de recordatorio
     */
    public function crearRecordatorio($destinatario, $asunto, $mensaje, $usuarioId = null) {
        return $this->create([
            'destinatario' => $destinatario,
            'asunto' => $asunto,
            'mensaje' => $mensaje,
            'tipo' => 'recordatorio',
            'estado' => 'pendiente',
            'usuario_id' => $usuarioId,
            'intentos' => 0
        ]);
    }
}