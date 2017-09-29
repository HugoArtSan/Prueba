var app = angular.module("mesas",[])

app.factory('keyService', function($rootScope) {
   var service = {};
   service.message = '';
   service.broadcast = function(key) {
      service.message = key;
	  $rootScope.$broadcast('keyEvent',key);
   }
   return service;
});

app.controller('mainCtrl', function($scope,keyService) {


});

app.directive('teclas', function(keyService) {
   return function(scope,element,attr) {
       element.bind("keydown", function(event) {
		keyService.broadcast(event.keyCode);
    });   
   }

});

app.config(function($routeProvider) {
   $routeProvider.when('/',
     {
	    templateUrl: "login.html",
		controller: "loginCtrl"
	 })
	 .when('/mesas/:seccion', {
	    templateUrl: "mesas.html",
		controller: "mesasCtrl",

	 })
	 .otherwise( {
	    redirectTo: "/"
	 });
});



app.controller("loginCtrl", function($scope,$http,$window) {
   $scope.mesero = "";
   $scope.password = "";
   
   $scope.validar = function() {

      $scope.url = 'sesion_mesero_set.php'; // archivo que devuelve las mesas encontradas.


	  
     // La peticion es JSON
	 
      $http.post($scope.url, { "mesero" : $scope.mesero,"pass" : $scope.password}).
        success(function(data, status) {
	       $scope.status = status;
		   $scope.data = data;
		   if ($scope.data[0].clave) {
		      if ($scope.data[0].bloqueado == 'S')
		         alert("El mesero esta activo en otra estacion");			  
			  else
                 $window.location.href="comandero.html#/mesas/0";	
		   } else {
		      alert("por favor revise los datos proporcionados");
		   }					 
	    }).
	    error(function(data, status) {
	      $scope.data = data || "Error de datos.";
		  $scope.status = status;
	   });


   };

   document.getElementById("mesero").focus(); 
});

app.controller("mesasCtrl", function($scope,$http,$window,$routeParams,keyService) {
   //alert(screen.width);
   $scope.mesero = {};

   $scope.secciones = [{"clave" : "01", "nombre" : "SALON 1"},   
                       {"clave" : "02", "nombre" : "SALON 2"},
					   {"clave" : "03", "nombre" : "EVENTOS"},
					   {"clave" : "04", "nombre" : "VARIOS"}
                     ];

   if ($routeParams.seccion)
      $scope.seccion_sel = $routeParams.seccion;
   else
      $scope.seccion_sel = 0;
   
   $scope.mesa_sel = "";

   $scope.procesarTecla = function(tecla) {
		    if (tecla == 34) {
			   // Avanzar seccion.
			   $scope.avanzar_secc();

			} if (tecla == 13) {
			   var temp = $scope.mesa_sel.split("_");
			   var indice = temp[1];
			   var mesa = $scope.mesas[indice].numero;
               var seccion = $scope.secciones[$scope.seccion_sel].clave;
			   $window.location.href = "comandas_prod.php?mesa="+mesa+"&seccion="+seccion+"&mesero="+$scope.mesero.clave+"&seccion_ind="+$scope.seccion_sel;
		    }  else if (tecla == 27) {
			   $window.location.href = "comandero.html#/login";			
			}  else if (tecla == 33) {
			   $scope.retroceder_secc();			
			} else if (tecla == 39) {
			      // Seleccionar la mesa del lado derecho que este mas cerca de la mesa actual.
			      var temp = $scope.mesa_sel.split("_");
				  var indice = temp[1];
				  var actual_x = $scope.mesas[indice].pos_x + 40;
				  var actual_y = $scope.mesas[indice].pos_y;
				  
				  var distancia = 10000;
				  var pos = 0;
				  // Recorrer todas las mesas y tomar la que este a la menor distancia a la derecha de la mesa actual.
				  for (x = 0; x < $scope.mesas.length; x++) {
				     if ($scope.mesas[x].pos_x > actual_x) {
					    var distancia_x = Math.abs(actual_x - $scope.mesas[x].pos_x);
					    var distancia_y = Math.abs(actual_y - $scope.mesas[x].pos_y);
						if ((distancia_x + distancia_y) < distancia) {
						   distancia = (distancia_x + distancia_y);
						   pos = x;
						}
					 }
				  
				  }
				  
				  if (distancia < 10000) {
                     $scope.select("mesa_"+pos);				  
				  }
			} else if (tecla == 37) {
			      // Seleccionar la mesa del lado izquierdo que este mas cerca de la mesa actual.
			      var temp = $scope.mesa_sel.split("_");
				  var indice = temp[1];
				  var actual_x = $scope.mesas[indice].pos_x - 40;
				  var actual_y = $scope.mesas[indice].pos_y;
				  
				  var distancia = 10000;
				  var pos = 0;
				  // Recorrer todas las mesas y tomar la que este a la menor distancia a la derecha de la mesa actual.
				  for (x = 0; x < $scope.mesas.length; x++) {
				     if ($scope.mesas[x].pos_x < actual_x) {
					    var distancia_x = Math.abs(actual_x - $scope.mesas[x].pos_x);
					    var distancia_y = Math.abs(actual_y - $scope.mesas[x].pos_y);
						if ((distancia_x + distancia_y) < distancia) {
						   distancia = (distancia_x + distancia_y);
						   pos = x;
						}
					 }
				  
				  }
				  
				  if (distancia < 10000) {
                     $scope.select("mesa_"+pos);				  
				  }

			} else if (tecla == 40) {
			      // Seleccionar la mesa de abajo que este mas cerca de la mesa actual.
			      var temp = $scope.mesa_sel.split("_");
				  var indice = temp[1];
				  var actual_x = $scope.mesas[indice].pos_x;
				  var actual_y = $scope.mesas[indice].pos_y + 40;
				  
				  var distancia = 10000;
				  var pos = 0;
				  // Recorrer todas las mesas y tomar la que este a la menor distancia a la derecha de la mesa actual.
				  for (x = 0; x < $scope.mesas.length; x++) {
				     if ($scope.mesas[x].pos_y > actual_y) {
					    var distancia_x = Math.abs(actual_x - $scope.mesas[x].pos_x);
					    var distancia_y = Math.abs(actual_y - $scope.mesas[x].pos_y);
						if ((distancia_x + distancia_y) < distancia) {
						   distancia = (distancia_x + distancia_y);
						   pos = x;
						}
					 }
				  
				  }
				  
				  if (distancia < 10000) {
                     $scope.select("mesa_"+pos);				  
				  }

			} else if (tecla == 38) {
			      // Seleccionar la mesa de arriba que este mas cerca de la mesa actual.
			      var temp = $scope.mesa_sel.split("_");
				  var indice = temp[1];
				  var actual_x = $scope.mesas[indice].pos_x;
				  var actual_y = $scope.mesas[indice].pos_y - 40;
				  
				  var distancia = 10000;
				  var pos = 0;
				  // Recorrer todas las mesas y tomar la que este a la menor distancia a la derecha de la mesa actual.
				  for (x = 0; x < $scope.mesas.length; x++) {
				     if ($scope.mesas[x].pos_y < actual_y) {
					    var distancia_x = Math.abs(actual_x - $scope.mesas[x].pos_x);
					    var distancia_y = Math.abs(actual_y - $scope.mesas[x].pos_y);
						if ((distancia_x + distancia_y) < distancia) {
						   distancia = (distancia_x + distancia_y);
						   pos = x;
						}
					 }
				  
				  }
				  
				  if (distancia < 10000) {
                     $scope.select("mesa_"+pos);				  
				  }
			
			}

   
   };   
   // Marcar la mesa seleccionada					 
   $scope.select = function(obID) {
      //var el = document.getElementsByTagName("div");
	  //for (x = 0; x < el.length; x++) {
      //   if(el.item(x).name == "mesa");
	  //     el.item(x).style.border = "none";
	  //}
	  
	  // Si la mesa ya estaba seleccionada, significa que queremos abrirla.
	  if ($scope.mesa_sel == obID) {
		 var temp = $scope.mesa_sel.split("_");
		 var indice = temp[1];
		 var mesa = $scope.mesas[indice].numero;
         var seccion = $scope.secciones[$scope.seccion_sel].clave;
		 $window.location.href = "comandas_prod.php?mesa="+mesa+"&seccion="+seccion;	  
	  }
	  
	  if ($scope.mesa_sel != "") 
         document.getElementById($scope.mesa_sel).style.border = "none";
	     
      document.getElementById(obID).style.border = "solid #fa5";
      $scope.mesa_sel = obID;
   }; 

   $scope.setStyle = function(pos_x,pos_y) {
      return { "position" : "absolute", "left" : pos_x+"px", "top" : pos_y+"px" };
   }
   
   // Avanzar seccion.
   $scope.avanzar_secc = function() {
	   if ($scope.seccion_sel >= $scope.secciones.length - 1) {
	      $scope.$apply($scope.seccion_sel = 0);
	   } else {
          $scope.$apply($scope.seccion_sel++);
	   } 
	   $scope.$apply($scope.cargar($scope.secciones[$scope.seccion_sel].clave));   
   }
   // Retroceder seccion.
   $scope.retroceder_secc = function() {
       if ($scope.seccion_sel <= 0) {
	      $scope.$apply($scope.seccion_sel = $scope.secciones.length -1);
	   } else {
          $scope.$apply($scope.seccion_sel--);			   
	   }

	   $scope.$apply($scope.cargar($scope.secciones[$scope.seccion_sel].clave));
   
   }
   
    
   $scope.cargar = function(seccion) {
      $scope.url = 'carga_mesas.php'; // archivo que devuelve las mesas encontradas.


	  
     // La peticion es JSON
	 var ancho = screen.width;
	 //alert("Ancho "+ancho);
	 
     $http.post($scope.url, { "seccion" : seccion,"ancho" : ancho}).
       success(function(data, status) {
	      $scope.status = status;
 	  	  $scope.mesa_sel = "";		
		  if (data[0].numero) {
		     $scope.mesas = data;
		     $scope.select("mesa_0");
		  } else {
		     $scope.mesas = {};
		  } 					 
          $scope.$apply();	  

	   }).
	   error(function(data, status) {
	      $scope.data = data || "Error de datos.";
		  $scope.status = status;
	   });
	
    };
    
	$scope.keyup = function(keyEvent) {
	  alert(keyEvent);
	};
	$scope.$apply($scope.cargar($scope.secciones[$scope.seccion_sel].clave)) 
	//$scope.cargar("01");
	//document.getElementById("divPrincipal").focus()
	
	$scope.$on('keyEvent',function(event,message){
	   //$scope.procesarTecla(keyService.message);
	   $scope.procesarTecla(message);

	});
	
	// Obtener el mesero de la session PHP y si no existe, regresar a login.

    $scope.url = 'sesion_mesero_get.php'; // archivo que devuelve las mesas encontradas.
	  
    // La peticion es JSON	 
    $http.post($scope.url, { }).
        success(function(data, status) {
		   if (data[0].clave) {
              $scope.mesero = {clave : data[0].clave, nombre : data[0].nombre};	
		   } else {
		     alert("No hay mesero registrado");
             $window.location.href="comandero.html#/login";	
		   }					 
	    }).
	    error(function(data, status) {
	      $scope.data = data || "Error de datos.";
		  $scope.status = status;
	});


	
	
});