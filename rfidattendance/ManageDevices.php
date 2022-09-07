<?php
include "connectDB.php";

if (!isset($_SESSION['Admin-name'])) {
	header("location: login.php");
}

$all_users = getAllActiveUsers();

//Output Stage
$title = "Leser Verwalten";
$css_extra = "css/devices.css";
ob_start();
// <script src="js/manage_users.js"></script>
?>
<h1 class="slideInDown animated">Leser hinzufügen/bearbeiten entfernen</h1>
<!-- New Devices -->
<div class="modal fade" id="new-device" tabindex="-1" role="dialog" aria-labelledby="Neuer Leser" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title">Leser:</h3>
				<button type="button" class="btn close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<input type="hidden" name="dev_id" id="dev_id" /><br>
				<label for="User-mail"><b>Name:</b></label>
				<input type="text" name="dev_name" id="dev_name" placeholder="Name..." required /><br>
				<label for="User-mail"><b>Abteilung:</b></label>
				<input type="text" name="dev_dep" id="dev_dep" placeholder="Abteilung..." required /><br>
			</div>
			<div class="modal-footer">
				<button type="button" name="dev_save" id="dev_save" class="btn btn-success">Speichern</button>
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
			</div>
		</div>
	</div>
</div>
<!-- //New Devices -->
<section class="container py-lg-5">
	<div class="alert_dev"></div>
	<!-- devices -->
	<div class="row">
		<div class="col-lg-12 mt-4">
			<div class="panel">
				<div class="panel-heading" style="font-size: 19px;">Deine Geräte:</div>
				<div class="panel-body">
					<div>
						<div class="table-responsive">
							<table class="table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Abteilung</th>
										<th>Token</th>
										<th>Datum</th>
										<th>Modus</th>
										<th>Einstellung</th>
									</tr>
								</thead>
								<tbody id="devices_list">
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#new-device" style="font-size: 18px; float: right; margin-top: -6px;">Neuer Leser</button>
			</div>
		</div>
		<!-- \\devices -->
</section>

<script>
	let devices = [];
	function addDevice() {
		$.ajax({
			url: 'dev_config.php',
			type: 'POST',
			dataType: 'json',
			data: JSON.stringify({
				method: ($.isNumeric($('#dev_id').val())?"update":"add"),
				data: {
					id: $('#dev_id').val(),
					device_name: $('#dev_name').val(),
					device_dep: $('#dev_dep').val(),
				}
			}),
			success: function(response) {
				$('#dev_name').val('');
				$('#dev_dep').val('');
				loadDevices();
				$("#new-device").modal("hide");
			}
		});
	}

	function editDevice(id){
		device = devices.find(device => device.id==id);
		$('#dev_id').val(device.id);
		$('#dev_name').val(device.device_name);
		$('#dev_dep').val(device.device_dep);;
	}

	function updateDevice(id, data) {
		$.ajax({
			url: 'dev_config.php',
			type: 'POST',
			dataType: 'json',
			data: JSON.stringify({
				method: "update",
				data: {
					...{
						id: id
					},
					...data,
				}
			}),
			success: function(response) {
				$('#dev_id').val('');
				$('#dev_name').val('');
				$('#dev_dep').val('');
				loadDevices();
			}
		});
	}

	function updateDeviceToken(id) {
		$.ajax({
			url: 'dev_config.php',
			type: 'POST',
			dataType: 'json',
			data: JSON.stringify({
				method: "token",
				data: {
					id: id
				}
			}),
			success: function(response) {
				loadDevices();
			}
		});
	}

	function deleteDevice(id) {
		if (!confirm("Möchtest du das Gerät wirklich löschen?")) {
			return;
		}
		$.ajax({
			url: 'dev_config.php',
			type: 'POST',
			dataType: 'json',
			data: JSON.stringify({
				method: "remove",
				data: {
					id: id,
				}
			}),
			success: function(response) {
				loadDevices();
			}
		});
	}

	function loadDevices(force = false) {
		$.getJSON("device_list.php", (data_devices) => {
			if ($("#devices_list").children().length == 0 || force) {
				let selected_user = data_devices.find((user) => {
					return user.card_select;
				});
				if (selected_user != undefined) {
					selectUser(selected_user.card_uid);
				}
			}
			$("#devices_list").loadTemplate("template/device_table_manage.html", data_devices, {
				complete: () => {
					devices = data_devices;
					$('.mode_sel').bootstrapToggle({
						on: 'Zeiterfassung',
      					off: 'Registrierung',
						onstyle: "mode_select_read",
						offstyle: "mode_select_learn"
					});
				}
			});
		});
	}
	$().ready(() => {
		//Save Device 
		$("#dev_save").click(addDevice);
		//Device Token update
		$(document).on('click', '.dev_up_token', (event) => {
			if (confirm("Möchtest du wirklich einen neuen Token erzeugen?")) {
				let id = $(event.currentTarget).data("id");
				updateDeviceToken(id);
			}
		});

		//Device Mode
		$(document).on('change', '.mode_sel', (event) => {
			if (confirm("Möchtest du wirklich den Modus umschalten?")) {
				let id = $(event.currentTarget).data("id");
				let device_mode = $(event.currentTarget).prop('checked');
				updateDevice(id, {
					device_mode: device_mode //($('input[name="mode_' + id + '"]:checked').val() == 1)
				});
			}
		});

		let modeSelectId = -1;
		$.addTemplateFormatter({
			modeSelect: function(value, template) {
				switch (modeSelectId) {
					case -1:
						modeSelectId = value;
						return value;
					default:
						let html = "<input type=\"checkbox\" class=\"mode_sel\" data-id=\"" + modeSelectId + "\" data-toggle=\"toggle\" " + (value==1 ? "checked" : "") + ">";
						modeSelectId = -1;
						return html;
				}
			},
			editButtons: function(value, template) {
				return "<button type=\"button\" class=\"btn btn-success\" data-bs-toggle=\"modal\" data-bs-target=\"#new-device\" onClick=\"editDevice('" + value + "')\" title=\"Edit this device\"><span class=\"fa fa-pencil\"></span></button>" +
					   "<button type=\"button\" class=\"btn btn-danger\" onClick=\"deleteDevice('" + value + "')\" title=\"Delete this device\"><span class=\"fa fa-trash\"></span></button>";
			}
		});
		loadDevices(true);
		setInterval(() => {
			loadDevices();
		}, 5000);
	});
</script>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>