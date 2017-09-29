<?php
session_start();
   include "database.lib.php";
   include "objetos.lib.php";
   include "libreria.lib.php";
?>
<!--
//consultar datos de db
//guardar datos
//eliminar
//mover
-->
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>:: MEDISA - HUGO ::</title>
  <link rel="stylesheet" href="bootstrap/css/bootstrap15.css">
  <link rel="stylesheet" href="bootstrap/css/bootstrap-submenu.min.css">

  <script src="js/jquery.js"></script>

  <script src="bootstrap/js/bootstrap.js"></script>
  <script src="bootstrap/js/bootstrap-submenu.min.js"></script>
  <script src="bootstrap/js/bootstrap-modal.js"></script>
  <script src="bootstrap/js/bootbox.js"></script>

  <link rel="stylesheet" href="css/jquery-ui.css" />
  <script type="text/javascript" src="js/jquery-ui.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body>


<div id="div_consumo" class="container col-md-offset-4 col-md-4">
	<div class="panel panel-primary">
	  <div class="panel-heading text-center">:: TABLA ::</div>
	  <div class="panel-body">
		<div class="table-responsive">
  		  <table id="consumo" class='table table-striped table-bordered table-hover table-condensed'>
			<thead>
			  <tr>
				<th colspan="2">&nbsp;</th>
				<th>Producto</th>
				<th class="text-right" >Importe</th>
				<th class="hidden-xs hidden-sm hidden-md hidden-lg">Clave</th>
				<th class="hidden-xs hidden-sm hidden-md hidden-lg">Precio</th>
			  </tr>
			</thead>
			 <tbody>
			 </tbody>
			 <tfoot>
			 <tr>
				<td class="text-right h3" colspan="3"> TOTAL</td>
				<td class="text-right h3" id="total">0.00</td>
			 </tr>
			 </tfoot>
		  </table>
		  <p align="center">
			  <button id="Bagrega" onclick="agrega();" class="btn btn-info btn-lg" type="button">Agrega</button>
			  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			  <button id="Bquita" onclick="cancela();" class="btn btn-info btn-lg" type="button">Quita todo</button>
		  </p>
		  <p align="center">
			  <button id="Bverde" onclick="verde();" class="btn btn-success btn-lg" type="button">Verde</button>
			  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			  <button id="Brojo" onclick="rojo();" class="btn btn-danger btn-lg" type="button">Rojo</button>
		  </p>
		  <p align="center">
			  <button id="Boculta" onclick="oculta();" class="btn btn-warning btn-lg" type="button">Oculta</button>
			  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			  <button id="Bmuestra" onclick="muestra();" class="btn btn-default btn-lg" type="button">Muestra</button>
		  </p>
		</div>
	  </div>
	</div>
</div>

	<div class="container col-md-offset-4 col-md-4">
		<p align="center">
		     <button id="botonazo" onclick="traiteAlgo();" class="btn btn-warning btn-lg" type="button">Botonazo</button>
		</p>
	</div>


</body>
</html>


<script language="javascript">
var ren=0;



function oculta(){
	$("#botonazo").hide();
}

function muestra(){
	$("#botonazo").show();
}

function rojo(){
	$('#botonazo').removeClass('btn-warning');
	$('#botonazo').removeClass('btn-success');
	$('#botonazo').addClass('btn-danger');
}

function verde(){
	$('#botonazo').removeClass('btn-warning');
	$('#botonazo').removeClass('btn-danger');
	$('#botonazo').addClass('btn-success');
}


function agrega(){
	    c='1';
		n='ALGUN PRODUCTO';
		p='100.00';
	    cant=1;
		ren++;
	    $("#consumo").append('<tr id="renglon'+ren+'"> '+
				    '<td class="text-center">'+
					'<button type="button" class="btn btn-sm btn-success" onclick="aumenta('+ren+');">'+
					'	    <span class="glyphicon glyphicon-plus"></span>'+
					'</button> &nbsp; &nbsp; &nbsp; &nbsp;'+
					'<button type="button" class="btn btn-sm btn-danger" onclick="quita('+ren+');">'+
					'	    <span class="glyphicon glyphicon-minus"></span>'+
					'</button>'+
					'</td> '+
					'<td id="cant'+ren+'">'+cant+'</td> '+
					'<td id="n'+ren+'" >'+n+'</td> '+
					'<td class="text-right" id="i'+ren+'">'+p+'</td> '+
					'<td id="c'+ren+'" class="hidden-xs hidden-sm hidden-md hidden-lg">'+c+'</td> '+
					'<td id="p'+ren+'" class="hidden-xs hidden-sm hidden-md hidden-lg">'+p+'</td> '+
				'</tr> ');
	totaliza();
    $(".navbar-collapse").collapse('hide');
}


function aumenta(este){
	p=$("#p"+este).html();
	c=$("#cant"+este).html();
	c++;
	$("#cant"+este).html(c);
	i=p*c;
	i=i.toFixed(2);
	$("#i"+este).html(i);
	totaliza();
}

function quita(este){
	p=$("#p"+este).html();
	c=$("#cant"+este).html();
	c--;
	if(c>0)$("#cant"+este).html(c);
	i=p*c;
	i=i.toFixed(2)
	$("#i"+este).html(i);
	totaliza();
	if(c<1)	$('#renglon'+este).addClass('hide');
}

function totaliza(){
  t=0;
  f=$('#consumo >tbody >tr').length;
  for(i=1; i<=f; i++){
	  imp=$('#i'+i).html();
	  t+=parseFloat(imp);
  }
  t=t.toFixed(2)
  $("#total").html(t);


}

function cancela(){
	$("#consumo > tbody").empty();
	ren=0;
	totaliza();
}

function ordena(){
  lineas = [];
  f=$('#consumo >tbody >tr').length;
  //alert(f);
  for(x=1; x<=f; x++){
	cant=$('#cant'+x).html();
	//alert(cant);
    if(cant>0){
		c=$('#c'+x).html();
		p=$('#p'+x).html();
		i=$('#i'+x).html();

		lineas.push( {'c': c, 'cant': cant , 'p':p } );
	}

  }

  id=$('#elID').val();
  nombre=$('#nombre').val();
  direccion=$('#direccion').val();
  exterior=$('#exterior').val();
  interior=$('#interior').val();
  colonia=$('#colonia').val();
  telefono=$('#telefono').val();
  email=$('#email').val();
  referencia=$('#referencia').val();
  //alert(id);
  guardaCom(lineas,id,nombre,direccion,exterior,interior,colonia,telefono,email,referencia);
}


function traiteAlgo(){
	$.get('traiteAlgo.php', function(data, status, request){
	   alert(JSON.stringify(data));
	   data.datos.forEach(function(reg) {
	       alert(reg.nombre);
		   alert(reg.direccion);
       });
    });
}


</script>
//all