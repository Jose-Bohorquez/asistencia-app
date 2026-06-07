<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Modelo TokenActivacion
 * Gestiona los tokens de activación de cuenta y reset de contraseña.
 * El token REAL nunca se almacena — solo su SHA-256.
 */
class TokenActivacion extends BaseModel {
    protected $table = 'tokens_activacion';
    protected $fillable = ['usuario_id', 'token', 'tipo', 'usado', 'expirado', 'expires_at', 'used_at'];

    const TIPO_ACTIVACION     = 'activacion';
    const TIPO_RESET_PASSWORD = 'reset_password';

    const EXPIRY_ACTIVACION_HORAS = 48;
    const EXPIRY_RESET_HORAS      = 1;

    /**
     * Genera un nuevo token para el usuario, invalida los anteriores del mismo tipo.
     * Devuelve el token REAL (hex 64) para enviar por correo.
     */
    public function generarToken(int $usuarioId, string $tipo = self::TIPO_ACTIVACION): string {
        $conn = $this->getConnection();

        // Marcar tokens anteriores del mismo tipo como expirados
        $stmt = $conn->prepare(
            "UPDATE tokens_activacion SET expirado = 1 WHERE usuario_id = ? AND tipo = ? AND usado = 0 AND expirado = 0"
        );
        $stmt->bind_param('is', $usuarioId, $tipo);
        $stmt->execute();
        $stmt->close();

        // Generar token real
        $tokenReal  = bin2hex(random_bytes(32)); // 64 hex chars
        $tokenHash  = hash('sha256', $tokenReal);
        $horasExpiry = ($tipo === self::TIPO_RESET_PASSWORD)
            ? self::EXPIRY_RESET_HORAS
            : self::EXPIRY_ACTIVACION_HORAS;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$horasExpiry} hours"));

        $stmt = $conn->prepare(
            "INSERT INTO tokens_activacion (usuario_id, token, tipo, expires_at) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('isss', $usuarioId, $tokenHash, $tipo, $expiresAt);
        $stmt->execute();
        $stmt->close();

        return $tokenReal;
    }

    /**
     * Valida un token recibido por URL.
     * Devuelve el registro del token si es válido y no usado/expirado.
     * Devuelve null si no es válido.
     */
    public function validarToken(string $tokenReal, string $tipo): ?array {
        $tokenHash = hash('sha256', $tokenReal);
        $conn = $this->getConnection();

        $stmt = $conn->prepare(
            "SELECT * FROM tokens_activacion
             WHERE token = ? AND tipo = ? AND usado = 0 AND expirado = 0 AND expires_at > NOW()
             LIMIT 1"
        );
        $stmt->bind_param('ss', $tokenHash, $tipo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Marca el token como usado.
     */
    public function marcarUsado(int $tokenId): void {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "UPDATE tokens_activacion SET usado = 1, used_at = NOW() WHERE id = ?"
        );
        $stmt->bind_param('i', $tokenId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Verifica si el usuario tiene un token de activación pendiente sin usar.
     */
    public function tienePendiente(int $usuarioId, string $tipo = self::TIPO_ACTIVACION): bool {
        $conn = $this->getConnection();
        $stmt = $conn->prepare(
            "SELECT COUNT(*) as total FROM tokens_activacion
             WHERE usuario_id = ? AND tipo = ? AND usado = 0 AND expirado = 0 AND expires_at > NOW()"
        );
        $stmt->bind_param('is', $usuarioId, $tipo);
        $stmt->execute();
        $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total > 0;
    }
}
