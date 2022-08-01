<?php
//Connect to database
require 'connectDB.php';
$d = date("Y-m-d");
$t = date("H:i:s");

$device_token = filter_input(INPUT_GET, "device_token",  FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/\A[[:xdigit:]]{16}\z/']]);
$card_uid = filter_input(INPUT_GET, "card_uid",  FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/\A[[:xdigit:]]{8,32}\z/']]);

//Check given Data
if ($card_uid && $device_token) {
    //search db for device
    $result = $conn->query("SELECT * FROM devices WHERE device_uid = '$device_token'");
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        //extract($row);
        $device_mode = $row['device_mode'];
        $device_dep = $row['device_dep'];
        //check device mode
        if ($device_mode == 1) {
            //search db for user
            $result = $conn->query("SELECT * FROM users WHERE card_uid = '$card_uid'");
            if ($result->num_rows == 1) {

                $row = mysqli_fetch_assoc($result);
                //*****************************************************
                //An existed Card has been detected for Login or Logout
                if ($row['add_card'] == 1) {
                    //in correct department or allowed for all ?
                    if ($row['device_uid'] == $device_uid || $row['device_uid'] == 0) {
                        $Uname = $row['username'];
                        $Number = $row['serialnumber'];

                        $result = $conn->query("SELECT * FROM users_logs WHERE card_uid='$card_uid' AND checkindate='$d' AND card_out=0");
                        if ($result->num_rows > 0) {
                            //*****************************************************
                            //Logout
                            if ($conn->query("UPDATE users_logs SET timeout='$t', card_out=1 WHERE card_uid='$card_uid' AND checkindate='$d' AND card_out=0") === TRUE) {
                                echo "logout" . $Uname;
                            } else {
                                echo "Error_SQL_insert_logout1";
                            }
                        } else {
                            $row = mysqli_fetch_assoc($result);
                            //*****************************************************
                            //Login
                            $result = $conn->query("INSERT INTO users_logs (username, serialnumber, card_uid, device_uid, device_dep, checkindate, timein, timeout) 
                                                    VALUES ('$Uname' ,'$Number', '$card_uid', '$device_uid', '$device_dep', '$d', '$t', '00:00:00')");
                            if ($result === TRUE) {
                                echo "login" . $Uname;
                            } else {
                                echo "Error_SQL_Select_login1";
                            }
                        }
                    } else {
                        echo "Error: Not Allowed!";
                    }
                } else { //if ($row['add_card'] == 0) {
                    echo "Error: Not registerd!";
                }
            } else {
                echo "Error: Not found!";
            }
        } else if ($device_mode == 0) {
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
            }
            if ($result->num_rows == 0) {
                //The Card is new
                $result = $conn->query("INSERT INTO users (card_uid, card_select, device_uid, device_dep, user_date) 
                                        VALUES ('$card_uid', 1, '$device_uid', '$device_dep', CURDATE())");
                echo "succesful";
            }
        } else {
            echo "Invalid Device!";
        }
    }
}
