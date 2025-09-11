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


require_once './Services/email_service.php';
class UsuariosModel extends Query
{
    // Prepara la clase
    private $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->emailService = new EmailService();
    }

    // Obtiene todos los usuarios con estado activo
    public function getUsuarios()
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, rol, estado, fecha FROM obtener_todos_usuarios()";
        return $this->selectAll($sql);
    }

    // Obtiene los datos de un usuario por su ID
    public function getUsuario($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha,fecha_ultimo_cambio_clave FROM obtener_usuario_por_id(?)";
        $datos = array($id);
        return $this->select($sql, $datos);
    }

    // Verifica si un item (correo, telefono, etc.) ya existe en la base de datos para un usuario
    public function getVerificar($item, $nombre, $id)
    {
        $sql = "SELECT id FROM verificar_campo_usuario(?, ?, ?)";
        $datos = array($item, $nombre, $id);
        return $this->select($sql, $datos);
    }

    // Registra un nuevo usuario en la base de datos
    public function registrar($nombre, $apellido, $correo, $telefono, $direccion, $clave, $rol)
    {
        try {
            $sql = "SELECT * FROM registrar_usuario(?, ?, ?, ?, ?, ?, ?)";
            $datos = array($nombre, $apellido, $correo, $telefono, $direccion, $clave, $rol);
            $resultado = $this->select($sql, $datos);

            if (!$resultado) {
                error_log("No se recibió resultado del procedimiento almacenado registrar_usuario");
                return false;
            }

            if (isset($resultado['success']) && $resultado['success'] === true) {
                return $resultado['id_usuario_creado'];
            } else {
                $mensaje = isset($resultado['mensaje']) ? $resultado['mensaje'] : 'Error desconocido al registrar usuario';
                error_log("Error en registrar_usuario: " . $mensaje);
                throw new Exception($mensaje);
            }
        } catch (PDOException $e) {
            error_log("Error PDO en registrar(): " . $e->getMessage());
            throw new Exception("Error en la base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error general en registrar(): " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    // Modifica los datos de un usuario existente
    public function modificar($nombre, $apellido, $correo, $telefono, $direccion, $rol, $id)
    {
        try {
            $sql = "SELECT * FROM modificar_usuario(?, ?, ?, ?, ?, ?, ?)";
            $datos = array($id, $nombre, $apellido, $correo, $telefono, $direccion, $rol);
            $resultado = $this->select($sql, $datos);

            if (!$resultado) {
                error_log("No se recibió resultado del procedimiento almacenado modificar_usuario");
                return false;
            }

            if (isset($resultado['success']) && $resultado['success'] === true) {
                return 1;
            } else {
                $mensaje = isset($resultado['mensaje']) ? $resultado['mensaje'] : 'Error desconocido al modificar usuario';
                error_log("Error en modificar_usuario: " . $mensaje);
                throw new Exception($mensaje);
            }
        } catch (PDOException $e) {
            error_log("Error PDO en modificar(): " . $e->getMessage());
            throw new Exception("Error en la base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error general en modificar(): " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    // Cambia el estado de un usuario (activo/inactivo)
    public function delete($id)
    {
        try {
            $sql = "SELECT * FROM cambiar_estado_usuario(?)";
            $datos = array($id);
            $resultado = $this->select($sql, $datos);

            if (!$resultado) {
                error_log("No se recibió resultado del procedimiento almacenado cambiar_estado_usuario");
                return false;
            }

            if (isset($resultado['success']) && $resultado['success'] === true) {
                return 1;
            } else {
                $mensaje = isset($resultado['mensaje']) ? $resultado['mensaje'] : 'Error desconocido al cambiar estado del usuario';
                error_log("Error en cambiar_estado_usuario: " . $mensaje);
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error PDO en delete(): " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general en delete(): " . $e->getMessage());
            return false;
        }
    }


    // Cuenta los archivos compartidos de un usuario específico por su correo
    public function verificarEstado($correo)
    {
        try {
            $sql = "SELECT * FROM verificar_estado_solicitudes_compartidos(?)";
            $datos = array($correo);
            $resultado = $this->select($sql, $datos);

            if (!$resultado) {
                error_log("No se recibió resultado del procedimiento almacenado verificar_estado_solicitudes_compartidos");
                return ['total' => 0];
            }
            return [
                'total' => $resultado['total'],
                'correo' => $resultado['correo'],
                'mensaje' => $resultado['mensaje']
            ];
        } catch (PDOException $e) {
            error_log("Error PDO en verificarEstado(): " . $e->getMessage());
            return ['total' => 0];
        } catch (Exception $e) {
            error_log("Error general en verificarEstado(): " . $e->getMessage());
            return ['total' => 0];
        }
    }

    // Actualiza los datos de perfil de un usuario
    public function actualizarPerfil($nombre, $apellido, $correo, $telefono, $direccion, $id)
    {
        try {
            $sql = "SELECT * FROM actualizar_perfil_usuario(?, ?, ?, ?, ?, ?)";
            $datos = array($id, $nombre, $apellido, $correo, $telefono, $direccion);
            $resultado = $this->select($sql, $datos);

            if (!$resultado) {
                error_log("No se recibió resultado del procedimiento almacenado actualizar_perfil_usuario");
                return false;
            }

            if (isset($resultado['success']) && $resultado['success'] === true) {
                return 1;
            } else {
                $mensaje = isset($resultado['mensaje']) ? $resultado['mensaje'] : 'Error desconocido al actualizar perfil';
                error_log("Error en actualizar_perfil_usuario: " . $mensaje);
                throw new Exception($mensaje);
            }
        } catch (PDOException $e) {
            error_log("Error PDO en actualizarPerfil(): " . $e->getMessage());
            throw new Exception("Error en la base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error general en actualizarPerfil(): " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    // Cambia la contraseña de un usuario
    public function cambiarClave($clave, $id)
    {
        try {
            $sql = "SELECT * FROM cambiar_clave_usuario(?, ?)";
            $datos = array($id, $clave);
            $resultado = $this->select($sql, $datos);

            if (!$resultado) {
                error_log("No se recibió resultado del procedimiento almacenado cambiar_clave_usuario");
                return false;
            }

            if (isset($resultado['success']) && $resultado['success'] === true) {
                return 1;
            } else {
                $mensaje = isset($resultado['mensaje']) ? $resultado['mensaje'] : 'Error desconocido al cambiar contraseña';
                error_log("Error en cambiar_clave_usuario: " . $mensaje);
                throw new Exception($mensaje);
            }
        } catch (PDOException $e) {
            error_log("Error PDO en cambiarClave(): " . $e->getMessage());
            throw new Exception("Error en la base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error general en cambiarClave(): " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    // Obtiene todas las solicitudes de registro pendientes
    public function getUsuariosSolicitados()
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, fecha FROM obtener_solicitudes_pendientes ()";
        return $this->selectAll($sql);
    }


    // Procesa y aprueba una solicitud de registro, creando un nuevo usuario
    public function aprobarSolicitud($id_solicitud, $id_admin)
    {
        try {
            $sql = "SELECT * FROM aprobar_solicitud_registro(?, ?)";
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

    // Procesa y rechaza una solicitud de registro
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
            $sql = "SELECT * FROM rechazar_solicitud_registro(?, ?)";
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

    public function verificarSolicitudPendiente($id_solicitud)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, fecha_solicitud, estado, id_usuario_admin, fecha_procesado FROM verificar_solicitud_pendiente(?)";
        return $this->select($sql, [$id_solicitud]);
    }

    // Actualiza la ruta del avatar de un usuario
    public function actualizarAvatar($id, $ruta_avatar)
    {
        try {
            $sql = "SELECT * FROM actualizar_avatar_usuario(?, ?)";
            $datos = array($id, $ruta_avatar);
            $resultado = $this->select($sql, $datos);

            if (!$resultado) {
                error_log("No se recibió resultado del procedimiento almacenado actualizar_avatar_usuario");
                return false;
            }

            if (isset($resultado['success']) && $resultado['success'] === true) {
                return 1;
            } else {
                $mensaje = isset($resultado['mensaje']) ? $resultado['mensaje'] : 'Error desconocido al actualizar avatar';
                error_log("Error en actualizar_avatar_usuario: " . $mensaje);
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error PDO en actualizarAvatar(): " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general en actualizarAvatar(): " . $e->getMessage());
            return false;
        }
    }
}
