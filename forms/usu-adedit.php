<?php
include '../php/conexion.php';

session_start();
if (isset($_SESSION['id'])) { //verificar que un usuario tiene una sesión activa en la plataforma
	include '../php/encriptar.php';
	include '../php/inactividad.php';

	expirar(); //verificar que hubo actividad en los ultimos 10 minutos
	include '../php/edit.php';
?>
	<!DOCTYPE html>
	<html lang="es">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Agregar usuario</title>
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
		<link href="../css/style_nav.css" rel="stylesheet">
		<link href="../css/style_ini.css" rel="stylesheet">
		<link rel="icon" type="image/png" href="../../img/icons/logo1.png" />

	</head>

	<body>
		<div class="loader"></div>
		<div class="web-page">
			<div class="content">


				<?php
				/* 
				si se recibe un valor de id el formulario cargará opciones para agregar un usuario,
				de lo contrario, el formulario cargará opciones para agregar un recurso,
				
				*/
				if (isset($_GET["nik"])) {
					$cuenta = $_SESSION['id'];



					if ($cuenta != $_GET["nik"]) {
				?>
						<script type="text/javascript">
							//mostrar error si se ingresa un id no correspondiente a la sesión iniciada
							alert("Error, No corresponde a su cuenta de usuario");
						</script>
					<?php
						header("Location: usu-adedit.php?nik=" . $cuenta);
					}

					?>
					<script type="text/javascript">
						//cambiar titulo de la pagina
						document.title = "Editar perfil";
					</script>
					<?php

					$nik = $con->real_escape_string($_GET["nik"]); //guardar valor del id recibido				
					$sql = $con->prepare("SELECT * FROM usuarios WHERE id=?");  //consultar la existencia de un usuario con el id recibido
					$sql->bind_param('i', $nik);
					$sql->execute();
					$result = $sql->get_result();




					?>
					<h2>Editar Perfil</h2>
					<hr />
					<?php


					if ($result->num_rows == 0) {
					?>
						<script type="text/javascript">
							alert("Error, No corresponde a algun valor");
							window.location.href = "inicio.php";
						</script>
					<?php

					} else {
						$row = $result->fetch_assoc();
					}
				} else {

					?> <h2>Usuarios &raquo; Agregar usuario</h2>
					<hr />
				<?php

				}

				//si se recibe un dato de editar, para actualizar la información de un usuario
				if (isset($_POST['save'])) {
					try {
						//nuevos valores de usuario recibidos					
						$usuario = $con->real_escape_string($_POST["usuario"]); //Escapar caracteres especiales 
						$nombres = $con->real_escape_string($_POST["nombres"]); //Escapar caracteres especiales 
						$apellidos = $con->real_escape_string($_POST["apellidos"]); //Escapar caracteres especiales 
						$contrasena = $encriptar($con->real_escape_string($_POST["contrasena"])); //Escapar caracteres especiales 

						//generar una excepcion si se intenta agregar una cuenta con el nombre administrador
						if ($nuevos['usuario'] == 'admin' && $antiguos['usuario'] != 'admin') {
							throw new Exception();                
						//generar una excepcion si se intenta modificar el nombre del usuario administrador
						}else if($antiguos['usuario'] == 'admin' && $nuevos['usuario'] != 'admin'){
							throw new Exception();
						}

						//actualizar usuario según su id
						$update = $con->prepare("UPDATE `usuarios` SET `usuario`=?, `nombres`=?, `apellidos`=?, `contrasena`=? WHERE `id`=?");
						$update->bind_param('ssssi', $usuario, $nombres, $apellidos, $contrasena, $nik); //agregar variables a la sentencia preparada
						$update->execute();
						$n = "";
						$fecha = date("Y-m-d");
						date_default_timezone_set("America/Bogota");
						$hora = date('h:i A');
						$usu = $_SESSION['name'];

						//comparar valores antiguos del usuario
						$n = cambios($row['nombres'], $nombres, $n);

						$n = cambios($row['apellidos'], $apellidos, $n);

						$n = cambios($row['usuario'], $usuario, $n);
						$_SESSION['name'] = $usuario;

						if (cambioValor($desencriptar($row['contrasena']), $desencriptar($contrasena))) {
							$n = $n . "Cambió su contraseña";
						}

						//agregar acción de editar usuario al historial de acciones de usuarios
						$con->query("INSERT INTO `historial`(`usuario`, `accion`, `valor`, `fecha`, `hora`,`tipo`) VALUES ('$usu','Modificó su perfil','$n','$fecha','$hora',1)");
						$update->close();
						$con->commit();
						//recargar información para enviar mensaje de éxito
						header("Location: usu-adedit.php?nik=" . $nik . "&ops=succ");
					} catch (Exception $e) {
						$con->rollback();
						header("Location: usu-adedit.php?nik=" . $nik . "&ops=erro");
					}
				}
				//si se recibe un dato de agregar, para agregar un usuario
				if (isset($_POST['add'])) {
					try {
						$usuario = $con->real_escape_string($_POST["usuario"]); //Escapar caracteres especiales 
						$nombres = $con->real_escape_string($_POST["nombres"]); //Escapar caracteres especiales 
						$apellidos = $con->real_escape_string($_POST["apellidos"]); //Escapar caracteres especiales 
						$contrasena = $encriptar($con->real_escape_string($_POST["contrasena"])); //Escapar caracteres especiales 
						$fecha = date("Y-m-d");

						//si se intenta cambiar el nombre a admin, emite un error para cancelar la operacion
						if ($usuario == 'admin') {
							throw new Exception();
						}
						//agregar nueva capa a la tabla de capas
						mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // para activar reportar error en secuencias simples (query)
						$con->autocommit(FALSE); //iniciar transaccion a la base de datos
						$insert = $con->prepare("INSERT INTO `usuarios`(`nombres`, `apellidos`, `usuario`, `contrasena`, `fecha`) VALUES (?,?,?,?,?)");
						$insert->bind_param('sssss', $nombres, $apellidos, $usuario, $contrasena, $fecha); //agregar variables a la sentencia preparada
						$insert->execute();
						date_default_timezone_set("America/Bogota");
						$hora = date('h:i A');
						$n = "Nombre: " . $nombres . "<br>Usuario: " . $usuario;
						$con->query("INSERT INTO `historial`(`usuario`, `accion`, `valor`, `fecha`, `hora`) VALUES ('admin','Creó un usuario','$n','$fecha','$hora')");
						echo '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> Los datos han sido guardados con éxito.</div>';
						$insert->close();
						$con->commit();
					} catch (Exception $e) { //si hay error, revierte la transaccion
						$con->rollback();
						echo '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Error. No se pudo guardar los datos !</div>';
					}
				}


				if (isset($_GET['ops'])) {
					switch ($_GET['ops']) {
						case 'succ':
							echo '<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Los datos han sido guardados con éxito.</div>';
							break;
						case 'erro':
							echo '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Error, no se pudo guardar los datos.</div>';
							break;
					}
				}

				?>
				<form class="form-horizontal text-left" action="" method="post" onsubmit="return checkSubmit();">
					<?php
					//si se recibe un valor de id, el formulario cargará la información de un usuario para editarla
					if (isset($_GET["nik"])) {
					?>

						<div class="form-group">
							<label class="col-sm-3 control-label">Nombres</label>
							<div class="col-sm-3">
								<input type="text" name="nombres" value="<?php echo $row['nombres']; ?>" class="form-control" placeholder="Nombres" required pattern=".{5,50}" title="El campo de nombres debe tener mínimo 5 y máximo 50 caracteres">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Apellidos</label>
							<div class="col-sm-3">
								<input type="text" name="apellidos" value="<?php echo $row['apellidos']; ?>" class="form-control" placeholder="Apellidos">
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-3 control-label">Usuario</label>
							<div class="col-sm-3">
								<input type="text" name="usuario" value="<?php echo $row['usuario']; ?>" class="form-control" placeholder="Usuario" required pattern=".{5,20}" title="El campo de usuario debe tener entre 5 y 20 caracteres">
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-3 control-label">Contraseña</label>

							<div class="col-sm-3">
								<div class="input-group">
									<input type="password" name="contrasena" class="form-control pwd" value="<?php echo $desencriptar($row['contrasena']); ?>" placeholder="Contraseña" required pattern=".{5,15}" title="La contraseña debe tener entre 5 y 15 caracteres" style="width: 252px;">
									<button class="btn btn-default reveal" type="button">
										<span><i class="glyphicon glyphicon-eye-open"></i></span>
									</button>
								</div>

							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-3 control-label">&nbsp;</label>
							<div class="col-sm-6">
								<input type="submit" name="save" class="btn btn-sm btn-primary" value="Guardar datos">
								<a href="inicio.php" class="btn btn-sm btn-danger">Cancelar</a>
							</div>
						</div>

					<?php
						$sql->close();
					}


					//de lo contrario, el formulario será para agregar un usuario
					else { ?>

						<div class="form-group">
							<label class="col-sm-3 control-label">Nombres</label>
							<div class="col-sm-4">
								<input type="text" name="nombres" class="form-control" placeholder="Nombres" required pattern=".{5,50}" title="El campo de nombres debe tener mínimo 5 y máximo 50 caracteres">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Apellidos</label>
							<div class="col-sm-4">
								<input type="text" name="apellidos" class="form-control" placeholder="Apellidos">
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-3 control-label">Usuario</label>
							<div class="col-sm-4">
								<input type="text" name="usuario" class="form-control" placeholder="Usuario" autocomplete="new-text" required pattern=".{5,20}" title="El campo de usuario debe tener entre 5 y 20 caracteres">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Contraseña</label>

							<div class="col-sm-3">
								<div class="input-group">
									<input type="password" name="contrasena" class="form-control pwd" placeholder="Contraseña" required pattern=".{5,15}" title="La contraseña debe tener entre 5 y 15 caracteres" style="width: 252px;">
									<button class="btn btn-default reveal" type="button">
										<span><i class="glyphicon glyphicon-eye-open"></i></span>
									</button>
								</div>

							</div>
						</div>


						<div class="form-group">
							<label class="col-sm-3 control-label">&nbsp;</label>
							<div class="col-sm-6">
								<input type="submit" name="add" class="btn btn-sm btn-primary" value="Guardar datos">
								<a href="inicio.php" class="btn btn-sm btn-danger">Cancelar</a>
							</div>
						</div>
					<?php
					} ?>

				</form>
			</div>
		</div>


		<?php

		include 'nav.php'; ?>
		<script type="text/javascript">
			//evitar el envio doble de los formularios
			var statSend = false;

			function checkSubmit() {
				if (!statSend) {
					statSend = true;
					return true;
				} else {
					return false;
				}
			}
			$(document).ready(function() {

				//mostrar u ocultar contraseña
				$(".reveal").on('click', function() {
					var $pwd = $(".pwd");
					if ($pwd.attr('type') === 'password') {
						$pwd.attr('type', 'text');
					} else {
						$pwd.attr('type', 'password');
					}
				});
				//restringir el tamaño máximo del texto en el input de nombres del usuario
				$('[name="nombres"]').keypress(function(event) {
					if (this.value.length >= 50) {
						return false;
					}
				});
				//restringir el tamaño máximo del texto en el input de apellidos del usuario				
				$('[name="apellidos"]').keypress(function(event) {
					if (this.value.length >= 50) {
						return false;
					}
				});
				//restringir el tamaño máximo del texto en el input de usuario del usuario				
				$('[name="usuario"]').keypress(function(event) {
					if (this.value.length >= 20) {
						return false;
					}
				});
				//restringir el tamaño máximo del texto en el input de contraseña del usuario				
				$('[name="contrasena"]').keypress(function(event) {
					if (this.value.length >= 15) {
						return false;
					}
				});

			});
		</script>
	</body>
<?php
} else {
	//si no hay una sesión activa en la plataforma,redirige al formulario de login
	header("location:../index.php");
}
?>

	</html>