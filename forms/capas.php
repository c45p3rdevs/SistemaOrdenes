<?php
include '../php/conexion.php';
session_start();
if (isset($_SESSION['id'])) { //verificar que un usuario tiene una sesión activa en la plataforma
	include '../php/inactividad.php';
	expirar(); //verificar que hubo actividad en los ultimos 10 minutos
	include '../php/add.php';
?>
	<!DOCTYPE html>
	<html lang="es">

	<head>

		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>| Crear Mantenimiento |</title>
		<link rel="icon" type="image/png" href="../../img/icons/logo1.png" />

		<link rel="stylesheet" type="text/css" href="http://cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css">
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
		<link href="../css/style_nav.css" rel="stylesheet">
		<link href="../css/style_ini.css" rel="stylesheet">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/fontawesome.min.css" integrity="sha384-NvKbDTEnL+A8F/AA5Tc5kmMLSJHUO868P+lDtTpJIeQdGYaUIuLr4lVGOEA1OcMy" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css"> 
	</head>

	<body>
		<div class="loader"></div>
		<div class="web-page">
			<div class="content">
				<h2>Lista de Manteniemientos a mostrar</h2>
				<a href="capas-add.php" class="btn btn-primary btn-lg"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>Agregar un Mantenimiento </a>
				<div class="text-right mb-2">
				<a href="fpdf/PruebaV.php" target="_blank" class="btn btn-light"><i class="fa fa-file-pdf"></i>Generar Reporte</a>
				</div>
                 
				<style>
					.btn-light {
						background-color: #c50d66;
						color: white;
						
					}
				</style>
				<hr />

				<?php
				
				//eliminar un registro
				if (isset($_GET['del']) && ($_GET['del']) == 'y') {
					$nik = $con->real_escape_string($_GET["nik"]); //guardar valor del id recibido
					$sql1 = $con->prepare("SELECT * FROM capas WHERE id=?"); //consultar la existencia de una capa con el id recibido
					$sql1->bind_param('i', $nik);
					$sql1->execute();
					$result = $sql1->get_result();

					if ($result->num_rows == 0) {
						echo '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Error, no se encontraron datos.</div>';
					} else {
						try {
							mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);// para activar reportar error en secuencias simples (query)
							$con->autocommit(FALSE); //preparar una transaccion
							$delete = $con->prepare("DELETE FROM capas WHERE id=?"); //eliminar la capa con el id recibido
							$delete->bind_param('i', $nik);
							$delete->execute();
							//guardar los valores eliminados para el historial
							$row = $result->fetch_assoc();
							$ubicacion = $row['ubicacion'];
							$titulo = $row['titulo'];
							$fecha = date("Y-m-d");
							date_default_timezone_set("America/Bogota"); //ajustar horario de reloj
							$hora = date('h:i A');
							$nom = $_SESSION['name'];
							$recu = getNrec($con, $row['rec']);
							$depa = getNdep($con, $row['dep']);
							//agregar acción de eliminar capa al historial de acciones de usuarios
							$con->query("INSERT INTO `historial`(`usuario`, `accion`, `valor`, `fecha`, `hora`) VALUES ('$nom','Eliminó una capa del recurso $recu del departamento $depa','- $ubicacion<br>- $titulo','$fecha','$hora')");
							$con->commit();
							$sql1->close();
							$delete->close();
							//mensaje de éxito								
							echo '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> Datos eliminados correctamente.</div>';
						} catch (Exception $e) {
							$con->rollback();
							//mensaje de error
							echo '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> Error, no se pudo eliminar los datos.</div>';

						}
	
					}
				}
				?>

				<div class="table-responsive">
					<table class="table table-striped">
						<thead>
							<tr>
								<th>Ubicación / Dependencia</th>
								<th>Nombre del Técnico</th>
								<th>Datos del Equipo</th>
								<th>Nombre del Resguardante</th>
								<th>Departamento</th>
								<th>Servicio</th>
								<th>Acciones</th>
							</tr>
						</thead>
						<?php
						$sql2 = $con->query("SELECT * FROM capas ORDER BY id ASC"); //consultar la tabla de capas
						if ($sql2->num_rows == 0) {
							echo '<tr><td colspan="8">No hay datos.</td></tr>';
						} else {
							//agregar información a la tabla
							while ($row = $sql2->fetch_assoc()) {
								echo '
						<tr>
							
							<td>' . $row['ubicacion'] . '</td>
							<td>' . $row['titulo'] . '</td>
							';
								echo '<td>' . $row['ttl_lynd'] . '</td>';
								echo '<td>' . $row['url_archivo'] . '</td>';

								echo '<td>' . getNdep($con, $row['dep']) . '</td>';

								echo '<td>' . getNrec($con, $row['rec']) . '</td>';

								echo '
							<td>

								<a href="capas-edit.php?nik=' . $row['id'] . '" title="Editar datos" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a>
								<button value="' . $row['id'] . ';;' . $row['titulo'] . '" title="Eliminar" class="btn btn-danger btn-sm"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>
							</td>
							</tr>';
							}
						}
						$sql2->close();
						?>
					</table>
				</div>

				<br>



			</div>
		</div>
		<?php include 'nav.php'; ?>

		<script type="text/javascript">
			$(document).ready(function() {
				elementos(); // cargar funcionalidades del menu lateral
				$(".btn-danger").click(function() {
					let texto = $(this).val().split(";;");
					//confirmar el eliminar una capa
					Swal.fire({
						title: 'Esta seguro?',
						html: "<h4>Esto eliminará la capa '" + texto[1] + "' y no es reversible!</h4>",
						icon: 'warning',
						showCancelButton: true,
						confirmButtonColor: '#3085d6',
						cancelButtonColor: '#d33',
						confirmButtonText: 'Si, eliminar!',
						cancelButtonText: 'No, cancelar!'

					}).then((result) => {
						if (result.isConfirmed) {
							//realizar operacion de eliminar registro							
							window.location = "capas.php?del=y&nik=" + texto[0];
						}
					})
				});
				//aplicar plugin datatables a la tabla para filtrar, paginar y ordenarla
				$(".table").DataTable({
					"pagingType": "simple_numbers",
					"autoWidth": false,
					"ordering": false

				});
			});
		</script>


	</body>

	</html>
<?php

} else {
	//si no hay una sesión activa en la plataforma,redirige al formulario de login
	header("location:../index.php");
}
?>