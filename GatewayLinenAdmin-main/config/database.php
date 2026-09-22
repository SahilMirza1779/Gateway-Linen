<?php

/*
|--------------------------------------------------------------------------
| GatewayLinen Database Connection
|--------------------------------------------------------------------------
| This file ONLY creates the SQL Server connection.
| Do not put HTML/CSS here.
|--------------------------------------------------------------------------
*/

// 👇 Yahan apna actual SQL Server name dalo (Jaise: 'localhost\SQLEXPRESS' ya tumhare PC ka naam)
$serverName   = "TR-HYFFVT2\SQLEXPRESS";
$databaseName = "GatewayLinenDB";
$username     = "gateway_admin";
$password     = "Gateway@2026#Admin";

$connectionOptions = [
    "Database" => $databaseName,
    "UID" => $username,
    "PWD" => $password,
    "TrustServerCertificate" => true,
    "CharacterSet" => "UTF-8",
    "LoginTimeout" => 10
];

$conn = sqlsrv_connect(
    $serverName,
    $connectionOptions
);

if ($conn === false) {
    // Yeh line exact SQL Server ki error screen par print kar degi
    die("<pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
}
