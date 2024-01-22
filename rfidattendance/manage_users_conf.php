<?php
//Connect to database
require 'connectDB.php';

if (!isset($_SESSION['Admin-name'])) {
  $error = new stdClass();
  $error->code=1;
  $error->message="not logged in";
  http_response_code(503);
  echo json_encode($error);
  exit;
}

if(isset($_POST)){
    $request = json_decode(file_get_contents('php://input'));
    switch($request->method){
        case "select":
            $user = selectUserByCardId($request->data->card_id);
            if(!is_null($user)){
                echo json_encode($user);
                return;
            }
            break;
        case "update":
            $user = getUserById($request->data->id);
            if(!is_null($user)){
                foreach(get_object_vars($request->data) as $key=>$value){
                    if(property_exists($user,$key)){
                        $user->$key=$value;
                    }
                }
                if($user->save()){
                    $response = new stdClass();
                    $response->result = "success";
                    echo json_encode($response);
                    return;
                }
            }
            break;
        case "remove":
            if(deleteUser($request->data->id)){
                $response = new stdClass();
                $response->result = "success";
                echo json_encode($response);
                return;
            }
            break;
        case "add":
            $User = getUserById($request->data->id);
            if(!is_null($User)){
                foreach(get_object_vars($request->data) as $key=>$value){
                    if(property_exists($User,$key)){
                        $User->$key=$value;
                    }
                }
                $User->add_card = 1;
                if($User->save()){
                    $response = new stdClass();
                    $response->result = "success";
                    echo json_encode($response);
                    return;
                }
            }
            break;
            break;
        default:
    }
    echo "false";
}