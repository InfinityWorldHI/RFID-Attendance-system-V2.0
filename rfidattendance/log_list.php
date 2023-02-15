<?php
//Connect to database
require 'connectDB.php';

if (!isset($_SESSION['Admin-name'])) {
  $error = new stdClass();
  $error->code = 1;
  $error->message = "not logged in";
  http_response_code(503);
  echo json_encode($error);
  exit;
}

if (isset($_POST)) {
  $request = json_decode(file_get_contents('php://input'));

  //Start date filter
  $Start_date = filter_var($request->filter->date_sel_start, FILTER_VALIDATE_REGEXP, [
    'options' => [
      "regexp" => "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",
      "default" => date("Y-m-d")
    ]
  ]);

  //End date filter
  $End_date = filter_var($request->filter->date_sel_end, FILTER_VALIDATE_REGEXP, [
    'options' => [
      "regexp" => "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",
      "default" => date("Y-m-d")
    ]
  ]);
  $searchQuery = " checkindate BETWEEN '" . $Start_date . "' AND '" . $End_date . "'";

  //Start time filter
  $time_sel_start = filter_var($request->filter->time_sel_start, FILTER_VALIDATE_REGEXP, [
    'options' => [
      "regexp" => "/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/",
      "default" => 0
    ]
  ]);
  //End time filter
  $time_sel_end = filter_var($request->filter->time_sel_end, FILTER_VALIDATE_REGEXP, [
    'options' => [
      "regexp" => "/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/",
      "default" => 0
    ]
  ]);

  //Time filter
  if ($time_sel_start || $time_sel_end) {
    switch ($request->filter->time_sel) {
      case "Time_in":
        $searchQuery .= " AND timein BETWEEN '" . $time_sel_start . "' AND '" . $time_sel_end . "'";
        break;
      case "Time_out":
        $searchQuery .= " AND timeout BETWEEN '" . $time_sel_start . "' AND '" . $time_sel_end . "'";
        break;
      default:
    }
  }
  //Card filter
  $card_sel = filter_var(
    $request->filter->card_sel,
    FILTER_VALIDATE_REGEXP,
    [
      'options' => [
        'regexp' => '/\A[[:xdigit:]]{8,32}\z/', "default" => 0
      ]
    ]
  );
  if ($card_sel) {
    $searchQuery .= " AND card_uid='" . $card_sel . "'";
  }
  //Department filter
  $dev_uid = filter_var(
    $request->filter->dev_uid,
    FILTER_VALIDATE_REGEXP,
    [
      'options' => [
        'regexp' => '/\A[[:xdigit:]]{16}\z/', "default" => 0
      ]
    ]
  );
  if ($dev_uid) {
    $searchQuery .= " AND device_dep ='" . $dev_uid . "'";
  }
  $logs = getLogList($searchQuery);
  //adjust for timezone;
  $serverTimezone = new DateTimeZone('UTC');
  $newTimezone = new DateTimeZone($config["timezone"]);
  foreach ($logs as $logEntry) {
    //checkin
    $logEntry->timein = (new DateTime($logEntry->checkindate . ' ' . $logEntry->timein, $serverTimezone))
      ->setTimeZone($newTimezone)
      ->format('H:i:s');
    //checkout
    $logEntry->timeout = (new DateTime($logEntry->checkindate . ' ' . $logEntry->timeout, $serverTimezone))
      ->setTimeZone($newTimezone)
      ->format('H:i:s');
  }

  //adjust output format
  switch ($request->output) {
    case "csv":
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment; filename=User_Log' . $Start_date . '.csv');
      $out = fopen('php://output', 'w');
      fputcsv($out, [
        "ID",
        "Name",
        "Serial Number",
        "Card UID",
        "Device ID",
        "Device Dep",
        "Date log",
        "Time In",
        "Time Out"
      ], ";", "\"");
      foreach ($logs as $log) {
        fputcsv($out, $log->getData(), ";", "\"");
      }
      fclose($out);
      break;
    case "json":
    default:
      header('Content-Type: application/json');
      echo json_encode($logs);
  }
}
