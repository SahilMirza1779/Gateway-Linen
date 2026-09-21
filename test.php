<?php
$serverName = "LAPTOP-2L3H6B5C\\MSSQLSERVER02";
$connectionOptions = array(
    "Database" => "GatewayLinenDB",
    "UID" => "gateway_admin",
    "PWD" => "Gateway@2026#Admin",
    "TrustServerCertificate" => true
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn) {
    echo "Bhai, Connection Successful!";
} else {
    echo "Connection Failed. Asli Error ye hai:<br />";
    die(print_r(sqlsrv_errors(), true));
}
