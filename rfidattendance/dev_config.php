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
    $data = $request->data;
    switch ($request->method) {
        case "add":
            $array = [];
            foreach (get_object_vars($data) as $key => $value) {
                $array[$key] = $value;
            }
            $array["device_uid"] = bin2hex(random_bytes(8));
            $array["device_date"] = date("Y-m-d");
            $array["device_mode"] = DeviceObject::DEVICE_MODE_LEARN;
            $device = new DeviceObject($array, $conn);
            if ($device->insert()) {
                $response = new stdClass();
                $response->result = "success";
                echo json_encode($response);
                return;
            }
            break;
        case "update":
            $device = getDeviceById($request->data->id);
            if (!is_null($device)) {
                foreach (get_object_vars($data) as $key => $value) {
                    if (property_exists($device, $key)) {
                        $device->$key = $value;
                    }
                }
                if ($device->save()) {
                    $response = new stdClass();
                    $response->result = "success";
                    echo json_encode($response);
                    return;
                }
            }
            break;
        case "token":
            $device = getDeviceById($request->data->id);
            if (!is_null($device)) {
                $device->device_uid = bin2hex(random_bytes(8));
                if ($device->save()) {
                    $response = new stdClass();
                    $response->result = "success";
                    echo json_encode($response);
                    return;
                }
            }
            break;
        case "remove":
            if(deleteDevice($request->data->id)){
                $response = new stdClass();
                $response->result = "success";
                echo json_encode($response);
                return;
            }
            break;
            break;
        default:
    }
    echo "false";
}
