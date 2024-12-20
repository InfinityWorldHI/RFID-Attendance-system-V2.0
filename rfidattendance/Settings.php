<?php
include "connectDB.php";

if (!isset($_SESSION['Admin-name'])) {
    header("location: login.php");
}

$Admin = getAdminByEmail($_SESSION["Admin-email"]);

if (isset($_POST["update"])) {
    if (password_verify($_POST['admin_pwd'], $Admin->admin_pwd) === true) {
        $neues_pw = filter_input(INPUT_POST, "admin_pwd_new", FILTER_VALIDATE_REGEXP, ["options" => ["regexp" => "/.{6,25}/"]]);
        $admin_name =  filter_input(INPUT_POST, "admin_name", FILTER_VALIDATE_REGEXP, ['options' => ["regexp" => "/^[a-zA-Z 0-9]*$/"]]);
        if (!empty($neues_pw)) {
            $Admin->admin_pwd = password_hash($neues_pw, PASSWORD_BCRYPT);
        }
        if(!empty($admin_name)){
            $Admin->admin_name = $admin_name;
        }
        if(!$Admin->save()){
            $messages[] = ["mode" => "danger", "message" => "Fehler beim Speichern"];    
            // var_dump(mysqli_error($conn));
        }
        
        $messages[] = ["mode" => "success", "message" => "Gespeichert!"];
    }else{
        $messages[] = ["mode" => "success", "message" => "Passwort falsch"];
    }
}

//Output Stage
$title = "Einstellungen";
ob_start();
?>
<h1>Deine Benutzerdaten</h1>
<section>
    <div class="container">
        <div class="row">
            <div class="form form-style-5 slideInDown animated">
                <!-- Account Update -->
                <form method="POST" enctype="multipart/form-data">
                    <?php if(isset($messages) && count($messages)) foreach ($messages as $entry) {
                        echo "<div class=\"alert alert-" . $entry['mode'] . "\">" . $entry['message'] . "</div>";
                    } ?>
                    <fieldset>
                        <div class="form-group">
                            <label for="User-mail"><b>Admin Name:</b></label>
                            <input class="form-control" type="text" name="admin_name" placeholder="Name..." value="<?= $Admin->admin_name; ?>" required />
                        </div>
                        <div class="form-group">
                            <label for="User-mail"><b>Admin E-mail:</b></label>
                            <input class="form-control" type="email" name="admin_email" placeholder="E-Mail..." value="<?= $Admin->admin_email; ?>" required />
                        </div>
                        <div class="form-group">
                            <label for="User-psw"><b>Altes Password</b></label>
                            <input class="form-control" type="password" name="admin_pwd" placeholder="Aktuelles Passwort..." required />
                        </div>
                        <div class="form-group">
                            <label for="User-psw"><b>Password</b></label>
                            <input class="form-control" type="password" name="admin_pwd_new" placeholder="Neues Passwort..." />
                        </div>
                        <div class="form-group">
                            <a href="./google-login.php" class="form-control btn btn-success">Google Konto autorisieren</a>
                        </div>                        
                        <div class="form-group">
                            <button type="submit" name="update" class="btn btn-success">Speichern</button>
                        </div>
                    </fieldset>
                </form>
                <!-- //Account Update -->
            </div>
        </div>
    </div>
</section>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>