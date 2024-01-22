<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


/* Database connection settings */
if (file_exists("./config.php")) {
    $config = include "./config.php";
} else {
    $config = include "./config.sample.php";
}
$db = $config["db"];
$conn = mysqli_connect($db["host"], $db["user"], $db["password"], $db["name"]);
unset($db);

if (!$conn || $conn->connect_error) {
    die("Database Connection failed: " . (!$conn ? "" : $conn->connect_error));
}

include "functions.php";
