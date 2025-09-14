<?php

/********************************************
Archivo php PrincipalModel.php - MODIFICADO                       
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

class PrincipalModel extends Query
{

    public function __construct()
    {
        parent::__construct();

    }

    // 1. Obtiene la información de un usuario por su ID.
    public function getUsuario($correo)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioporcorreop(?)";
        $datos = array($correo);
        return $this->select($sql, $datos);
    }
    // 2. Obtiene un usuario por su correo si su estado es 1
    public function getUsuarioPorId($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioid(?)";
        $datos = [$id];
        return $this->select($sql, $datos);
    }
    // 3. cambiar la clave por primera vez
    public function cambiarClaveInicial($id_usuario, $nueva_clave)
    {
        $sql = "SELECT success, mensaje, id_usuario_actualizado FROM fun_cambiarclaveinicial(?, ?)";
        $datos = [$id_usuario, $nueva_clave];
        $resultado = $this->select($sql, $datos);
        return !empty($resultado) && $resultado['success'] === true;
    }

    // 4. Registra una nueva solicitud de usuario SIN contraseña
    public function registrarSolicitud($nombre, $apellido, $correo, $telefono, $direccion)
    {
        $sql = "SELECT * FROM fun_registrarsolicitud(?, ?, ?, ?, ?)";
        $datos = [$nombre, $apellido, $correo, $telefono, $direccion];
        $resultado = $this->select($sql, $datos);
        return $resultado;
    }

    // 5. Procesa y aprueba una solicitud de registro, creando un nuevo usuario
    public function aprobarSolicitud($id_solicitud, $id_admin)
    {
        try {
            $sql = "SELECT * FROM fun_aprobarsolicitud(?, ?)";
            $resultado = $this->select($sql, [$id_solicitud, $id_admin]);

            if (!$resultado) {
                return [
                    'success' => false,
                    'mensaje' => 'Error al ejecutar el procedimiento almacenado'
                ];
            }

            if ($resultado['success']) {
                $contrasena_temporal = $resultado['contrasena_temporal'];
                $hash_php = password_hash($contrasena_temporal, PASSWORD_DEFAULT);
                $sqlUpdate = "UPDATE usuarios SET clave = ? WHERE id = ?";
                $updateResult = $this->save($sqlUpdate, [$hash_php, $resultado['id_usuario_creado']]);
                if (!$updateResult) {
                    $sqlDelete = "DELETE FROM usuarios WHERE id = ?";
                    $this->save($sqlDelete, [$resultado['id_usuario_creado']]);

                    return [
                        'success' => false,
                        'mensaje' => 'Error al establecer la contraseña del usuario'
                    ];
                }
                try {
                    $solicitud = $this->select("SELECT * FROM solicitudes_registro WHERE id = ?", [$id_solicitud]);

                    if ($solicitud) {
                        $emailEnviado = $this->emailService->enviarSolicitudAprobada(
                            $solicitud['correo'],
                            $solicitud['nombre'],
                            $solicitud['apellido'],
                            $solicitud['correo'],
                            $contrasena_temporal
                        );

                        if (!$emailEnviado) {
                            error_log("Error al enviar email de aprobación para usuario: " . $solicitud['correo']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Excepción al enviar email: " . $e->getMessage());
                }
                return [
                    'success' => true,
                    'mensaje' => $resultado['mensaje'],
                    'id_usuario_creado' => $resultado['id_usuario_creado']
                ];
            } else {
                return [
                    'success' => false,
                    'mensaje' => $resultado['mensaje']
                ];
            }
        } catch (PDOException $e) {
            error_log("Error en aprobarSolicitud: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error interno del sistema'
            ];
        }
    }

    // 6. Rechaza una solicitud de registro usando la función PostgreSQL
    public function rechazarSolicitud($id_solicitud, $id_admin)
    {
        try {
            // Obtener datos de la solicitud antes de procesarla
            $solicitud = $this->select("SELECT * FROM solicitudes_registro WHERE id = ? AND estado = 0", [$id_solicitud]);

            if (!$solicitud) {
                return [
                    'success' => false,
                    'mensaje' => 'Solicitud no encontrada o ya procesada'
                ];
            }

            // Llamar al procedimiento almacenado
            $sql = "SELECT * FROM fun_rechazarsolicitud(?, ?)";
            $resultado = $this->select($sql, [$id_solicitud, $id_admin]);

            if (!$resultado) {
                return [
                    'success' => false,
                    'mensaje' => 'Error al ejecutar el procedimiento almacenado'
                ];
            }

            // Si la solicitud fue rechazada exitosamente, enviar el correo
            if ($resultado['success']) {
                try {
                    $emailEnviado = $this->emailService->enviarSolicitudRechazada(
                        $solicitud['correo'],
                        $solicitud['nombre'],
                        $solicitud['apellido']
                    );

                    if (!$emailEnviado) {
                        error_log("Error al enviar email de rechazo para usuario: " . $solicitud['correo']);
                    }
                } catch (Exception $e) {
                    error_log("Excepción al enviar email de rechazo: " . $e->getMessage());
                }

                return [
                    'success' => true,
                    'mensaje' => $resultado['mensaje']
                ];
            } else {
                return [
                    'success' => false,
                    'mensaje' => $resultado['mensaje']
                ];
            }
        } catch (PDOException $e) {
            error_log("Error en rechazarSolicitud: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error interno del sistema'
            ];
        }
    }
}