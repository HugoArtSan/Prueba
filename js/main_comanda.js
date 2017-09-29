var app = angular.module("comandaApp",[])



app.controller("comandaCtrl", function($scope,$http) {
   //alert(screen.width);
   $scope.data = {};
   
   $scope.partidas = [];
   $scope.etiquetas_vista = 'L';
   
   $scope.tam_pagina = 30;
   $scope.pagina_actual = 0;
   $scope.etiquetas = {};
   $scope.linea_actual = '';  // Linea o familia de productos seleccionada.

   $scope.totalCuenta = function() {
      var total = 0;
      for (var ind = 0; ind < $scope.partidas.length; ind++) {
	      total += ($scope.partidas[ind].cantidad * $scope.partidas[ind].precio);
	  }
	  return total;
   
   }
    
   $scope.avanzarPag = function() {
      $scope.pagina_actual++;
	  if ($scope.etiquetas_vista == 'L')
	     $scope.cargarFamilias();
	  else
	     $scope.cargarProductos();
   }

   $scope.regresarPag = function() {
      if ($scope.pagina_actual > 0) {
         $scope.pagina_actual--;
		 if ($scope.etiquetas_vista == 'L')
	        $scope.cargarFamilias();
		 else
		    $scope.cargarProductos();
	  }
   }
   
   $scope.mostrarLineas = function() {
      $scope.etiquetas_vista = 'L';
	  $scope.linea_actual = '';  // Linea o familia de productos seleccionada.
	  $scope.pagina_actual = 0;
	  $scope.cargarFamilias();
   }
   
   $scope.cargarFamilias = function() {
      $scope.url = 'pagina_registros.php'; // archivo que devuelve las mesas encontradas.


	  
     // La peticion es JSON
	 var offset = $scope.tam_pagina * $scope.pagina_actual;
	 var sql="SELECT * FROM r_lineas WHERE venta = 'S' AND status='A' LIMIT "+$scope.tam_pagina+" OFFSET "+offset;
	 
     $http.post($scope.url, { "sql" : sql}).
       success(function(data, status) {
	      $scope.status = status;
          $scope.$apply();	  
		  if (data.length > 0) {
		     $scope.etiquetas = data;
		  } else {
		     if ($scope.pagina_actual > 0)
			    $scope.pagina_actual--;
		  }
		  $scope.$apply();					 
	   }).
	   error(function(data, status) {
	      $scope.data = data || "Error de datos.";
		  $scope.status = status;
	   });
	
    };

   $scope.cargarProductos = function() {
      $scope.url = 'pagina_registros.php'; // archivo que devuelve las mesas encontradas.

     // La peticion es JSON
	 var offset = $scope.tam_pagina * $scope.pagina_actual;
	 var sql="SELECT clave,nombre,precio1,precio2,precio3 FROM r_productos WHERE linea = '"+$scope.linea_actual+"' AND status='A' LIMIT "+$scope.tam_pagina+" OFFSET "+offset;
     $http.post($scope.url, { "sql" : sql}).
       success(function(data, status) {
	      $scope.status = status;
		  if (data.length > 0) {
		     $scope.etiquetas = data;
		  } else {
		    if ($scope.pagina_actual == 0) 
	           $scope.etiquetas = {};
		    if ($scope.pagina_actual > 0) 
			   $scope.pagina_actual--;
		  
		  }

		  $scope.$apply();		
			 
	   }).
	   error(function(data, status) {
	      $scope.data = data || "Error de datos.";
		  $scope.status = status;
	   });
	
    };

	$scope.clickEtiqueta = function(indice) {
	   if ($scope.etiquetas_vista == 'L') {
	      $scope.etiquetas_vista = 'P';
		  $scope.linea_actual =  $scope.etiquetas[indice].clave;
		  $scope.pagina_actual = 0;
		  $scope.cargarProductos();
	   } else {
	      var clave = $scope.etiquetas[indice].clave;
		  var nombre = $scope.etiquetas[indice].nombre;
		  var precio = $scope.etiquetas[indice].precio1;  // Despues revisar cual precio se toma segun los horarios.
		  $scope.partidas.push({clave : clave,cantidad : 1, nombre : nombre, precio : precio});
	      
	   }
	}
	
	$scope.claseEtiqueta = function() {
	   if ($scope.etiquetas_vista == 'L')
	      return "lineas";
	   else
	      return "productos";	  
	}
    $scope.cargarFamilias();
	
});