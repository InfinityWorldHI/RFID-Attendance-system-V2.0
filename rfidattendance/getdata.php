<?php
//Connect to database
require 'connectDB.php';
$d = date("Y-m-d");
$t = date("H:i:s");

$device_token = filter_input(INPUT_GET, "device_token",  FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/\A[[:xdigit:]]{16}\z/', 'default' => 0]]);
$card_uid = filter_input(INPUT_GET, "card_uid",  FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/\A[[:xdigit:]]{8,32}\z/', 'default' => 0]]);

function error(string $message){
    http_response_code(503);
    echo $message;
    exit;
}

//Check given Data
if (!$card_uid || !$device_token) {
    error("Error: equest_malformed");
}
//search db for device
$device = getDeviceByToken($device_token);
if (is_null($device)) {
    error("Error: Device not found");
}

//check device mode
switch ($device->device_mode) {
    case DeviceObject::DEVICE_MODE_TIME: //CheckinOut
        //search db for user
        $user = getUserByCardId($card_uid);
        if (is_null($user)) {
            error("Error: user_not_found!");
        }
        //*****************************************************
        //An existed Card has been detected for Login or Logout
        if ($user->add_card != 1) {
            error("Error: not registered!");
        }
        //in correct department or allowed for all ?
        if ($user->device_dep == $device->device_dep || $user->device_dep == 'All') {
            //Already Checked in ?
            $Log = getLogByCheckinDate($d, $user->card_uid);
            if (!is_null($Log)) {
                //*****************************************************
                //Logout
                $Log->timeout = $t;
                $Log->card_out = 1;
                if ($Log->save()) {
                    echo "logout" . $user->username;
                } else {
                    error("Error: SQL insert logout");
                }
            } else {
                //*****************************************************
                //Login
                $Log = new UserLogObject([
                    "username" => $user->username,
                    "serialnumber" => $user->serialnumber,
                    "card_uid" => $user->card_uid,
                    "device_uid" => $device->device_uid,
                    "device_dep" => $device->device_dep,
                    "checkindate" => $d,
                    "timein" => $t,
                    "timeout" => 0
                ], $conn);

                if ($Log->insert()) {
                    echo "login" . $user->username;
                } else {
                    error("Error: SQL Select login");
                }
            }
        } else {
            error("Error: Not allowed here!");
        }
        break;
    case DeviceObject::DEVICE_MODE_LEARN: //Learn
        unselectUsers();

        //New Card should be been added if needed so search for it
        if(selectUserByCardId($card_uid)){
            echo "available";
        }else{
            //The Card is new
            $User = new UserObject([
                "card_uid" => $card_uid,
                "card_select" => 1,
                "device_uid" => $device->device_token,
                "device_dep" => $device->device_dep,
                "user_date" => $d
            ], $conn);
            if($User->insert()){
                echo "successful";
            }
        }
        break;
    default:
        error("Error: Unknown device mode");
}
