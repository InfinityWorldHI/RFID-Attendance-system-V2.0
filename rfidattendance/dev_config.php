<?php 
session_start();
require('connectDB.php');

if (isset($_POST['dev_add'])) {

    $dev_name = $_POST['dev_name'];
    $dev_dep = $_POST['dev_dep'];

    if (empty($dev_name)) {
        echo '<p class="alert alert-danger">Please, Set the device name!!</p>';
    }
    elseif (empty($dev_dep)) {
        echo '<p class="alert alert-danger">Please, Set the device department!!</p>';
    }
    else{
        $token = random_bytes(8);
        $dev_token = bin2hex($token);

        $sql = "INSERT INTO devices (device_name, device_dep, device_uid, device_date) VALUES(?, ?, ?, CURDATE())";
        $result = mysqli_stmt_init($conn);
        if ( !mysqli_stmt_prepare($result, $sql)){
            echo '<p class="alert alert-danger">SQL Error</p>';
        }
        else{
            mysqli_stmt_bind_param($result, "sss", $dev_name, $dev_dep, $dev_token);
            mysqli_stmt_execute($result);
            echo 1;
        }
    mysqli_stmt_close($result); 
    mysqli_close($conn);
    }
}
elseif (isset($_POST['dev_del'])) {

    $dev_del = $_POST['dev_sel'];

    $sql = "DELETE FROM devices WHERE id=?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo '<p class="alert alert-danger">SQL Error</p>';
    }
    else{
        mysqli_stmt_bind_param($stmt, "i", $dev_del);
        mysqli_stmt_execute($stmt);
        echo 1;
        mysqli_stmt_close($stmt); 
        mysqli_close($conn);
    }
}
elseif (isset($_POST['dev_uid_up'])) {
    
    $dev_id = $_POST['dev_id_up'];

    $token = random_bytes(8);
    $dev_token = bin2hex($token);

    $sql = "UPDATE devices SET device_uid=? WHERE id=?";
    $result = mysqli_stmt_init($conn);
    if ( !mysqli_stmt_prepare($result, $sql)){
        echo '<p class="alert alert-danger">SQL Error</p>';
    }
    else{
        mysqli_stmt_bind_param($result, "si", $dev_token, $dev_id);
        mysqli_stmt_execute($result);
        echo 1;
    }
    mysqli_stmt_close($result); 
    mysqli_close($conn);
}
elseif (isset($_POST['update'])) {

    $useremail = $_SESSION['user-Email'];

    $up_name = $_POST['up_name'];
    $up_email = $_POST['up_email'];
    $up_password =$_POST['up_pwd'];

    if (empty($up_name) || empty($up_email)) {
        header("location: account.php?error=emptyfields");
        exit();
    }
    elseif (!filter_var($up_email,FILTER_VALIDATE_EMAIL) && !preg_match("/^[a-zA-Z 0-9]*$/", $up_name)) {
        header("location: account.php?error=invalidEN&UN=".$up_name);
        exit();
    }
    elseif (!filter_var($up_email,FILTER_VALIDATE_EMAIL)) {
        header("location: account.php?error=invalidEN&UN=".$up_name);
        exit();
    }
    elseif (!preg_match("/^[a-zA-Z 0-9]*$/", $up_name)) {
        header("location: account.php?error=invalidName&E=".$up_email);
        exit();
    }
    else{
        $sql = "SELECT * FROM users WHERE user_email=?";  
        $result = mysqli_stmt_init($conn);
        if ( !mysqli_stmt_prepare($result, $sql)){
            header("location: account.php?error=sqlerror1");
            exit();
        }
        else{
            mysqli_stmt_bind_param($result, "s", $useremail);
            mysqli_stmt_execute($result);
            $resultl = mysqli_stmt_get_result($result);
            if ($row = mysqli_fetch_assoc($resultl)) {
                $pwdCheck = password_verify($up_password, $row['user_pwd']);
                if ($pwdCheck == false) {
                    header("location: account.php?error=wrongpassword");
                    exit();
                }
                else if ($pwdCheck == true) {
                    if ($useremail == $up_email) {
                        $sql = "UPDATE users SET user_name=? WHERE user_email=?";
                        $stmt = mysqli_stmt_init($conn);
                        if (!mysqli_stmt_prepare($stmt, $sql)) {
                            header("location: account.php?error=sqlerror");
                            exit();
                        }
                        else{
                            mysqli_stmt_bind_param($stmt, "ss", $up_name, $useremail);
                            mysqli_stmt_execute($stmt);
                            $_SESSION['user-Name'] = $up_name;
                            header("location: account.php?success=updated");
                            exit();
                        }
                    }
                    else{
                        $sql = "SELECT user_email FROM users WHERE user_email=?";  
                        $result = mysqli_stmt_init($conn);
                        if ( !mysqli_stmt_prepare($result, $sql)){
                            header("location: account.php?error=sqlerror1");
                            exit();
                        }
                        else{
                            mysqli_stmt_bind_param($result, "s", $up_email);
                            mysqli_stmt_execute($result);
                            $resultl = mysqli_stmt_get_result($result);
                            if (!$row = mysqli_fetch_assoc($resultl)) {
                                $sql = "UPDATE users SET user_name=?, user_email=? WHERE user_email=?";
                                $stmt = mysqli_stmt_init($conn);
                                if (!mysqli_stmt_prepare($stmt, $sql)) {
                                    header("location: account.php?error=sqlerror");
                                    exit();
                                }
                                else{
                                    mysqli_stmt_bind_param($stmt, "sss", $up_name, $up_email, $useremail);
                                    mysqli_stmt_execute($stmt);
                                    $_SESSION['user-Name'] = $up_name;
                                    $_SESSION['user-Email'] = $up_email;
                                    header("location: account.php?success=updated");
                                    exit();
                                }
                            }
                            else{
                                header("location: account.php?error=nouser2");
                                exit();
                            }
                        }
                    }
                }
            }
            else{
                header("location: account.php?error=nouser1");
                exit();
            }
        }
    }
}
elseif (isset($_POST['dev_mode_set'])) {

    $dev_mode = $_POST['dev_mode'];
    $dev_id = $_POST['dev_id'];
    
    $sql = "UPDATE devices SET device_mode=? WHERE id=?";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo '<p class="alert alert-danger">SQL Error</p>';
    }
    else{
        mysqli_stmt_bind_param($stmt, "ii", $dev_mode, $dev_id);
        mysqli_stmt_execute($stmt);
        echo 1;
    }
}
else{
    header("location: index.php");
    exit();
}
//*********************************************************************************
?>