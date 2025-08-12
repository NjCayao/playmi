<?php

/**
 * Modelo Base PLAYMI
 * Clase padre que contiene funcionalidades comunes para todos los modelos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/constants.php';

class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los registros
     */
    public function findAll($orderBy = 'id', $order = 'ASC')
    {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$order}";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en findAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener registro por ID
     */
    public function findById($id)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en findById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear nuevo registro
     */
    public function create($data)
    {
        try {
            // Remover campos que no existen en la tabla
            $data = $this->filterValidFields($data);

            // Agregar timestamps si existen en la tabla
            if ($this->hasField('created_at') && !isset($data['created_at'])) {
                $data['created_at'] = date(DB_DATETIME_FORMAT);
            }
            if ($this->hasField('updated_at') && !isset($data['updated_at'])) {
                $data['updated_at'] = date(DB_DATETIME_FORMAT);
            }

            $fields = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute($data);

            if ($result) {
                return $this->db->lastInsertId();
            }

            return false;
        } catch (Exception $e) {
            $this->logError("Error en create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar registro
     */
    public function update($id, $data)
    {
        try {
            // Remover campos que no existen en la tabla
            $data = $this->filterValidFields($data);

            // Actualizar timestamp si existe
            if ($this->hasField('updated_at')) {
                $data['updated_at'] = date(DB_DATETIME_FORMAT);
            }

            $fields = [];
            foreach ($data as $key => $value) {
                $fields[] = "$key = :$key";
            }
            $fieldString = implode(', ', $fields);

            $sql = "UPDATE {$this->table} SET $fieldString WHERE {$this->primaryKey} = :id";
            $data['id'] = $id;
            $stmt = $this->db->prepare($sql);

            return $stmt->execute($data);
        } catch (Exception $e) {
            $this->logError("Error en update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar registro
     */
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            $this->logError("Error en delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar registros con condición opcional
     */
    public function count($condition = '')
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            if (!empty($condition)) {
                $sql .= " WHERE " . $condition;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            $this->logError("Error en count: " . $e->getMessage());
            return 0;
        }
    }



    /**
     * Obtener registros con paginación
     */
    public function paginate($page = 1, $limit = RECORDS_PER_PAGE, $where = '', $orderBy = 'id', $order = 'DESC')
    {
        try {
            $offset = ($page - 1) * $limit;

            // Obtener total de registros
            $totalRecords = $this->count($where);

            // Obtener registros de la página actual
            $sql = "SELECT * FROM {$this->table}";
            if ($where) {
                $sql .= " WHERE $where";
            }
            $sql .= " ORDER BY $orderBy $order LIMIT $limit OFFSET $offset";

            $stmt = $this->db->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalRecords / $limit),
                'has_next' => $page < ceil($totalRecords / $limit),
                'has_prev' => $page > 1
            ];
        } catch (Exception $e) {
            $this->logError("Error en paginate: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'pages' => 0,
                'has_next' => false,
                'has_prev' => false
            ];
        }
    }

    /**
     * Verificar si un campo existe en la tabla
     */
    protected function hasField($fieldName)
    {
        try {
            $sql = "DESCRIBE {$this->table}";
            $stmt = $this->db->query($sql);
            $fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return in_array($fieldName, $fields);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Filtrar solo campos válidos de la tabla
     */
    protected function filterValidFields($data)
    {
        try {
            $sql = "DESCRIBE {$this->table}";
            $stmt = $this->db->query($sql);
            $validFields = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $filteredData = [];
            foreach ($data as $key => $value) {
                if (in_array($key, $validFields)) {
                    $filteredData[$key] = $value;
                }
            }

            return $filteredData;
        } catch (Exception $e) {
            return $data; // Si falla, devolver datos originales
        }
    }

    /**
     * Registrar errores en log
     */
    protected function logError($message)
    {
        $logFile = LOGS_PATH . 'model-errors-' . date('Y-m-d') . '.log';
        $logEntry = date(DATETIME_FORMAT) . " - {$this->table}: $message" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Obtener último error de la base de datos
     */
    public function getLastError()
    {
        $errorInfo = $this->db->errorInfo();
        return $errorInfo[2] ?? 'Error desconocido';
    }
}
