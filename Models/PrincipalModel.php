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

    // Obtiene un usuario por su correo si su estado es 1 
    public function getUsuario($correo)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM obtener_usuario_por_correo(?)";
        $datos = [$correo];
        return $this->select($sql, $datos);
    }

    public function getUsuarioPorId($id)
    {
        $sql = "SELECT id, nombre, apellido, correo, telefono, direccion, clave, estado, rol, avatar, fecha, fecha_ultimo_cambio_clave FROM obtener_usuario_por_id(?)";
        $datos = [$id];
        return $this->select($sql, $datos);
    }

    public function cambiarClaveInicial($id_usuario, $nueva_clave)
    {
        $sql = "SELECT success, mensaje, id_usuario_actualizado FROM cambiar_clave_inicial_usuario(?, ?)";
        $datos = [$id_usuario, $nueva_clave];
        $resultado = $this->select($sql, $datos);

        // Retornar true/false para mantener compatibilidad con el código existente
        return !empty($resultado) && $resultado['success'] === true;
    }

    // MODIFICADO: Registra una nueva solicitud de usuario SIN contraseña
    public function registrarSolicitud($nombre, $apellido, $correo, $telefono, $direccion)
    {
        $sql = "SELECT * FROM registrar_solicitud_usuario(?, ?, ?, ?, ?)";
        $datos = [$nombre, $apellido, $correo, $telefono, $direccion];
        $resultado = $this->select($sql, $datos);

        // Ya no devuelve contrasena_temporal
        return $resultado;
    }

    // Genera una contraseña temporal usando la función de PostgreSQL
    public function generarContrasenaTemporal($longitud = 12)
    {
        $sql = "SELECT generar_contrasena_temporal(?) as contrasena_generada";
        $datos = [$longitud];
        $resultado = $this->select($sql, $datos);
        return $resultado['contrasena_generada'] ?? null;
    }

    // Valida la fortaleza de una contraseña usando la función de PostgreSQL
    public function validarFortalezaContrasena($contrasena)
    {
        $sql = "SELECT * FROM validar_fortaleza_contrasena(?)";
        $datos = [$contrasena];
        return $this->select($sql, $datos);
    }

    // MODIFICADO: Usa SOLO la función PostgreSQL completa para aprobar
    public function aprobarSolicitud($id_solicitud, $id_admin)
    {
        $sql = "SELECT * FROM aprobar_solicitud_registro(?, ?)";
        $datos = [$id_solicitud, $id_admin];
        return $this->select($sql, $datos);
    }

    // Rechaza una solicitud de registro usando la función PostgreSQL
    public function rechazarSolicitud($id_solicitud, $id_admin)
    {
        $sql = "SELECT * FROM rechazar_solicitud_registro(?, ?)";
        $datos = [$id_solicitud, $id_admin];
        return $this->select($sql, $datos);
    }
}