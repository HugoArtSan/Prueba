<?php
   include "libreria.lib.php";
   include "database.lib.php";

class Algo
{

   public function __construct() {   

   }

   public function buscarAlgo($este) {   
      $con=conectar();
	  
      $datos_array = array();      
      $datos_array[] = array("nombre" => 'juanelo', "direccion" => 'monte limbo #13');  
      $datos_array[] = array("nombre" => 'petronila', "direccion" => 'puerto la soledad #71');  
	  $datos = array("datos" => $datos_array);
	  
	  pg_close($con);
      return $datos;
 
   }
}



header('Content-Type: application/json');
$algo = new Algo();
echo json_encode($algo->buscarAlgo());

?>