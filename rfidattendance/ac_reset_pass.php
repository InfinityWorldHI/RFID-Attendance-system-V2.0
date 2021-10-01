<?php  

if (isset($_POST['reset'])) {
	
	$selector = $_POST['selector'];
	$validator = $_POST['validator'];
	$pwd = $_POST['pwd'];
	$pwd_re = $_POST['pwd_re'];

	if (empty($pwd) || empty($pwd_re)) {
		header("location: new_pass.php?error=emptypass");
		exit();
	}
	elseif ($pwd !== $pwd_re){
		header("location: new_pass.php?error=pwdnotsame");
		exit();
	}

	$currentDate = date("U");

	require 'connectDB.php';

	$sql = "SELECT * FROM pwd_reset WHERE pwd_reset_selector=? AND pwd_reset_expires>=?";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		header("location: new_pass.php?error=sqlerror");
		exit();
	}
	else{
		mysqli_stmt_bind_param($stmt, "ss", $selector, $currentDate);
		mysqli_stmt_execute($stmt);

		$result = mysqli_stmt_get_result($stmt);
		if (!$row = mysqli_fetch_assoc($result)) {
			header("location: new_pass.php?error=resubmit");
			exit();
		}
		else{
			$tokenBin = hex2bin($validator);
			$tokeCheck = password_verify($tokenBin, $row['pwd_reset_token']);
			if ($tokeCheck == false) {
				header("location: new_pass.php?error=resubmit");
				exit();
			}
			elseif ($tokeCheck == true) {

				$tokenEmail = $row['pwd_reset_email'];

				$sql = "SELECT * FROM admin WHERE admin_email=?";
				$stmt = mysqli_stmt_init($conn);
				if (!mysqli_stmt_prepare($stmt, $sql)) {
					header("location: new_pass.php?error=sqlerror");
					exit();
				}
				else{
					mysqli_stmt_bind_param($stmt, "s", $tokenEmail);
					mysqli_stmt_execute($stmt);

					$result = mysqli_stmt_get_result($stmt);
					if (!$row = mysqli_fetch_assoc($result)) {
						header("location: new_pass.php?error=nouser");
						exit();
					}
					else{
						$sql = "UPDATE admin SET admin_pwd=? WHERE admin_email=?";
						$stmt = mysqli_stmt_init($conn);
						if (!mysqli_stmt_prepare($stmt, $sql)) {
							header("location: new_pass.php?error=sqlerror");
							exit();
						}
						else{
							$newPwdHash = password_hash($pwd, PASSWORD_DEFAULT);
							mysqli_stmt_bind_param($stmt, "ss", $newPwdHash, $tokenEmail);
							mysqli_stmt_execute($stmt);

							$sql = "DELETE FROM pwd_reset WHERE pwd_reset_email=?";
							$stmt = mysqli_stmt_init($conn);
							if (!mysqli_stmt_prepare($stmt, $sql)) {
								header("location: login.php?error=sqlerror");
								exit();
							}
							else{
								mysqli_stmt_bind_param($stmt, "s", $tokenEmail);
								mysqli_stmt_execute($stmt);

								header("location: login.php?pwd=pwdUpd");
							}
						}
					}
				}
			}
		}
	}
}
else{
	header("location: index.php");
	exit();
}
?>