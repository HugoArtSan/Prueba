// Clase para manejar teclas de cursor dentro de una tabla
function TeclasTabla(tabla,rens,cols) {
   this.tabla = tabla;
   this.totR = rens;   
   this.totC = cols;   
   this.actualR = 0;   
   this.actualC = 0;   

   this.init = function() {   
      this.actualR = 0;   
      this.actualC = 0;   
      this.ponFoco();   
   };   

   this.accion = null;  // Aqui se pone la funcion que se va a llamar cuando se pulse la tecla enter;   
   this.escape = null;  // Aqui se pone la funcion que se va a llamar cuando se pulse la tecla escape;   

   this.setPos = function(ren,col) {
      this.quitaFoco();   
      if (ren < this.totR)      
         this.actualR = ren;         
      if (col < this.totC)      
         this.actualC = col;         
      this.ponFoco();
   };   


   this.getCol = function() {   
      return this.actualC;
   };   

   this.getRow = function() {   
      return this.actualR;
   };   

   this.processKey = function(e) {   

      if (document.activeElement.id == this.tabla) {      
         return this.revisaTecla(e);
      } else if (document.activeElement.parentNode.parentNode.parentNode) {      
         if (document.activeElement.parentNode.parentNode.parentNode.id == this.tabla) {        
             return this.revisaTecla(e);
         }
      }
   };


   this.revisaTecla = function(e) {
      var code = 0;
      if (e.keyCode) code = e.keyCode;
      else if (e.which) code = e.which;
   
      switch (code)      
      {
	    case 40:
           this.baja();            
           return false;
		   break;
	    case 38:
           this.sube();            
           return false;
		   break;
	    case 37:
           this.izquierda();            
           return false;
		   break;
	    case 39:
           this.derecha();            
           return false;
		   break;
	    case 13:    
           if (this.accion)
              this.accion();            
           return false;
		   break;
	    case 27:    
           if (this.escape)
              this.escape();            
           return false;
		   break;

		default:
		  return true;   
      }
   };   

   this.baja = function() {   
      if (this.actualR < this.totR-1) {
	     this.quitaFoco();  // Desmarcar la celda actual.      
         this.actualR++;         
         this.ponFoco();  // Marcar la celda seleccionada
      }
   };   

   this.sube = function() {   
      if (this.actualR > 0) {
	     this.quitaFoco();  // Desmarcar la celda actual.      
         this.actualR--;         
         this.ponFoco();  // Marcar la celda seleccionada
      }
   };   

   this.izquierda = function() {   
      if (this.actualC > 0) {
	     this.quitaFoco();  // Desmarcar la celda actual.      
         this.actualC--;         
         this.ponFoco();  // Marcar la celda seleccionada
      }
   };   

   this.derecha = function() {   
      if (this.actualC < this.totC-1) {
	     this.quitaFoco();  // Desmarcar la celda actual.      
         this.actualC++;         
         this.ponFoco();  // Marcar la celda seleccionada
      }
   };   

   this.quitaFoco = function() {   
      var pos = (this.actualR * this.totC) + this.actualC;
      $("#"+this.tabla+" td:eq("+pos+")").removeClass("focus");
   };   

   this.ponFoco = function() {   
      var pos = (this.actualR * this.totC) + this.actualC;
      $("#"+this.tabla+" td:eq("+pos+")").addClass("focus");
   };   

};

// Clase para manejar tablas con mayor facilidad incluye paginacion.
function tabla(rens,cols,dim) {
   this.activa = false;
   this.paginaActual = 0;      
   this.totPaginas = 0;
   this.rens = rens;   
   this.cols = cols;   
   this.dim = dim;   
   this.tamPagina = this.rens * this.cols;    
   this.lista = new Array();   

   this.longitud = rens * cols;   


   // Obtener el numero de renglones
   this.getRenglones = function() {
      return this.rens;
   };   

   // Agregar un registro a la lista   
   this.addReg = function(registro) {   
      this.lista.push(registro);      
            
      this.totPaginas = Math.floor(this.lista.length / this.tamPagina);      

      if (this.lista.length > (this.totPaginas * this.tamPagina)) this.totPaginas++;
      // Actualizar el total de paginas.      
            
      if (this.paginaActual > this.totPaginas) this.paginaActual = this.totPaginas;
   };   

   this.getReg = function(ren,col) {      
       // Pendiente: validar limites.    
	   var indice = (this.paginaActual * this.tamPagina) + (ren * this.cols) + col;   

       if (indice < this.lista.length) {      
          return this.lista[indice];    
	   } else {         
          var registro = new Array(dim);          
          for (i = 0; i < dim; i++) registro[i] = "";
          return registro;          
       }
   }


   // Regresar el total de paginas   
   this.getPaginas = function() {   
      return this.totPaginas;
   }   

   // Regresar el total de registros   
   this.getRegistros = function() {   
      return this.lista.length;
   }      

   // Avanzar una pagina
   this.avanzaPag = function() {    

      if (this.paginaActual < (this.totPaginas - 1))
         this.paginaActual++;
   }

   // Regresar una pagina   
   this.regresaPag = function() {   
      if (this.paginaActual > 0)      
         this.paginaActual--;
   }   

   this.clear = function() {   
      this.lista.splice(0,this.lista.length);
   }
}