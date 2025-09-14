<?php

/********************************************
Archivo php UsuariosModel.php
Creado por el equipo Gaes 1:
Anyi Solayi Tapias
Sharit Delgado Pinzón
Durly Yuranni Sánchez Carillo
Año: 2025
SENA - CSET - ADSO
 ********************************************/


require_once ROOT_PATH . 'Services/email_service.php';
class UsuariosModel extends Query
{
    // Prepara la clase
    private $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new EmailService();
    }


    // 1. Obtiene los datos de un usuario por su ID
    public function getUsuario($correo)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM fun_obtenerusuarioporcorreo(?)";
        $datos = array($correo);
        return $this->select($sql, $datos);
    }

    // 2. Obtiene todos los usuarios con estado activo
    public function getUsuarios($valor, $id_usuario)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, rol, estado, fecha,avatar FROM fun_buscarusuariosporcorreo(?, ?)";
        return $this->selectAll($sql, [$valor, $id_usuario]);
    }

    // 3. Verifica si un item (correo, telefono, etc.) ya existe en la base de datos para un usuario
    public function getVerificar($item, $nombre, $id)
    {
        $sql = "SELECT id FROM fun_verificarcampo(?, ?, ?)";
        $datos = array($item, $nombre, $id);
        return $this->select($sql, $datos);
    }

    // 4. Registra un nuevo usuario en la base de datos
    public function registrar($nombre, $apellido, $correo, $telefono, $direccion, $clave, $rol)
    {
        $sql = "SELECT success, mensaje, id_usuario_creado FROM fun_registrarusuario(?, ?, ?, ?, ?, ?, ?)";
        $datos = array($nombre, $apellido, $correo, $telefono, $direccion, $clave, $rol);
        $resultado = $this->select($sql, $datos);
        return $resultado;
    }

    // 5. Modifica los datos de un usuario existente
    public function modificar($nombre, $apellido, $correo, $telefono, $direccion, $rol, $id)
    {
        $sql = "SELECT success, mensaje, id_usuario_modificado FROM fun_modificarusuario(?, ?, ?, ?, ?, ?, ?)";
        $datos = array($id, $nombre, $apellido, $correo, $telefono, $direccion, $rol);
        return $this->save($sql, $datos);
    }

    // 6. Cambia el estado de un usuario (activo/inactivo)
    public function delete($id)
    {
        $sql = "SELECT success, mensaje, estado_anterior, estado_nuevo FROM fun_cambiarestadousuario(?)";
        $datos = array($id);
        $resultado = $this->select($sql, $datos);
        return $resultado;
    }

    // 7. Cuenta los archivos compartidos de un usuario específico por su correo
    public function verificarEstado($correo)
    {
        $sql = "SELECT total FROM fun_verificarestadosolicitudescompartidas(?)";
        $resultado = $this->select($sql, [$correo]);
        return $resultado;
    }

    // 8. Actualiza los datos de perfil de un usuario
    public function actualizarPerfil($nombre, $apellido, $correo, $telefono, $direccion, $id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion FROM fun_actualizarperfil(?, ?, ?, ?, ?, ?)";
        $datos = array($id, $nombre, $apellido, $correo, $telefono, $direccion);
        $resultado = $this->select($sql, $datos);
        return $resultado;
    }

    // 9. Cambia la contraseña de un usuario
    public function cambiarClave($clave, $id)
    {
        $sql = "SELECT id, success, mensaje FROM fun_cambiarclave(?, ?)";
        $datos = array($id, $clave);
        $resultado = $this->select($sql, $datos);
        return $resultado;
    }

    // 10. Obtiene todas las solicitudes de registro pendientes
    public function getUsuariosSolicitados()
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, fecha FROM fun_obtenersolicitudes()";
        return $this->selectAll($sql);
    }

    // 11. Procesa y aprueba una solicitud de registro, creando un nuevo usuario
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

    // 12. Procesa y rechaza una solicitud de registro
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

    // 13. Verifica si una solicitud de registro está pendiente
    public function verificarSolicitudPendiente($id_solicitud)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, fecha_solicitud, estado, id_usuario_admin, fecha_procesado FROM fun_verificarsolicitudpendiente(?)";
        return $this->select($sql, [$id_solicitud]);
    }

    // 14. Actualiza la ruta del avatar de un usuario
    public function actualizarAvatar($id, $ruta_avatar)
    {
        $sql = "SELECT * FROM fun_actualizaravatarusuario(?, ?)";
        $datos = array($id, $ruta_avatar);
        $resultado = $this->select($sql, $datos);
        return $resultado;
    }

    // 15. Actualiza el token de acceso de Google para un usuario específico en la base de datos.
    public function actualizarAccessToken($id_usuario, $accessTokenJson)
    {
        $sql = "SELECT fun_actualizartokenaccesousuario(?, ?)";
        $datos = array($id_usuario, $accessTokenJson);
        return $this->save($sql, $datos);
    }

    // 16. Obtiene una lista de usuarios que tienen conectada su cuenta de Google, incluyendo sus tokens de acceso y actualización.
    public function getUsuariosConGoogleConectado()
    {
        $sql = "SELECT id_usuario, google_access_token, google_refresh_token FROM fun_obtenerusuariosgoogle()";
        return $this->selectAll($sql);
    }
}
