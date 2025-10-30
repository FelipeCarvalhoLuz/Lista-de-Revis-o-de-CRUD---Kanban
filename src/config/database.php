<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'kanban_system');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn = null;

    public function connect() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            return $this->conn;
        } catch(PDOException $e) {
            echo "Erro de conexão: " . $e->getMessage();
            return null;
        }
    }

    public function disconnect() {
        $this->conn = null;
    }

    public function getConnection() {
        return $this->conn;
    }
}

function getDBConnection() {
    $database = new Database();
    return $database->connect();
}
?>
