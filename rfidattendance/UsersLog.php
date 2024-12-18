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
<section class="container py-lg-5">
  <!--User table-->
  <h1>Zeiten-Aufzeichnungen</h1>
  <div>
    <div class="table-responsive">
      <p>
        <a class="btn btn-primary" data-toggle="collapse" href="#Filter-export" role="button" aria-expanded="false" aria-controls="collapseExample">
          <i class="fa-solid fa-filter"></i> Filter
        </a>
      </p>
      <div class="collapse" id="Filter-export">
        <div class="card card-body">
          <form method="POST" id="filter-form" action="#" enctype="multipart/form-data">
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
              <button type="submit" name="user_log" id="user_log" data-dismiss="modal" class="btn btn-success">
                <i class="fa-solid fa-filter"></i>Anwenden
              </button>
            </div>
          </form>
        </div>
      </div>

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
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <script>
    let table = new DataTable('#records', {
      layout: {
        topStart: {
          buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
        }
      },
      processing: true,
      ajax: {
        url: 'log_list.php',
        type: 'POST',
        data: function(d) {
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
          return JSON.stringify({
            filter: logFilter,
            output: "json"
          });
        }
      },
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
          data: 'card_uid'
        },
        {
          data: 'device_dep'
        },
        {
          data: 'checkindate'
        },
        {
          data: 'timein'
        },
        {
          data: 'timeout'
        },
      ]
    });
    $('#filter-form').on('submit', function(e) {
      e.preventDefault(); // Verhindert das Standard-Formular-Submit
      table.ajax.reload(); // Daten neu laden
    });
  </script>
</section>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>