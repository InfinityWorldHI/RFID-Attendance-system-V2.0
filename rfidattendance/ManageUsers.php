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
<h1>Füge Nutzer hinzu oder ändere Sie</h1>
<div class="form-style-5 slideInDown animated">
	<form enctype="multipart/form-data" id="user_form">
		<div class="alert_user"></div>
		<fieldset>
			<legend><span class="number">1</span> Infos</legend>
			<input type="hidden" name="user_id" id="user_id">
			<input type="text" name="user_username" id="user_username" placeholder="Nutzername">
			<input type="number" name="user_serialnumber" id="user_serialnumber" placeholder="Laufende Nummer">
			<input type="email" name="user_email" id="user_email" placeholder="E-Mail...">
		</fieldset>
		<fieldset>
			<legend><span class="number">2</span> Erweitert</legend>
			<label>
				<label for="Device"><b>Nutzer Abteilung:</b></label>
				<select class="dev_sel" name="user_device_dep" id="user_device_dep" style="color: #000;">
					<option value="All">Alle Abteilungen</option>
					<?php
					foreach (getAllDepartments() as $department) {
						echo "					<option value=\"" . $department . "\">" . $department . "</option>\r\n";
					}
					?>
				</select>
				<label for="calendarId"><b>Nutzer Calendar:</b></label>
				<select class="dev_sel" name="calendarId" id="calendarId" style="color: #000;">
					<option value="">Kein Kalendar</option>
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
				<input type="radio" name="user_gender" class="gender" value="Female">Frau
				<input type="radio" name="user_gender" class="gender" value="Male">Mann
			</label>
		</fieldset>
		<button type="button" name="user_add" class="user_add">Nutzer Hinzufügen</button>
		<button type="button" name="user_upd" class="user_upd">Nutzer Speichern</button>
		<button type="button" name="user_rmo" class="user_rmo">Nutzer Entfernen</button>
	</form>
</div>
<!--User table-->
<div class="section">
	<div>
		<div class="table-responsive-sm" style="max-height: 870px;">
			<table class="table">
				<thead class="table-primary">
					<tr>
						<th>Card UID</th>
						<th>Name</th>
						<th>Gender</th>
						<th>S.No</th>
						<th>Date</th>
						<th>Department</th>
					</tr>
				</thead>
				<tbody id="manage_users"></tbody>
			</table>
		</div>
	</div>
</div>
</div>
<script>
	function selectUser(card_id) {
		$.ajax({
			type: 'POST',
			url: "manage_users_conf.php",
			dataType: 'json',
			data: JSON.stringify({
				method: "select",
				data: {
					card_id: card_id
				}
			}),
			success: (data) => {
				loadUser(data);
				loadUsers();
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
				loadUsers();
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
				loadUsers();
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
					loadUsers();
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

	function loadUsers(force = false) {
		$.getJSON("user_list.php", (data_users) => {
			if ($("#manage_users").children().length == 0 || force) {
				let selected_user = data_users.find((user) => {
					return user.card_select;
				});
				if (selected_user != undefined) {
					selectUser(selected_user.card_uid);
				}
			}
			$("#manage_users").loadTemplate("template/user_table_manage.html", data_users);
		});
	}
	$().ready(() => {
		$.addTemplateFormatter({
			selectIcon: function(value, template) {
				if (value == 1) {
					return "<span><i class='fa fa-check' title='The selected UID'></i></span>";
				}
				return "";
			},
			editButton: function(value, template) {
				return '<button type="button" class="select_btn" onClick="selectUser(\'' + value + '\');" title="select this UID">' + value + '</button>';
			}
		});
		loadUsers(true);
		setInterval(() => {
			loadUsers();
		}, 5000);
	});
</script>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>