<?php
include "connectDB.php";

if (!isset($_SESSION['Admin-name'])) {
  header("location: login.php");
}

$all_users = getAllActiveUsers();

//Output Stage
$title = "Benutzer";
$css_extra = "css/Users.css";
ob_start();
?>
<section>
  <h1>Nutzerliste</h1>
  <!--User table-->
  <div class="table-responsive" style="max-height: 400px;">
    <table class="table">
      <thead class="table-primary">
        <tr>
          <th>ID | Name</th>
          <th>Serial Number</th>
          <th>Gender</th>
          <th>Card UID</th>
          <th>Date</th>
          <th>Device</th>
        </tr>
      </thead>
      <tbody id="user_list">
      </tbody>
    </table>
  </div>
</section>
<script>
  $().ready(() => {
    $.getJSON("user_list.php", (data_users) => {
      $("#user_list").loadTemplate("template/user_table.html", data_users);
    });
  });
</script>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>