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
?>
<script type="module" src="https://unpkg.com/esp-web-tools@9.0.3/dist/web/install-button.js?module"></script>
<section class="container py-lg-5">
	<!--User table-->
	<h1>Leser hinzufügen/bearbeiten entfernen</h1>
	<div class="table-responsive-sm">

		<!-- New Devices -->
		<div class="modal fade" id="new-device" tabindex="-1" role="dialog" aria-labelledby="Neuer Leser" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h3 class="modal-title">Leser:</h3>
						<button type="button" class="btn close" data-dismiss="modal" aria-label="Close">
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
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
					</div>
				</div>
			</div>
		</div>
		<!-- //New Devices -->

		<!-- devices -->
		<table class="table" id="devices_list">
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
			<tbody>
			</tbody>
		</table>
		<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#new-device" style="font-size: 18px; float: right; margin-top: -6px;">Neuer Leser</button>
		<esp-web-install-button manifest="/firmware/manifest.json">
			<button slot="activate" class="btn btn-success">Installieren/Aktualisieren</button>
			<span slot="unsupported">Mit deinem Browser ist das leider nicht möglich - Verwenden Sie Microsoft Edge/Google Chrome auf einem PC</span>
			<span slot="not-allowed">Diese Installation ist nur über HTTPS:// möglich - Prüfen Sie die Adresszeile</span>
		</esp-web-install-button>
		<!-- \\devices -->new-device
		<script>
			let table = null;

			function addDevice() {
				$.ajax({
					url: 'dev_config.php',
					type: 'POST',
					dataType: 'json',
					data: JSON.stringify({
						method: ($.isNumeric($('#dev_id').val()) ? "update" : "add"),
						data: {
							id: $('#dev_id').val(),
							device_name: $('#dev_name').val(),
							device_dep: $('#dev_dep').val(),
						}
					}),
					success: function(response) {
						$('#dev_name').val('');
						$('#dev_dep').val('');
						table.ajax.reload();
						$("#new-device").modal("hide");
					}
				});
			}

			function editDevice(id) {
				let devices = table.rows().data().toArray();
				device = devices.find(device => device.id == id);
				$('#dev_id').val(device.id);
				$('#dev_name').val(device.device_name);
				$('#dev_dep').val(device.device_dep);;
				$('#new-device').modal('show');
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
						table.ajax.reload();
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
						table.ajax.reload();
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
						table.ajax.reload();
					}
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

				//draw the table
				table = new DataTable('#devices_list', {
					layout: {
						topStart: {
							buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5']
						}
					},
					processing: true,
					ajax: {
						url: 'device_list.php',
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
							data: 'device_name'
						},
						{
							data: 'device_dep'
						},
						{
							data: 'device_uid'
						},
						{
							data: 'device_date'
						},
						{
							data: 'device_mode'
						},
						{
							data: 'id',
						}
					],
					columnDefs: [{
							targets: 4,
							render: function(data, type, row, meta) {
								var isChecked = data === 'true' || data === true || data === 1 || data === "1"; // Überprüfen, ob true
								return `<input type="checkbox" class="toggle-switch" data-id="${row["id"]}" 
                                    ${isChecked ? 'checked' : ''} 
                                    data-toggle="toggle" data-size="sm">`;
							}
						},
						{
							targets: 2,
							render: function(data, type, row, meta) {
								return `<button type="button" class="dev_up_token btn btn-warning" title="Update this device Token" data-id="${row['id']}">
            								<span class="fa fa-refresh"></span>
        								</button>
        								${data}`;
							}
						},
						{
							targets: 5,
							render: function(data, type, row, meta) {
								return `
								<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#new-device" onClick="editDevice(${data})" title="Edit this device"><span class="fa fa-pencil"></span></button>
								<button type="button" class="btn btn-danger" onClick="deleteDevice(${data})" title="Delete this device"><span class="fa fa-trash"></span></button>`;
							}
						}
					],
					drawCallback: function(row, data) {
						$(".toggle-switch").bootstrapToggle({
							on: 'Zeiterfassung',
							off: 'Registrierung',
							onstyle: "mode_select_read",
							offstyle: "mode_select_learn"
						});
					}
				});
				$('#devices_list').on('change', '.toggle-switch', function() {
					var readerId = $(this).data('id'); // Hole die ID des Lesers
					var isActive = $(this).prop('checked'); // Zustand des Toggles (true/false)

					updateDevice(readerId, {
						device_mode: isActive
					});

					// Führe deine Funktion aus
					console.log(`Leser ${readerId} Betriebsmodus geändert zu: ${isActive}`);
				});
			});
		</script>
	</div>
</section>
<?php
$html = ob_get_clean();
include "template/index.phtml"; ?>