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

<section class="my-5">
  <h2>Nutzerliste</h2>
  <!--User table-->
  <div class="table-responsive">
    <table id="user_list">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th> Name</th>
          <th>Serial Number</th>
          <th>Gender</th>
          <th>Card UID</th>
          <th>Date</th>
          <th>Device</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
</section>

<script>
  $(document).ready(function() {
    let table = new DataTable('#user_list', {
      layout: {
        topStart: {
          // buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
        }
      },
      ajax: 'user_list.php',
      columns: [{
          data: 'id'
        },
        {
          data: 'username'
        },
        {
          data: 'serialnumber'
        },
        {
          data: 'gender'
        },
        {
          data: 'card_uid'
        },
        {
          data: 'user_date'
        },
        {
          data: 'device_dep'
        }
      ]
    });
  });
</script>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>