<?php
session_start();
require 'connectDB.php';
$messages = [];

if (isset($_POST['login'])) {

  $Admin_EMail = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
  $Admin = getAdminByEmail($Admin_EMail);
  if (
    !is_null($Admin) && 
    password_verify($_POST['pwd'], $Admin->admin_pwd) === true
  ) {
    $_SESSION['Admin-name'] = $Admin->admin_name;
    $_SESSION['Admin-email'] = $Admin->admin_email;
    header("location: index.php");
  } else {
    $messages[] = ["mode" => "danger", "message" => "zugangsdaten ungültig"];
  }
}

//Output Stage
$title = "Login";
$css_extra = "css/Users.css";
ob_start();
?>
<h1 class="">Bitte einloggen</h1>

<!-- Log In -->
<section>
  <div>
    <div class="login-page">
      <div class="form">
        <?php if(isset($messages) && count($messages)) foreach ($messages as $entry) {
          echo "<div class=\"alert alert-" . $entry['mode'] . "\">" . $entry['message'] . "</div>";
        }?>
        <form class="login-form" method="post" enctype="multipart/form-data">
          <input type="email" name="email" id="email" placeholder="E-Mail..." required />
          <input type="password" name="pwd" id="pwd" placeholder="Password" required />
          <button type="submit" name="login" id="login">login</button>
          <p class="message">Passwort vergessen? <a href="PasswordReset.php">Passwort zurücksetzen</a></p>
        </form>
      </div>
    </div>
  </div>
</section>

<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>