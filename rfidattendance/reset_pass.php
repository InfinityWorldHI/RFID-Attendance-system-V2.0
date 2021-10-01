<?php  

if (isset($_POST['reset_pass'])) {
	
	$selector = bin2hex(random_bytes(8));
	$token = random_bytes(32);

	$url = "https://domain-name.com/new_pass.php?selector=".$selector."&validator=".bin2hex($token);

	$expires = date("U") + 1800;

	require 'connectDB.php';

	$userEmail = $_POST['email'];

	$sql = "DELETE FROM pwd_reset WHERE pwd_reset_email=?";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		header("location: login.php?error=sqlerror1");
		exit();
	}
	else{
		mysqli_stmt_bind_param($stmt, "s", $userEmail);
		mysqli_stmt_execute($stmt);

		$sql = "SELECT * FROM admin WHERE admin_email=?";
		$result = mysqli_stmt_init($conn);
		if (!mysqli_stmt_prepare($result, $sql)) {
			header("location: login.php?error=sqlerror2");
  			exit();
		}
		else{
			mysqli_stmt_bind_param($result, "s", $userEmail);
			mysqli_stmt_execute($result);
			$resultl = mysqli_stmt_get_result($result);

			if ($row = mysqli_fetch_assoc($resultl)) {
				$sql = "INSERT INTO pwd_reset (pwd_reset_email, pwd_reset_selector, pwd_reset_token, pwd_reset_expires) VALUES(?, ?, ?, ?)";
		        $stmt = mysqli_stmt_init($conn);
		        if (!mysqli_stmt_prepare($stmt, $sql)) {
					header("location: login.php?error=sqlerror3");
					exit();
				}
				else{
					$hashedtoken = password_hash($token, PASSWORD_DEFAULT);
					mysqli_stmt_bind_param($stmt, "ssss", $userEmail, $selector, $hashedtoken, $expires);
					mysqli_stmt_execute($stmt);
				}
			}
			else{
				header("location: login.php?error=nouser");
				exit();
			}
		}	
	}

	mysqli_stmt_close($stmt);   
	mysqli_close($conn);

	$to = $userEmail;

	$subject = 'Reset your password!!';

	$message = '<p>We received a password reset request. The link to reset your password is below. if you did not make this request, you can ignore this email</p>';
	$message .= '<p>Here is your password reset link: </br>';
	$message .= '<a href="'.$url.'">'.$url.'</a></p>';

	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

	$headers .= "From: support@domain-name.com" . "\r\n";
	$headers .= 'Cc: support@domain-name.com' . "\r\n";

	// send email
	$success = mail($to, $subject, $message, $headers);
	if (!$success) {
	    $errorMessage = error_get_last()['message'];
	    header("location: login.php?reset=failed");
	}
	else{
	    header("location: login.php?reset=success");
	}
	
}
else{
	header("location: index.php");
	exit();
}
?>