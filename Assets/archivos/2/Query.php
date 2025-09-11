<?php
class Query extends Conexion {
    private $pdo, $con, $sql, $datos;

    public function __construct() {
        $this->pdo = new Conexion();
        $this->con = $this->pdo->conect();
    }

    public function select(string $sql, array $params = [])
    {
        $this->sql = $sql;
        $resul = $this->con->prepare($this->sql);
        $resul->execute($params); 
        $data = $resul->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function selectAll(string $sql, array $params = [])
    {
        $this->sql = $sql;
        $resul = $this->con->prepare($this->sql);
        $resul->execute($params);
        $data = $resul->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function save(string $sql, array $datos)
    {
        $this->sql = $sql;
        $this->datos = $datos;
        $insert = $this->con->prepare($this->sql);
        $data = $insert->execute($this->datos);
        if ($data) {
            $res = 1;
        } else {
            $res = 0;
        }
        return $res;
    }

    public function insertar(string $sql, array $datos)
    {
        $this->sql = $sql;
        $this->datos = $datos;
        $insert = $this->con->prepare($this->sql);
        $data = $insert->execute($this->datos);
        if ($data) {
            // Para PostgreSQL, necesitamos obtener el ID de manera diferente
            try {
                // Intentar obtener el último ID insertado
                $res = $this->con->lastInsertId();
                
                // Si lastInsertId() no funciona en PostgreSQL, usar RETURNING
                if (!$res && strpos($this->sql, 'RETURNING') === false) {
                    // Si es una inserción sin RETURNING, intentar obtener el máximo ID
                    if (preg_match('/INSERT INTO (\w+)/', $this->sql, $matches)) {
                        $table = $matches[1];
                        $stmt = $this->con->query("SELECT currval(pg_get_serial_sequence('$table', 'id'))");
                        $res = $stmt->fetchColumn();
                    }
                }
            } catch (Exception $e) {
                // Si todo falla, devolver 1 para indicar éxito
                $res = 1;
            }
        } else {
            $res = 0;
        }
        return $res;
    }

    // MÉTODOS DE TRANSACCIONES
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction()
    {
        return $this->con->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public function commit()
    {
        return $this->con->commit();
    }

    /**
     * Revertir transacción
     */
    public function rollback()
    {
        return $this->con->rollBack();
    }

    // MÉTODOS ADICIONALES PARA POSTGRESQL

    /**
     * Método específico para inserciones con RETURNING en PostgreSQL
     */
    public function insertarConRetorno(string $sql, array $datos)
    {
        $this->sql = $sql;
        $this->datos = $datos;
        $insert = $this->con->prepare($this->sql);
        $data = $insert->execute($this->datos);
        if ($data) {
            // Para consultas con RETURNING, obtener el resultado
            $result = $insert->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : 1;
        } else {
            return 0;
        }
    }

    /**
     * Verificar si estamos usando PostgreSQL
     */
    private function isPostgreSQL()
    {
        return $this->con->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    }

    /**
     * Obtener información de la base de datos
     */
    public function getDatabaseInfo()
    {
        if ($this->isPostgreSQL()) {
            $stmt = $this->con->query("SELECT version() as version, current_database() as database");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->con->query("SELECT VERSION() as version, DATABASE() as database");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Establecer timezone
     */
    public function setTimezone($timezone = null)
    {
        if (!$timezone) {
            $timezone = date_default_timezone_get();
        }

        try {
            if ($this->isPostgreSQL()) {
                $this->con->exec("SET timezone = '$timezone'");
            } else {
                // Para MySQL
                $offset = date('P'); // Formato +05:00
                $this->con->exec("SET time_zone = '$offset'");
            }
        } catch (Exception $e) {
            error_log("Error estableciendo timezone: " . $e->getMessage());
        }
    }
}