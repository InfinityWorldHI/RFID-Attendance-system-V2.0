<?php
//Connect to database
require 'connectDB.php';
$d = date("Y-m-d");
$t = date("H:i:s");

$device_token = filter_input(INPUT_GET, "device_token",  FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/\A[[:xdigit:]]{16}\z/', 'default' => 0]]);
$card_uid = filter_input(INPUT_GET, "card_uid",  FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/\A[[:xdigit:]]{8,32}\z/', 'default' => 0]]);

//Check given Data
if (!$card_uid || !$device_token) {
    echo "Error: equest_malformed";
    exit;
}
//search db for device
$result = $conn->query("SELECT * FROM devices WHERE device_uid = '$device_token'");
if ($result->num_rows != 1) {
    echo "Error: Device not found";
    exit;
}

$row = $result->fetch_assoc();
$device_dep = $row['device_dep'];

//check device mode
switch ($row['device_mode']) {
    case 1: //CheckinOut
        //search db for user
        $result = $conn->query("SELECT * FROM users WHERE card_uid = '$card_uid'");
        if ($result->num_rows != 1) {
            echo "Error: user_not_found!";
            exit;
        }
        $row = mysqli_fetch_assoc($result);
        //*****************************************************
        //An existed Card has been detected for Login or Logout
        if ($row['add_card'] != 1) {
            echo "Error: not registered!";
            exit;
        }
        //in correct department or allowed for all ?
        if ($row['device_dep'] == $device_dep || $row['device_dep'] == 'All') {
            $user_name = $row['username'];
            $user_serial = $row['serialnumber'];
            
            //Already Checked in ?
            $result = $conn->query("SELECT * FROM users_logs WHERE card_uid='$card_uid' AND checkindate='$d' AND card_out=0");
            if ($result->num_rows > 0) {
                //*****************************************************
                //Logout
                if ($conn->query("UPDATE users_logs SET timeout='$t', card_out=1 WHERE card_uid='$card_uid' AND checkindate='$d' AND card_out=0") === TRUE) {
                    echo "logout" . $user_name;
                } else {
                    echo "Error: SQL insert logout";
                }
            } else {
                $row = mysqli_fetch_assoc($result);
                //*****************************************************
                //Login
                $result = $conn->query("INSERT INTO users_logs (username, serialnumber, card_uid, device_uid, device_dep, checkindate, timein, timeout) 
                                        VALUES ('$user_name' ,'$user_serial', '$card_uid', '$device_uid', '$device_dep', '$d', '$t', '00:00:00')");
                if ($result === TRUE) {
                    echo "login" . $user_name;
                } else {
                    echo "Error: SQL Select login";
                }
            }
        } else {
            echo "Error: Not allowed here!";
            exit;
        }
        break;
    case 0: //Learn
        $conn->query("UPDATE users SET card_select=0 WHERE card_select=1"); //deselect previous

        //New Card should be been added if needed so search for it
        $result = $conn->query("SELECT * FROM users WHERE card_uid='$card_uid'");
        if ($result->num_rows == 1) {
            $row = mysqli_fetch_assoc($result);
            //card exists so select it for ui
            $result = $conn->query("UPDATE users SET card_select=1 WHERE card_uid='$card_uid'");
            if ($result->num_rows == 1) {
                echo "available";
            }
        } else if ($result->num_rows == 0) {
            //The Card is new
            $result = $conn->query("INSERT INTO users (card_uid, card_select, device_uid, device_dep, user_date) 
                                            VALUES ('$card_uid', 1, '$device_uid', '$device_dep', CURDATE())");
            echo "successful";
        }
        break;
    default:
        echo "Error: Unknown device mode";
        exit;
}
