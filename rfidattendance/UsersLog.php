<?php
include "connectDB.php";

if (!isset($_SESSION['Admin-name'])) {
  header("location: login.php");
}


//<script src="js/user_log.js"></script>

//Output Stage
$title = "Zeiten-Log";
$css_extra = "css/userslog.css";
ob_start();
?>
<!-- Log filter -->
<div class="modal fade bd-example-modal-lg" id="Filter-export" tabindex="-1" role="dialog" aria-labelledby="Filter/Export" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg animate" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="exampleModalLongTitle">Filter auswählen:</h3>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" action="Export_Excel.php" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="container-fluid">
            <div class="row">
              <div class="col-lg-6 col-sm-6">
                <div class="panel panel-primary">
                  <div class="panel-heading">Filter By Date:</div>
                  <div class="panel-body">
                    <label for="Start-Date"><b>Select from this Date:</b></label>
                    <input type="date" name="date_sel_start" id="date_sel_start">
                    <label for="End -Date"><b>To End of this Date:</b></label>
                    <input type="date" name="date_sel_end" id="date_sel_end">
                  </div>
                </div>
              </div>
              <div class="col-lg-6 col-sm-6">
                <div class="panel panel-primary">
                  <div class="panel-heading">
                    Filter By:
                    <div class="time">
                      <input type="radio" id="radio-one" name="time_sel" class="time_sel" value="Time_in" checked />
                      <label for="radio-one">Time-in</label>
                      <input type="radio" id="radio-two" name="time_sel" class="time_sel" value="Time_out" />
                      <label for="radio-two">Time-out</label>
                    </div>
                  </div>
                  <div class="panel-body">
                    <label for="Start-Time"><b>Select from this Time:</b></label>
                    <input type="time" name="time_sel_start" id="time_sel_start">
                    <label for="End -Time"><b>To End of this Time:</b></label>
                    <input type="time" name="time_sel_end" id="time_sel_end">
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-lg-4 col-sm-12">
                <label for="Fingerprint"><b>Filter By User:</b></label>
                <select class="card_sel" name="card_sel" id="card_sel">
                  <option value="0">All Users</option>
                  <?php
                  /** @var UserObject */
                  foreach (getAllActiveUsers() as $User) { ?>
                    <option value="<?= $User->card_uid; ?>"><?= $User->username; ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-lg-4 col-sm-12">
                <label for="Device"><b>Filter By Device department:</b></label>
                <select class="dev_sel" name="dev_sel" id="dev_sel">
                  <option value="0">All Departments</option>
                  <?php
                  foreach (getAllDepartments() as $department) {
                  ?>
                    <option value="<?= $department ?>"><?= $department ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" name="user_log" id="user_log" data-bs-dismiss="modal" class="btn btn-success">Anwenden</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- //Log filter -->
<section class="container py-lg-5">
  <!--User table-->
  <h1>Zeiten-Aufzeichnungen</h1>
  <div>
    <div class="table-responsive" style="max-height: 500px;">
      <table class="table" id="records">
        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Serial Number</th>
            <th>Card UID</th>
            <th>Device Dep</th>
            <th>Date</th>
            <th>Rein</th>
            <th>Raus</th>
          </tr>
        </thead>
        <tbody id="users_log">
        </tbody>
      </table>
      <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#Filter-export">Filter</button>
      </div>
    </div>
  </div>
  <link href="//cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css" rel="stylesheet" />
  <script src="//cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
  <script>
    new DataTable('#records', {
      layout: {
        topStart: {
          buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
        }
      }
    });

    function loadLogs(excel = false) {
      let logFilter = {
        log_date: 1,
        date_sel_start: $('#date_sel_start').val(),
        date_sel_end: $('#date_sel_end').val(),
        time_sel: $(".time_sel:checked").val(),
        time_sel_start: $('#time_sel_start').val(),
        time_sel_end: $('#time_sel_end').val(),
        card_sel: $('#card_sel option:selected').val(),
        dev_uid: $('#dev_sel option:selected').val(),
      };
      $.ajax({
        type: 'POST',
        url: "log_list.php",
        dataType: (excel ? 'text' : 'json'),
        data: JSON.stringify({
          filter: logFilter,
          output: (excel ? "csv" : "json")
        }),
        success: (data_logs, textStatus, request) => {
          if (excel) {
            date = $('#date_sel_start').val();
            date = date ? date : (new Date().toISOString().slice(0, 10));
            save("report_" + date + ".csv", data_logs);
          } else {
            $("#users_log").loadTemplate("template/log_table.html", data_logs);
          }
        },
        statusCode: {
          503: function() {
            window.location.href = "login.php";
          }
        }
      });

    };
    $().ready(() => {
      loadLogs(false);
      setInterval(() => {
        loadLogs();
      }, 5000);
      $("#user_log").click(() => {
        loadLogs(false);
      });
      $("#excel_export").click(() => {
        loadLogs(true);
      });
    });
  </script>
</section>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>