<?php
session_start();
if (isset($_SESSION['Admin-name'])) {
  header("location: index.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Log In</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <script src="js/jquery-2.2.3.min.js"></script>
    <script>
      $(window).on("load resize ", function() {
          var scrollWidth = $('.tbl-content').width() - $('.tbl-content table').width();
          $('.tbl-header').css({'padding-right':scrollWidth});
      }).resize();
    </script>
    <script type="text/javascript">
      $(document).ready(function(){
        $(document).on('click', '.message', function(){
          $('form').animate({height: "toggle", opacity: "toggle"}, "slow");
          $('h1').animate({height: "toggle", opacity: "toggle"}, "slow");
        });
      });
    </script>
</head>
<body>
<?php include'header.php'; ?> 
<main>
  <h1 class="slideInDown animated">Please, Login with the Admin E-mail and Password</h1>
  <h1 class="slideInDown animated" id="reset">Please, Enter your Email to send the reset password link</h1>
<!-- Log In -->
<section>
  <div class="slideInDown animated">
    <div class="login-page">
      <div class="form">
        <?php  
          if (isset($_GET['error'])) {
            if ($_GET['error'] == "invalidEmail") {
                echo '<div class="alert alert-danger">
                        This E-mail is invalid!!
                      </div>';
            }
            elseif ($_GET['error'] == "sqlerror") {
                echo '<div class="alert alert-danger">
                        There a database error!!
                      </div>';
            }
            elseif ($_GET['error'] == "wrongpassword") {
                echo '<div class="alert alert-danger">
                        Wrong password!!
                      </div>';
            }
            elseif ($_GET['error'] == "nouser") {
                echo '<div class="alert alert-danger">
                        This E-mail does not exist!!
                      </div>';
            }
          }
          if (isset($_GET['reset'])) {
            if ($_GET['reset'] == "success") {
                echo '<div class="alert alert-success">
                        Check your E-mail!
                      </div>';
            }
          }
          if (isset($_GET['account'])) {
            if ($_GET['account'] == "activated") {
                echo '<div class="alert alert-success">
                        Please Login
                      </div>';
            }
          }
          if (isset($_GET['active'])) {
            if ($_GET['active'] == "success") {
                echo '<div class="alert alert-success">
                        The activation like has been sent!
                      </div>';
            }
          }
        ?>
        <div class="alert1"></div>
        <form class="reset-form" action="reset_pass.php" method="post" enctype="multipart/form-data">
          <input type="email" name="email" placeholder="E-mail..." required/>
          <button type="submit" name="reset_pass">Reset</button>
          <p class="message"><a href="#">LogIn</a></p>
        </form>
        <form class="login-form" action="ac_login.php" method="post" enctype="multipart/form-data">
          <input type="email" name="email" id="email" placeholder="E-mail..." required/>
          <input type="password" name="pwd" id="pwd" placeholder="Password" required/>
          <button type="submit" name="login" id="login">login</button>
          <p class="message">Forgot your Password? <a href="#">Reset your password</a></p>
        </form>
      </div>
    </div>
  </div>
</section>
</main>
</body>
</html>