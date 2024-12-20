<?php
include "connectDB.php";

if (!isset($_SESSION['Admin-name'])) {
	header("location: login.php");
}

$all_users = getAllActiveUsers();

//Output Stage
$title = "Nutzer Verwalten";
$css_extra = "css/Manage_Users.css";
ob_start();
// <script src="js/manage_users.js"></script>
?>
<section class="container py-lg-5">
	<h1>Füge Nutzer hinzu oder ändere Sie</h1>
	<!-- New User -->
	<div class="modal fade" id="new-user" tabindex="-1" role="dialog" aria-labelledby="Neuer Nutzer" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<form enctype="multipart/form-data" id="user_form">
				<div class="modal-content">
					<div class="modal-header">
						<h3 class="modal-title">Nutzer:</h3>
						<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<fieldset>
							<legend class="h6"><span class="badge badge-primary">1</span> Infos</legend>
							<div class="form-group">
								<input type="hidden" name="user_id" id="user_id" value="">
								<label for="user_username">Nutzername</label>
								<input type="text" class="form-control" name="user_username" id="user_username" placeholder="Nutzername">
							</div>
							<div class="form-group">
								<label for="user_serialnumber">Laufende Nummer</label>
								<input type="number" class="form-control" name="user_serialnumber" id="user_serialnumber" placeholder="Laufende Nummer">
							</div>
							<div class="form-group">
								<label for="user_email">E-Mail</label>
								<input type="email" class="form-control" name="user_email" id="user_email" placeholder="E-Mail...">
							</div>
						</fieldset>
						<fieldset>
							<legend class="h6"><span class="badge badge-secondary">2</span> Erweitert</legend>
							<div class="form-group">
								<label for="user_device_dep"><b>Nutzer Abteilung</b></label>
								<select class="form-control" name="user_device_dep" id="user_device_dep">
									<option value="All">Alle</option>
									<?php
									foreach (getAllDepartments() as $department) {
										echo "					<option value=\"" . $department . "\">" . $department . "</option>\r\n";
									}
									?>
								</select>
							</div>
							<div class="form-group">
								<label for="calendarId"><b>Nutzer Kalender</b></label>
								<select class="form-control" name="calendarId" id="calendarId">
									<option value="">Kein Kalender</option>
									<?php
									include "google-calendar-api.php";
									$cAPI = new GoogleCalendarApi($config["google"]["clientId"], $config["google"]["clientSecret"], $config["google"]);
									$calendarsAll = $cAPI->GetCalendarsList();
									foreach ($calendarsAll as $calendar) {
										echo "					<option value=\"" . $calendar["id"] . "\">" . $calendar["summary"] . "</option>\r\n";
									}
									if ($cAPI->tokenUpdated) {
										$config["google"] = $cAPI->getConfig();
										file_put_contents(
											"./config.php",
											"<?php\n\rreturn " . var_export($config, true) . ";\n?>"
										);
									}
									?>
								</select>
							</div>
							<div class="form-group">
								<label><b>Geschlecht</b></label>
								<div class="form-check">
									<input type="radio" class="form-check-input" name="user_gender" id="gender_female" value="Female">
									<label class="form-check-label" for="gender_female">Frau</label>
								</div>
								<div class="form-check">
									<input type="radio" class="form-check-input" name="user_gender" id="gender_male" value="Male">
									<label class="form-check-label" for="gender_male">Mann</label>
								</div>
							</div>
						</fieldset>

					</div>
					<div class="modal-footer">
						<button type="button" name="user_add" data-dismiss="modal" class="user_add btn btn-warning">Nutzer Hinzufügen</button>
						<button type="button" name="user_upd" data-dismiss="modal" class="user_upd btn btn-success">Nutzer Speichern</button>
						<button type="button" name="user_rmo" data-dismiss="modal" class="user_rmo btn btn-danger">Nutzer Entfernen</button>
					</div>
				</div>
			</form>
		</div>
	</div>
	<!-- //New User -->
	<!--User table-->
	<div class="table-responsive-sm">
		<table class="table" id="manage_users">
			<thead class="table-primary">
				<tr>
					<th>Card UID</th>
					<th>Name</th>
					<th>Gender</th>
					<th>S.No</th>
					<th>Date</th>
					<th>Department</th>
					<th>Aktion</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<script>
		let table = null;

		function selectUser(id) {
			$.ajax({
				type: 'POST',
				url: "manage_users_conf.php",
				dataType: 'json',
				data: JSON.stringify({
					method: "select",
					data: {
						id: id
					}
				}),
				success: (data) => {
					$("#new-user").modal('show');
					loadUser(data);
					table.ajax.reload();
				},
				statusCode: {
					503: function() {
						window.location.href = "login.php";
					}
				}
			});
		}

		function loadUser(data) {
			$('#user_id').val(data.id);
			$("#user_username").val(data.username);
			$("#user_serialnumber").val(data.serialnumber);
			$("#user_email").val(data.email);
			$("#user_device_dep").val(data.device_dep).change();
			$("#calendarId").val(data.calendarId).change();
			$("input:radio[name=user_gender]").val([data.gender]).change();
		}

		function saveUser() {
			let user_data = {
				id: $('#user_id').val(),
				username: $("#user_username").val(),
				serialnumber: $("#user_serialnumber").val(),
				email: $("#user_email").val(),
				device_dep: $("#user_device_dep").val(),
				calendarId: $("#calendarId").val(),
				gender: $('input[name=user_gender]:checked', '#user_form').val()
			};
			$.ajax({
				type: 'POST',
				url: "manage_users_conf.php",
				dataType: 'json',
				data: JSON.stringify({
					method: "update",
					data: user_data
				}),
				success: (data) => {
					table.ajax.reload();
				},
				statusCode: {
					503: function() {
						window.location.href = "login.php";
					}
				}
			});
		}

		function addUser() {
			let user_data = {
				id: $('#user_id').val(),
				username: $("#user_username").val(),
				serialnumber: $("#user_serialnumber").val(),
				email: $("#user_email").val(),
				device_dep: $("#user_device_dep").val(),
				calendarId: $("#calendarId").val(),
				gender: $('input[name=user_gender]:checked', '#user_form').val()
			};
			$.ajax({
				type: 'POST',
				url: "manage_users_conf.php",
				dataType: 'json',
				data: JSON.stringify({
					method: "add",
					data: user_data
				}),
				success: (data) => {
					table.ajax.reload();
				},
				statusCode: {
					503: function() {
						window.location.href = "login.php";
					}
				}
			});
		}

		function removeUser() {
			if (confirm("Nutzer wirklich löschen ?")) {
				$.ajax({
					type: 'POST',
					url: "manage_users_conf.php",
					dataType: 'json',
					data: JSON.stringify({
						method: "remove",
						data: {
							id: $('#user_id').val(),
						}
					}),
					success: (data) => {
						table.ajax.reload();
					},
					statusCode: {
						503: function() {
							window.location.href = "login.php";
						}
					}
				});
			}
		}

		$("button[name=user_add]").click(() => {
			addUser();
		});
		$("button[name=user_upd]").click(() => {
			saveUser();
		});
		$("button[name=user_rmo]").click(() => {
			removeUser();
		});

		// if ($("").children().length == 0 || force) {
		// 	let selected_user = data_users.find((user) => {
		// 		return user.card_select;
		// 	});
		// 	if (selected_user != undefined) {
		// 		selectUser(selected_user.card_uid);
		// 	}
		// }
		$().ready(() => {
			table = new DataTable('#manage_users', {
				layout: {
					topStart: {
						buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
					}
				},
				processing: true,
				ajax: {
					url: 'user_list.php',
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
						data: 'card_uid'
					},
					{
						data: 'username'
					},
					{
						data: 'gender'
					},
					{
						data: 'serialnumber'
					},
					{
						data: 'user_date'
					},
					{
						data: 'device_dep'
					},
					{
						data: 'id'
					},
				],
				columnDefs: [{
						targets: 0,
						render: function(data, type, row, meta) {
							if (data == 1) {
								return `<span><i class='fa fa-check' title='The selected UID'></i></span>${data}`;
							}
							return data
						}
					},
					{
						targets: 6,
						render: function(data, type, row, meta) {
							return `<button type="button" class="btn btn-success select_btn" onClick="selectUser('${data}');" title="select this UID"><span class="fa fa-pencil"></span> Bearbeiten</button>`;
						}
					},
				]
			});
			setInterval(() => {
				table.ajax.reload();
			}, 5000);
		});
	</script>
</section>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>