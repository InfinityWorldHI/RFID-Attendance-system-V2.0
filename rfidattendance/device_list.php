<?php
include "connectDB.php";

if (!isset($_SESSION['Admin-name'])) {
  $error = new stdClass();
  $error->code = 1;
  $error->message = "not logged in";
  http_response_code(503);
  echo json_encode($error);
  exit;
}
header('Content-Type: application/json');
$data = new stdClass();
$data->data = getAllDevices();
echo json_encode($data);
