<?php
function la_db(){
  return "vallarta";
}

function conectar(){
	 $servidor="localhost";
	 $administrador="postgres";
	 $password="1234";
	 $basedatos=prueba();
	 $puerto="5432";

	 $error=0;
	  $con = pg_connect("host=$servidor port=$puerto dbname=$basedatos user=$administrador password=$password") or $error=1;

	  if($error==1){
	    echo "<font face color='#FFFFFF'>No se pudo conectar a la db... Se crear� una nueva </font><br>";
	    $con = pg_connect("host=$servidor port=$puerto dbname=postgres user=$administrador password=$password") or
			die ("<font face color='#FFFFFF'>No se pudo crear una db nueva. Puede que postgres no este instalado o debidamente configurado </font><br>" . pg_last_error($con));

	    pg_query($con,"create database $basedatos") or die("<font face color='#FFFFFF'>No se pudo conectar a la db nueva, puede que ya exista una. </font><br>");
	    echo "<font face color='#FFFFFF'>Database nueva creada con exito</font><br>";
	    pg_close($con);

	    $con = pg_connect("host=$servidor port=$puerto dbname=$basedatos user=$administrador password=$password") or $error=1;
	    crea_tablas($con);

		}
	  return $con;
}


?>
