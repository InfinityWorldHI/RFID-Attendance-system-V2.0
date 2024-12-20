<?php 
session_start();
require('connectDB.php');

if (isset($_POST['update'])) {

    $useremail = $_SESSION['Admin-email'];

    $up_name = $_POST['up_name'];
    $up_email = $_POST['up_email'];
    $up_password =$_POST['up_pwd'];

    if (empty($up_name) || empty($up_email)) {
        header("location: index.php?error=emptyfields");
        exit();
    }
    elseif (!filter_var($up_email,FILTER_VALIDATE_EMAIL) && !preg_match("/^[a-zA-Z 0-9]*$/", $up_name)) {
        header("location: index.php?error=invalidEN&UN=".$up_name);
        exit();
    }
    elseif (!filter_var($up_email,FILTER_VALIDATE_EMAIL)) {
        header("location: index.php?error=invalidEN&UN=".$up_name);
        exit();
    }
    elseif (!preg_match("/^[a-zA-Z 0-9]*$/", $up_name)) {
        header("location: index.php?error=invalidName&E=".$up_email);
        exit();
    }
    else{
        $sql = "SELECT * FROM admin WHERE admin_email=?";  
        $result = mysqli_stmt_init($conn);
        if ( !mysqli_stmt_prepare($result, $sql)){
            header("location: index.php?error=sqlerror1");
            exit();
        }
        else{
            mysqli_stmt_bind_param($result, "s", $useremail);
            mysqli_stmt_execute($result);
            $resultl = mysqli_stmt_get_result($result);
            if ($row = mysqli_fetch_assoc($resultl)) {
                $pwdCheck = password_verify($up_password, $row['admin_pwd']);
                if ($pwdCheck == false) {
                    header("location: index.php?error=wrongpasswordup");
                    exit();
                }
                else if ($pwdCheck == true) {
                    if ($useremail == $up_email) {
                        $sql = "UPDATE admin SET admin_name=? WHERE admin_email=?";
                        $stmt = mysqli_stmt_init($conn);
                        if (!mysqli_stmt_prepare($stmt, $sql)) {
                            header("location: index.php?error=sqlerror");
                            exit();
                        }
                        else{
                            mysqli_stmt_bind_param($stmt, "ss", $up_name, $useremail);
                            mysqli_stmt_execute($stmt);
                            $_SESSION['Admin-name'] = $up_name;
                            header("location: index.php?success=updated");
                            exit();
                        }
                    }
                    else{
                        $sql = "SELECT admin_email FROM admin WHERE admin_email=?";  
                        $result = mysqli_stmt_init($conn);
                        if ( !mysqli_stmt_prepare($result, $sql)){
                            header("location: index.php?error=sqlerror1");
                            exit();
                        }
                        else{
                            mysqli_stmt_bind_param($result, "s", $up_email);
                            mysqli_stmt_execute($result);
                            $resultl = mysqli_stmt_get_result($result);
                            if (!$row = mysqli_fetch_assoc($resultl)) {
                                $sql = "UPDATE admin SET admin_name=?, admin_email=? WHERE admin_email=?";
                                $stmt = mysqli_stmt_init($conn);
                                if (!mysqli_stmt_prepare($stmt, $sql)) {
                                    header("location: index.php?error=sqlerror");
                                    exit();
                                }
                                else{
                                    mysqli_stmt_bind_param($stmt, "sss", $up_name, $up_email, $useremail);
                                    mysqli_stmt_execute($stmt);
                                    $_SESSION['Admin-name'] = $up_name;
                                    $_SESSION['Admin-email'] = $up_email;
                                    header("location: index.php?success=updated");
                                    exit();
                                }
                            }
                            else{
                                header("location: index.php?error=nouser2");
                                exit();
                            }
                        }
                    }
                }
            }
            else{
                header("location: index.php?error=nouser1");
                exit();
            }
        }
    }
}
else{
    header("location: index.php");
    exit();
}
//*********************************************************************************
?>