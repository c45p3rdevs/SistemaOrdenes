<?php

require('./fpdf.php');

class PDF extends FPDF
{

   // Cabecera de página
   function Header()
   {
      include '../../php/conexion.php';//llamamos a la conexion BD

       $consulta_info = $con->query(" select * from capas ");
       //var_dump($con);//traemos datos desde BD
      $dato_info = $consulta_info->fetch_object();
      $this->Image('logo1.png', 185, 5, 20); //logo de la empresa,moverDerecha,moverAbajo,tamañoIMG
      $this->SetFont('helvetica', 'B', 19); //tipo fuente, negrita(B-I-U-BIU), tamañoTexto
      $this->Cell(45); // Movernos a la derecha
      $this->SetTextColor(0, 0, 0); //color
      //creamos una celda o fila
      $this->Cell(110, 15, utf8_decode('Dirección General del Sistema Penitenciario'), 0, 1, 'C', 0); // AnchoCelda,AltoCelda,titulo,borde(1-0),saltoLinea(1-0),posicion(L-C-R),ColorFondo(1-0)
      $this->Ln(3); // Salto de línea
      $this->SetTextColor(103); //color

      /* UBICACION */
      $this->Cell(110);  // mover a la derecha
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(96, 10, utf8_decode("Ubicación : "), 0, 0, '', 0);
      $this->Ln(5);

      /* TELEFONO */
      $this->Cell(110);  // mover a la derecha
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(59, 10, utf8_decode("Teléfono : "), 0, 0, '', 0);
      $this->Ln(5);

      /* COREEO */
      $this->Cell(110);  // mover a la derecha
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(85, 10, utf8_decode("Correo : "), 0, 0, '', 0);
      $this->Ln(5);

      /* TELEFONO */
      $this->Cell(110);  // mover a la derecha
      $this->SetFont('Arial', 'B', 10);
      $this->Cell(85, 10, utf8_decode(" : "), 0, 0, '', 0);
      $this->Ln(10);

      /* TITULO DE LA TABLA */
      //color
      $this->SetTextColor(228, 100, 0);
      $this->Cell(50); // mover a la derecha
      $this->SetFont('Arial', 'B', 15);
      $this->Cell(100, 10, utf8_decode("REPORTE DE MANTENIMIENTOS "), 0, 1, 'C', 0);
      $this->Ln(7);

      /* CAMPOS DE LA TABLA */
      //color
      $this->SetFillColor(228, 100, 0); //colorFondo
      $this->SetTextColor(255, 255, 255); //colorTexto
      $this->SetDrawColor(163, 163, 163); //colorBorde
      $this->SetFont('Arial', 'B', 11);
      $this->Cell(18, 10, utf8_decode('Ubicacion'), 1, 0, 'C', 1);
      $this->Cell(20, 10, utf8_decode('Nombre Tecnico'), 1, 0, 'C', 1);
      $this->Cell(30, 10, utf8_decode('Datos Equipo'), 1, 0, 'C', 1);
      $this->Cell(25, 10, utf8_decode('Resguardante'), 1, 0, 'C', 1);
      $this->Cell(70, 10, utf8_decode('Departamento'), 1, 0, 'C', 1);
      $this->Cell(25, 10, utf8_decode('Servicio'), 1, 1, 'C', 1);
   }

   // Pie de página
   function Footer()
   {
      $this->SetY(-15); // Posición: a 1,5 cm del final
      $this->SetFont('Arial', 'I', 8); //tipo fuente, negrita(B-I-U-BIU), tamañoTexto
      $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C'); //pie de pagina(numero de pagina)

      $this->SetY(-15); // Posición: a 1,5 cm del final
      $this->SetFont('Arial', 'I', 8); //tipo fuente, cursiva, tamañoTexto
      $hoy = date('d/m/Y');
      $this->Cell(355, 10, utf8_decode($hoy), 0, 0, 'C'); // pie de pagina(fecha de pagina)
   }
}

include '../../php/conexion.php';
//require '../../funciones/CortarCadena.php';

$consulta_info = $con->query(" select * from capas ");
$dato_info = $consulta_info->fetch_object();

$pdf = new PDF();
$pdf->AddPage(); /* aqui entran dos para parametros (horientazion,tamaño)V->portrait H->landscape tamaño (A3.A4.A5.letter.legal) */
$pdf->AliasNbPages(); //muestra la pagina / y total de paginas

$i = 0;
$pdf->SetFont('Arial', '', 12);
$pdf->SetDrawColor(163, 163, 163); //colorBorde


$consulta_reporte_alquiler = $con->query(" select ubicacion, titulo, ttl_lynd from capas ");
//var_dump($con);

//var_dump($consulta_reporte_alquiler);
$arreglo = [];
$n=0;
while ($datos_reporte = $consulta_reporte_alquiler->fetch_object()) {      
   $arreglo[$n]=['ubicacion'=>$datos_reporte->ubicacion,'titulo'=>$datos_reporte->titulo,'campo'=>$datos_reporte->tt_lynd];
   $n++;
   }
  echo $arreglo[0]['ubicacion'];
$i = $i + 1;
/* TABLA */
$pdf->Cell(18, 10, utf8_decode("Ubicacion"), 1, 0, 'C', 0);
$pdf->Cell(20, 10, utf8_decode("Nombre del Tecnico"), 1, 0, 'C', 0);
$pdf->Cell(30, 10, utf8_decode("Datos del Equipo"), 1, 0, 'C', 0);
$pdf->Cell(25, 10, utf8_decode("Nombre del Resguardante"), 1, 0, 'C', 0);
$pdf->Cell(70, 10, utf8_decode("Departamento"), 1, 0, 'C', 0);
$pdf->Cell(25, 10, utf8_decode("Servicio"), 1, 1, 'C', 0);


$pdf->Output('Prueba.pdf', 'I');//nombreDescarga, Visor(I->visualizar - D->descargar)
