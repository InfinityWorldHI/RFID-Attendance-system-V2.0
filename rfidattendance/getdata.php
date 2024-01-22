<?php
//Connect to database
require 'connectDB.php';
include "./google-calendar-api.php";
$cAPI = new GoogleCalendarApi($config["google"]["clientId"], $config["google"]["clientSecret"], $config["google"]);
$d = date("Y-m-d");
$t = date("H:i:s");

$device_uid = filter_input(INPUT_GET, "device_token",  FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/\A[[:xdigit:]]{16}\z/', 'default' => 0]]);
$card_uid = filter_input(INPUT_GET, "card_uid",  FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/\A[[:xdigit:]]{8,32}\z/', 'default' => 0]]);

function error(string $message)
{
    http_response_code(503);
    echo $message;
    exit;
}

//Check given Data
if (!$card_uid || !$device_uid) {
    error("Error: Ungueltige Anfrage");
}
//search db for device
$device = getDeviceByToken($device_uid);
if (is_null($device)) {
    error("Error: Gerät nicht gefunden");
}

//check device mode
switch ($device->device_mode) {
    case DeviceObject::DEVICE_MODE_TIME: //CheckinOut
        //search db for user
        $user = getUserByCardId($card_uid);
        if (is_null($user)) {
            error("Error: Nutzer nicht gefunden!");
        }
        //*****************************************************
        //An existed Card has been detected for Login or Logout
        if ($user->add_card != 1) {
            error("Error: Nicht registriert!");
        }
        //in correct department or allowed for all ?
        if ($user->device_dep == $device->device_dep || $user->device_dep == 'All') {
            //Already Checked in ?
            $Log = getLogByCheckinDate($d, $user->card_uid);
            if (!is_null($Log)) {
                //*****************************************************
                //Logout
                if (!empty($Log->calendarEventId)) {
                    // Update event on primary calendar
                    $cAPI->UpdateCalendarEvent(
                        $Log->calendarEventId,
                        $user->calendarId,
                        $user->username . " Arbeitszeit",
                        false,
                        [
                            "start_time" => (new DateTime($Log->checkindate . " " . $Log->timein))->format(\DateTime::RFC3339),
                            "end_time" => (new DateTime())->format(\DateTime::RFC3339)
                        ],
                        $config["timezone"]
                    );
                }
                $Log->timeout = $t;
                $Log->card_out = 1;
                if ($Log->save()) {
                    echo "logout" . $user->username;
                } else {
                    error("Error: SQL Checkout Fehler");
                }
            } else {
                //*****************************************************
                //Login
                if (!empty($user->calendarId)) {
                    $eventId = $cAPI->CreateCalendarEvent(
                        $user->calendarId,
                        $user->username . "Arbeitszeit",
                        false,
                        false,
                        false,
                        [
                            "start_time" => (new DateTime())->format(\DateTime::RFC3339),
                            "end_time" => (new DateTime())->modify("+5 minutes")->format(\DateTime::RFC3339)
                        ],
                        $config["timezone"]
                    );
                }
                $Log = new UserLogObject([
                    "username" => $user->username,
                    "serialnumber" => $user->serialnumber,
                    "card_uid" => $user->card_uid,
                    "device_uid" => $device->device_uid,
                    "device_dep" => $device->device_dep,
                    "checkindate" => $d,
                    "timein" => $t,
                    "timeout" => 0,
                    "calendarEventId" => $eventId ?? null
                ], $conn);

                if ($Log->insert()) {
                    echo "login" . $user->username;
                } else {
                    error("Error: SQL Checkin Fehler");
                }
            }
        } else {
            error("Error: Hier nicht erlaubt");
        }
        break;
    case DeviceObject::DEVICE_MODE_LEARN: //Learn
        unselectUsers();

        //New Card should be been added if needed so search for it
        if (selectUserByCardId($card_uid)) {
            echo "available";
        } else {
            //The Card is new
            $User = new UserObject([
                "card_uid" => $card_uid,
                "card_select" => 1,
                "device_uid" => $device->device_uid,
                "device_dep" => $device->device_dep,
                "user_date" => $d
            ], $conn);
            if ($User->insert()) {
                echo "successful";
            }
        }
        break;
    default:
        error("Error: Unbekannter Modus");
}

if ($cAPI->tokenUpdated) {
    $config["google"] = $cAPI->getConfig();
    file_put_contents(
        "./config.php",
        "<?php\n\rreturn " . var_export($config, true) . ";\n?>"
    );
}
