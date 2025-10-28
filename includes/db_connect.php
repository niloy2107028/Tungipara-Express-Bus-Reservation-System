<?php
// Database connection class
// Database e connect korar jonno ei class use korbo

class Database
{
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn;
    private $error;

    // Database connect korbo
    public function connect()
    {
        $this->conn = null;

        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            // Charset utf8mb4 set korlam
            $this->conn->set_charset("utf8mb4");

            // Bangladesh timezone set korlam
            $this->conn->query("SET time_zone = '+06:00'");
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            die("Database connection error: " . $this->error);
        }

        return $this->conn;
    }

    // Connection return korbo
    public function getConnection()
    {
        if ($this->conn === null) {
            return $this->connect();
        }
        return $this->conn;
    }

    // Connection bondho korbo
    public function close()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    // Query run kore result return korbo
    public function query($sql)
    {
        $conn = $this->getConnection();
        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception("Query error: " . $conn->error);
        }

        return $result;
    }

    // Prepared statement execute korbo
    public function prepare($sql)
    {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare statement error: " . $conn->error);
        }

        return $stmt;
    }

    // Escape string
    public function escape($value)
    {
        $conn = $this->getConnection();
        return $conn->real_escape_string($value);
    }

    // Get last insert ID
    public function lastInsertId()
    {
        return $this->conn->insert_id;
    }

    // Begin transaction
    public function beginTransaction()
    {
        return $this->conn->begin_transaction();
    }

    // Commit transaction
    public function commit()
    {
        return $this->conn->commit();
    }

    // Rollback transaction
    public function rollback()
    {
        return $this->conn->rollback();
    }
}

// Create global database instance
$database = new Database();
$conn = $database->connect();
