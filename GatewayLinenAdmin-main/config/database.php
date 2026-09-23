<?php
class Database
{
    private $serverName   = "TR-HYFFVT2\SQLEXPRESS";
    private $databaseName = "GatewayLinenDB";
    private $username     = "gateway_admin";
    private $password     = "Gateway@2026#Admin";

    /** @var mixed */
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        $connectionOptions = [
            "Database" => $this->databaseName,
            "UID" => $this->username,
            "PWD" => $this->password,
            "TrustServerCertificate" => true,
            "CharacterSet" => "UTF-8"
        ];

        $this->conn = sqlsrv_connect($this->serverName, $connectionOptions);

        if ($this->conn === false) {
            die(json_encode([
                "success" => false,
                "message" => "Database connection failed",
                "errors" => sqlsrv_errors()
            ]));
        }
        return $this->conn;
    }
}

// ---- YAHAN HAI ASLI FIX ----
// Class ka object banakar $conn variable ko initialize karna zaroori tha
$database = new Database();
$conn = $database->getConnection();
