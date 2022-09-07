<?php
include "connectDB.php";

$token = filter_input(
  INPUT_GET,
  "token",
  FILTER_VALIDATE_REGEXP,
  [
    'options' => [
      'regexp' => '/\A[[:xdigit:]]{64}\z/',
      "default" => 0
    ]
  ]
);

if (isset($_POST)) {
  $email = filter_input(INPUT_POST, "admin_email", FILTER_VALIDATE_EMAIL);
  $name =  filter_input(INPUT_POST, "admin_name", FILTER_VALIDATE_REGEXP, ['options' => ["regexp" => "/^[a-zA-Z 0-9]*$/"]]);

  if (isset($_POST["reset_pass"])) {
    $Admin = getAdminByEmail($email);
    if (!is_null($Admin)) {
      $Admin->admin_passwd_reset_token = bin2hex(random_bytes(32));
      $url = "https://" . $_SERVER["SERVER_NAME"] . "/PasswordReset.php?token=" . $Admin->admin_passwd_reset_token;
      $Admin->admin_passwd_reset_timeout = (new DateTime())->modify("+3 hours")->format("Y-m-d H:i:s");

      $subject = 'Passwort zurückgesetzt';
      $message = '<p>Du wolltest dein Passwort zurücksetzen. Den Link dazu findest du unterhalb dieser Nachricht. Wenn du diese Anfrage nicht gestellt hast, kannst du die Nachricht ignorieren</p>';
      $message .= '<p>Hier ist der zurücksetzen Link: </br>';
      $message .= '<a href="' . $url . '">Zurücksetzen</a></p>';

      $headers = "MIME-Version: 1.0" . "\r\n";
      $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
      $headers .= "From: " . $Admin->admin_email . "\r\n";

      // send email
      $success = mail($Admin->admin_email, $subject, $message, $headers);
      if (!$success) {
        $errorMessage = error_get_last()['message'];
        $messages[] = ["mode" => "danger", "message" => "E-Mail konnte nicht gesendet werden"];
      } else if (!$Admin->save()) {
        $messages[] = ["mode" => "danger", "message" => "konnte Änderungen nicht speichern"];
        var_dump(mysqli_error($conn));
      }else{
        $messages[] = ["mode" => "success", "message" => "Link wurde versand"];
      }
    }
  } else if (isset($_POST["reset"])) {
    $Admin = getAdminByToken($token);
    if (!is_null($Admin) && $token == $Admin->admin_passwd_reset_token) {
      //check for account having this else unset
      $pw_new = filter_input(INPUT_POST, "admin_pwd_new", FILTER_VALIDATE_REGEXP, ["options" => ["regexp" => "/.{6,25}/"]]);
      $pw_repeat = filter_input(INPUT_POST, "admin_pwd_repeat", FILTER_VALIDATE_REGEXP, ["options" => ["regexp" => "/.{6,25}/"]]);
      if ($pw_new == $pw_repeat && strtotime($Admin->admin_passwd_reset_timeout) >= strtotime(date("Y-m-d H:i:s"))) {
        $Admin->admin_pwd = password_hash($pw_repeat, PASSWORD_BCRYPT);
        $Admin->admin_passwd_reset_token = "";
        $Admin->admin_passwd_reset_timeout = "0000-00-00 00:00:00";
        if ($Admin->save()) {
          $messages[] = ["mode" => "success", "message" => "Token korrekt, Passwort wurde geändert!"];
        } else {
          $messages[] = ["mode" => "danger", "message" => "konnte Änderungen nicht speichern"];
        }
      } else {
        $messages[] = ["mode" => "danger", "message" => "Passwort stimmt nicht überein oder Token abgelaufen"];
      }
    }else{
      $messages[] = ["mode" => "danger", "message" => "Token ungültig"];
    }
  }
}

//Output Stage
$title = "Passwort zurücksetzen";
ob_start();
?>
<?php if ($token) : ?>
  <h1>Please, Insert your new Password</h1>
<?php else : ?>
  <h1>Please, Enter your Email to send the reset password link</h1>
<?php endif; ?>

<section class="container">
  <div>
    <div class="login-page">
      <div class="form">
        <?php if (isset($messages) && count($messages)) foreach ($messages as $entry) {
          echo "<div class=\"alert alert-" . $entry['mode'] . "\">" . $entry['message'] . "</div>";
        }
        ?>
        <?php if ($token) : ?>
          <form class="login-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="selector" value="<?= $selector ?>">
            <input type="hidden" name="validator" value="<?= $validator ?>">
            <input type="password" name="admin_pwd_new" placeholder="Enter a new Password..." required />
            <input type="password" name="admin_pwd_repeat" placeholder="Repeat new Password..." required />
            <button type="submit" name="reset">Reset Password</button>
          </form> <?php else : ?>
          <form class="reset-form" method="post" enctype="multipart/form-data">
            <input type="email" name="admin_email" placeholder="E-mail..." required />
            <button type="submit" name="reset_pass">Reset</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>