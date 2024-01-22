<?php
session_start();

require_once('google-calendar-api.php');
$config = include 'config.php';
$cAPI = new GoogleCalendarApi($config["google"]["clientId"], $config["google"]["clientSecret"], $config["google"]);

// Google passes a parameter 'code' in the Redirect Url
if (isset($_GET['code'])) {
    try {
        // Get the access token 
        $data = $cAPI->GetAccessToken($_GET['code']);

        // Save the token for permanent use
        $config["google"] = $cAPI->getConfig();
        file_put_contents(
            "./config.php",
            "<?php\n\rreturn " . var_export($config, true) . ";\n?>"
        );

        // Redirect to the page where user can create event
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        echo $e->getMessage();
        exit();
    }
}
//Output Stage
$title = "Autorisieren von Google Calendar API";
ob_start();
?>
<h1><?=$title?></h1>
<section>
    <div class="container">
        <div class="row">
            <div class="form form-style-5 slideInDown animated">
                <a id="logo" class="btn btn-success" href="<?= $cAPI->getOAuthUrl() ?>">Login with Google</a>
            </div>
        </div>
    </div>
</section>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>