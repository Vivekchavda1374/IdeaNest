<?php
/**
 * Secure Database Connection and Query Handler
 * Prevents SQL injection with prepared statements
 */

class SecureDB {
    private static $connection = null;
    
    /**
     * Get secure database connection
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $host = "localhost";
                $user = "ictmu6ya_ideanest";
                $pass = "ictmu6ya_ideanest";
                $dbname = "ictmu6ya_ideanest";
                
                self::$connection = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection failed");
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Execute secure query with prepared statements
     */
    public static function query($sql, $params = []) {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Fetch single row
     */
    public static function fetchRow($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Fetch all rows
     */
    public static function fetchAll($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }
    
    /**
     * Insert record and return ID
     */
    public static function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = self::query($sql, $data);
        
        return $stmt ? self::getConnection()->lastInsertId() : false;
    }
    
    /**
     * Update record
     */
    public static function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "$key = :$key";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        $params = array_merge($data, $whereParams);
        
        return self::query($sql, $params);
    }
    
    /**
     * Delete record
     */
    public static function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return self::query($sql, $params);
    }
}