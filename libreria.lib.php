<?php
date_default_timezone_set('America/Mexico_City');

function contenido_cheque($id,$con) {
   $famosa=false;

   $contenido = ""; 
   if ($id == "") $id = -1;   

   $sql="SELECT ch.id,ch.folio,ch.subtotal,ch.descuento,ch.total,ch.fecha,ch.hora,c.seccion,c.mesa,ch.cuenta,c.mesero,ch.status,c.folio as comanda,ch.propina,c.personas
  	     FROM r_cheques AS ch INNER JOIN r_comandas AS c ON ch.comanda = c.folio
	     WHERE ch.id = '$id' ";

   
   $result = pg_query($con,$sql);	        
   $row = pg_fetch_object($result,0); // Leer el primer registro para sacar mesero, seccion y mesa.   

   $subtotal  =$row->subtotal;
   $desc      =$row->descuento;
   $total     = $row->total;
   $descuento = $subtotal - $total;
  
   $folio     = $row->folio;   
   $fecha     = invierte_fecha($row->fecha);
   $hora      = $row->hora;
   $mesero    = $row->mesero;
   $seccion   = $row->seccion;
   $mesa      = $row->mesa;
   $cuenta    = $row->cuenta;
   $status    = $row->status;   
   $comanda   = $row->comanda;   
   $personas  = $row->personas; 
   $incluyeProp = $row->propina;   

   $mesero_nomb=pg_este("select nombre from r_meseros where clave like '$mesero' ","nombre",$con);


   // Crear encabezado.   
   $sql2 = "SELECT * FROM ticket_conf WHERE seccion = 'E' ORDER BY renglon,columna";   
   $result2 = pg_query($con,$sql2) or die (pg_last_error($con));   

   // Inicializar variables de encabezado
   $encab[1] = $folio;
   $encab[2] = $fecha;    
   $encab[3] = substr($hora,0,8);   
   $encab[4] = $seccion;   
   $encab[5] = $mesa;
   $encab[6] = $mesero;
   $encab[7] = $mesero_nomb;

   // Si lleva propina incluida se omite el mensaje propina.   
   if ($incluyeProp != "SI")
      $pie[1] = "PROPINA:______________________________";   
   else   
      $pie[1] = "";   

   $contenido .= chr(27)."t".chr(16);  // Pagina de caracteres Latin-1
   // Imprimir logo
   //$contenido .= chr(27)."g".chr(1);   

   $renglon_prev = "";
   $salida = str_repeat(" ",40);   
   while ($row2 = pg_fetch_array($result2)) {
      $renglon = $row2["renglon"];      
      $col     = $row2["columna"];      
      $campo   = $row2["campo"];      
      if ($campo == -1)
         $datos   = utf8_decode($row2["datos"]);
      else      
         $datos   = $encab[$campo];         


      if ($renglon_prev == "")
         $renglon_prev = $renglon;         
      else if ($renglon_prev != $renglon ) {      
         if (strlen($salida) > 40)         
            $salida = substr($salida,0,40);
		
		 // Enviar el buffer al archivo     
		 $contenido .= $salida."\n";
		   
         // Limpiar el buffer.
         $salida = str_repeat(" ",40);
         $renglon_prev = $renglon;
      }      

      $salida = substr_replace($salida,$datos,$col,strlen($datos)); 
   }

   if ($renglon_prev != "") {   
       if (strlen($salida) > 40)         
          $salida = substr($salida,0,40);

	   $contenido .= $salida."\n";

   }    


   if ($status == 'C'){   
      $sql="SELECT cd.producto,p.nombre,cd.precio,sum(cd.cantidad) as sum_cantidad, sum(cd.cantidad * cd.precio) as sum_importe
  	        FROM r_cheques AS ch INNER JOIN r_comandas AS c ON ch.comanda = c.folio
		    INNER JOIN r_comandas_detalles_c AS cd ON ch.folio = cd.cheque and cd.status not like 'C'
		    INNER JOIN r_productos as p ON cd.producto = p.clave
		    WHERE ch.id = '$id' 
		    GROUP BY cd.producto,p.nombre,cd.precio ORDER BY cd.producto";	    

      if($famosa){
		$sql="select d.cuenta,d.cantidad as sum_cantidad,d.producto,p.nombre,(d.cantidad * d.precio) as sum_importe,d.status,d.motivo_can 
	           from r_comandas_detalles_c as d 
			   INNER JOIN r_productos as p ON p.clave = d.producto 
			   where d.folio = '$comanda' and d.cuenta = '$cuenta' and d.status not like 'C'";
	  }		   

   }else{
      $sql="SELECT cd.producto,p.nombre,cd.precio,sum(cd.cantidad) as sum_cantidad, sum(cd.cantidad * cd.precio) as sum_importe
  	        FROM r_cheques AS ch INNER JOIN r_comandas AS c ON ch.comanda = c.folio
		    INNER JOIN r_comandas_detalles AS cd ON ch.comanda = cd.folio and cd.cuenta = ch.cuenta and cd.status not like 'C'
		    INNER JOIN r_productos as p ON cd.producto = p.clave
		    WHERE ch.id = '$id' 
		    GROUP BY cd.producto,p.nombre,cd.precio ORDER BY cd.producto";
		    
      if($famosa){		    
		$sql="select d.cuenta,d.cantidad as sum_cantidad,d.producto,p.nombre,(d.cantidad * d.precio) as sum_importe,d.status,d.motivo_can 
	           from r_comandas_detalles as d 
			   INNER JOIN r_productos as p ON p.clave = d.producto 
			   where d.folio = '$comanda' and d.cuenta = '$cuenta' and d.status not like 'C'";
	  }
		   	    
	}
  
   // echo $sql;   


   $result = pg_query($con,$sql);

   while ($row = pg_fetch_array($result)){
      $clave   = $row["producto"];
      $nombre  = utf8_decode($row["nombre"]);
      $cantidad  = $row["sum_cantidad"];
      $precio    = $row["precio"];
      $importe = $row["sum_importe"];
      
      $nombre = substr($nombre,0,20); 


      $cantidad = number_format($cantidad,3,".",",");      
      $precio   = number_format($precio,2,".",",");
      $importe  = number_format($importe,2,".",",");      

	  $cantidad = str_pad($cantidad, 6, " ", STR_PAD_LEFT);
	  $precio   = str_pad($precio, 12, " ", STR_PAD_LEFT);
	  $importe  = str_pad($importe, 12, " ", STR_PAD_LEFT);
            
	  $nombre  = str_pad($nombre, 19, " ", STR_PAD_RIGHT);
      $salida = $cantidad." ".$nombre." ".$importe;
	  $contenido .= $salida."\n";
   }

   $contenido .= "\n";
   
   if ($descuento > 0) {   
      $subtotal = number_format($subtotal,2,".",",");
	  $subtotal = str_pad($subtotal, 12, " ", STR_PAD_LEFT);

      $descuento = number_format($descuento,2,".",",");
	  $descuento = str_pad($descuento, 12, " ", STR_PAD_LEFT);

      $salida = "                  SUBTOTAL: ".$subtotal;      
	  $contenido .= $salida."\n";
      $salida = "                 DESCUENTO: ".$descuento;      
	  $contenido .= $salida."\n";

   }   

   if ($incluyeProp == "SI") {      
      $propina = round($total * .10);
   } else {   
      $propina = 0;
   }   

   $importeletra=strtoupper(cantidadletra($total+$propina,"PESOS",0,"M.N."));

   $importeletra = "(".$importeletra.")";

    if ($incluyeProp == "SI") {    
      // Si hubo descuento ya se mostro el subtotal.     
      // Si no hubo descuento mostramos el subtotal.
      if ($descuento == 0) {      
         $subtotal = number_format($total,2,".",",");
	     $subtotal = str_pad($subtotal, 12, " ", STR_PAD_LEFT);
         $salida = "                  SUBTOTAL: ".$subtotal;      
	     $contenido .= $salida."\n";
      }      

      $propinaI = number_format($propina,2,".",",");
      $propinaI = str_pad($propinaI, 12, " ", STR_PAD_LEFT);
      $salida = "                  SERVICIO: ".$propinaI;         

      $contenido .= $salida."\n";      

   }   

   $totalI = number_format($total+$propina,2,".",",");
   $totalI = str_pad($totalI, 12, " ", STR_PAD_LEFT);
   $personas = str_pad($personas, 3, " ", STR_PAD_LEFT);
   $salida = "Personas: ".$personas;
   // $salida = chr(27)."!".chr(49)."TOTAL: ".$totalI.chr(27)."!".chr(1);         // Imprimir a doble ancho.
   $salida .= "TOTAL: ".$totalI;         
   $contenido .= $salida."\n\n";


   // Imprimir la cantidad con letra, si es necesario se divide en mas de un renglon.
   while (strlen($importeletra) > 0) {
         $remanente = "";
         while (strlen($importeletra) > 40) {
             $ind = strrpos($importeletra," ",0);
             if ($ind === false) {
                break;
             } else {
                $remanente = substr($importeletra,$ind).$remanente;
                $importeletra = substr($importeletra,0,$ind);
             }
         }
         $contenido .= $importeletra."\n";
         $importeletra = $remanente;
   }


   // Crear pie de pagina.
   $sql2 = "SELECT * FROM ticket_conf WHERE seccion = 'P' ORDER BY renglon,columna";
   $result2 = pg_query($con,$sql2) or die (pg_last_error($con));   

   $renglon_prev = "";
   $salida = str_repeat(" ",40);   
   while ($row2 = pg_fetch_array($result2)) {
      $renglon = $row2["renglon"];      
      $col     = $row2["columna"];      
      $campo   = $row2["campo"];      
      if ($campo == -1) {
         $datos   = $row2["datos"];         
      } else {
         $datos   = $pie[$campo];         
      }

      if ($renglon_prev == "")      
         $renglon_prev = $renglon;         
      else if ($renglon_prev != $renglon ) {      
         if (strlen($salida) > 40)         
            $salida = substr($salida,0,40);
		
		 // Enviar el buffer al archivo     
		 $contenido .= $salida."\n";
		   
         // Limpiar el buffer.
         $salida = str_repeat(" ",40);
         $renglon_prev = $renglon;
      }      

      $salida = substr_replace($salida,$datos,$col,strlen($datos)); 
   }

   if ($renglon_prev != "") {   
       if (strlen($salida) > 40)         
          $salida = substr($salida,0,40);

	   $contenido .= $salida."\n";

   }    

   // Imprimir los datos del cliente, cuando es servicio a domicilio.   
   if ($seccion == "DOM") {      
      $cliente = pg_este("select cliente from r_comandas_domicilio where comanda = '$comanda'","cliente",$con);      
      $observa = pg_este("select observa from r_comandas_domicilio where comanda = '$comanda'","observa",$con);      
      $sql = "SELECT nombre,direccion,exterior,interior,colonia,referencia,telefono FROM r_clientes WHERE clave = '$cliente'";      

      $result = pg_query($con,$sql);        
      $row = pg_fetch_object($result,0);

      $nombre  =$row->nombre;      
      $direccion  = $row->direccion;
      $exterior   = $row->exterior;      
      $interior   = $row->interior;
      $colonia    = $row->colonia;      
      $referencia = $row->referencia;      
      $telefono   = $row->telefono;
	  $domicilio  = $direccion." ".$exterior;    
      
      if ($interior != "")      
         $domicilio .= "-".$interior;
	  $contenido .= "\nCliente: ".$nombre."\n";
	  $contenido .= "Domicilio: ".$domicilio."\n";
	  $contenido .= "Colonia: ".$colonia."\n";
	  $contenido .= "Ref.: ".$referencia."\n";
	  $contenido .= "Telefono: ".$telefono."\n";
	  $contenido .= "Observaciones: \n";
	  $contenido .= $observa."\n";

   }



   return $contenido;

}


// Funcion para imprimir el ticket del lado del servidor.
function imprime_cheque($id,$impresora,$con,$no_copias) {

   if ($id == "") $id = -1;   

   if ($no_copias == "") $no_copias = 1;
   
   for ($copia = 1; $copia <= $no_copias; $copia++) {
      $contenido = contenido_cheque($id,$con);

      for ($x = 1; $x < 11; $x++)
         $contenido .= "\n";

      $cortar  = chr(27); 
      $cortar .= "i";
      $contenido .= $cortar;
       
      //$imche = gethostbyaddr($_SERVER['REMOTE_ADDR']);     
	  $imche = obten("estacion");

      // Si el nombre de la maquina contiene punto, se toma solo la parte izquierda del nombre. 
      $pos = strpos($imche,".");  
      if ($pos !== false)
         $imche = substr($imche,0,$pos); 

      //echo "Maquina: $imche <br>";
      $imche = strtoupper($imche); 
   
      // Si la impresora se recibio en los parametros, se conserva.
      if ($impresora != "") {
         $sufijo = "_2";
      } else {
         $impresora=pg_este("select valor from r_configuracion where clave like 'IMP' and nombre like '$imche'","valor",$con);
   	     $sufijo = "";  
      }
      $config_imp=file("rutita.txt");
      $prefijo_imp=$config_imp[0];

      $archivo_nomb = $prefijo_imp."cheque_$id".$sufijo."_".$copia; 
      $file=fopen($archivo_nomb.".dat","w");

      fwrite($file, $contenido);    
      fclose($file);


      //$x = exec("rawprint $impresora $archivo_nomb"); 

      //unlink($archivo_nomb);   

      $file=fopen($archivo_nomb.".prt","w");
      fwrite($file,$impresora."\n");

      fclose($file);
   }
}

function imprime_el_cheque($impresora_cheques,$con){
echo "

function formato_num(num) {
		num = num.toString().replace(/$|,/g,'');
		if(isNaN(num))
		num = \"0\";
		sign = (num == (num = Math.abs(num)));
		num = Math.floor(num*100+0.50000000001);
		cents = num%100;
		num = Math.floor(num/100).toString();
		if(cents<10)
		cents = \"0\" + cents;
		for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
		num = num.substring(0,num.length-(4*i+3))+
		num.substring(num.length-(4*i+3));
		return (((sign)?'':'-') + num + '.' + cents);
}

function imprime_el_cheque(folio,seccion,mesa,mesero,cantidades,productos,precios,descuento,d_cliente,d_direccion){
}

";
}

function abre_gaveta($con) {

   //$maquina = gethostbyaddr($_SERVER['REMOTE_ADDR']); 
   $maquina=obten("estacion_jefazo");    

   //echo "Maquina: $imche <br>";
   $maquina = strtoupper($maquina); 
   $impresora=pg_este("select valor from r_configuracion where clave like 'IMP' and nombre like '$maquina'","valor",$con);

   $archivo_nomb = "c:\\Medisa\\gaveta"; 
   $file=fopen($archivo_nomb.".dat","w");
   $cajon  = chr(27)."p".chr(0).chr(60).chr(240);
   fwrite($file,$cajon);  // Enviar pulso a la gaveta.   

   fclose($file);


   //$x = exec("rawprint $impresora $archivo_nomb"); 

   //unlink($archivo_nomb);   

   $file=fopen($archivo_nomb.".prt","w");
   fwrite($file,$impresora."\n");

   fclose($file);   

}

function guarda_comanda($con,$imprime){
 $LogDesc="";
 $LogSQL="";

 $doble_ancho = "SI";  // SI o NO
 $arre=NULL;
 $i=0;
 $result=pg_query($con,"select '\\\\\\\\'||maquina||'\\\\'||impresora as printer from  r_printers ");
 while ($row = pg_fetch_array($result)){
		$arre[$i]["impresora"]=$row["printer"];
		$arre[$i]["texto"]="";
		$i++;
 } 

 pg_query($con,"BEGIN");
  
 $impresora_esp = "\\\\estacion8\\cocinaf";
 $impresora_esp = "";
  
 $maquina = gethostbyaddr($_SERVER['REMOTE_ADDR']);     
 $maquina = strtoupper($maquina); 

 $folio=obten("mi_folio_comanda");


 $personas_por_mesa=obten("mi_personas_por_mesa");
 
 if($folio==""){
    $existia="nel";
	$sql="select valor from r_configuracion where clave like 'COM' FOR UPDATE";
 	$folio=pg_este($sql,"valor",$con);
	$LogSQL.="$sql <br>";
 }else{
   $sql="delete from r_comandas_domicilio where comanda = '$folio'";
   pg_query($con,$sql) or die (pg_last_error($con));
   $existia="simon";
   $LogSQL.="$sql <br>";
 }
 
 $fecha=date("Y/m/d");
 $hora=date("H:i:s");
 $mesa=obten("mi_mesa_comanda");
 $seccion=obten("mi_seccion_comanda");
 $mesero=obten("mi_mesero_comanda");
 $personas=$personas_por_mesa;
 $status="A";
 
 pg_query($con,"update r_mesas set bloqueado=now() where clave like '$mesa' and seccion like '$seccion' ");
 
 $lista=obten("mi_lista_a_guardar");


    
 if($existia=="nel"){
 	$sql="insert into r_comandas values('$folio','$fecha','$hora','$mesa','$seccion','$mesero','$personas','$status','1','$maquina')";
 	pg_query($con,$sql);
	$LogSQL.="$sql <br>";
 	
    $nuevo_folio=$folio+1;
    $sql="update r_configuracion set valor='$nuevo_folio' where clave like 'COM'";
    $Result1 = pg_query($con,$sql) or $errores="simon";
	$LogSQL.="$sql <br>";
 	
 }else{
   $maquina=pg_este("select maquina from r_comandas where folio = '$folio'","maquina",$con);
   $maquina = strtoupper($maquina); 
 }


$LogDesc.="SECCION: $seccion, MESA: $mesa, MESERO: $mesero <BR>PRODUCTOS:<BR>";
 
 $sql="delete from r_comandas_detalles where folio = '$folio'";
 pg_query($con,$sql);
 $LogSQL.="$sql <br>";
 
 $lista=explode("|",$lista);
 $j=count($lista);
 

 $imprimo_comanda="simon";
 $cuenta_comanda = "";
 
 for($i=0;$i<$j-1;$i++){

    $l=explode("-=-",$lista[$i]);    

  
    $cuenta=$l[5];
    $cantidad=$l[0];
    $producto=$l[1];
    $precio=$l[2];
    $imp1="";
    $imp1_porciento=0;
    $imp2="";
    $imp2_porciento=0;
    $status=$l[6];
    $motivo=$l[7];    
    $hora=$l[8];
    $nombre=utf8_decode($l[9]);
    $comentario=$l[11];
	
	// checa si es una cortesia
	$cortesia="N";
	if($precio=="0"){
		$elprecio1=pg_este("select precio1 from r_productos where clave='$producto'",'precio1',$con);
		if($elprecio1 > 0)
		   $cortesia="S";
	}
	//-------------------------
	

    $cantidad=number_format($cantidad,3,".","");  

    if ($cuenta_comanda == "") $cuenta_comanda = $cuenta;
    if($modificador=="undefined")$modificador="";
    
    $tipo=$l[4];
    $linea=pg_este("select linea from r_productos where clave like '$producto'","linea",$con);
    if($producto!="---"){
	    if (($seccion == "DOM") && ($impresora_esp != "")) {
		   $impresora = $impresora_esp;
		} else { 
	       $impresora=pg_este("select impresora from r_lineas_impresoras where maquina like '$maquina' and linea like '$linea'","impresora",$con);
	       
		    // Checar si se sustituye la impresora.    
		   $dia_sem = date("w");   
		   $impresora_b = strtoupper($impresora);   
		
		   //$impresora_b = str_replace("\\","\\\\",$impresora_b);
		   $sql = "select sustituto from r_sustitucion where upper(maquina) = '$maquina' and dia = '$dia_sem' and upper(original) = '$impresora_b' and 'now' >= hora_ini and 'now' <= hora_fin and tipo = 'I'";   
		
		   $sustituto=pg_este($sql,"sustituto",$con);
		
		   if ($sustituto != "")   
		      $impresora = $sustituto;
	      // -----------------------------------------
	      
		   // ---- sustitucion areas ------
		
		   $sql = "select area from r_sustitucion where upper(maquina) = '$maquina' and dia = '$dia_sem' and 'now' >= hora_ini and 'now' <= hora_fin and tipo = 'A'";   
		   $sustituto=pg_este($sql,"area",$con);
		
		   if ($sustituto != ""){   
		      pg_query($con,"update r_comandas set area='$sustituto' where folio like '$folio'");
		   }   
		   // ------------------------------
	      
		}
		
	}else{
	  $cantidad="";
	  $nombre="-----------------------------------";
	  $comentario="";
	}    

    //echo "select linea from r_productos where clave like '$producto'";
        //echo "$linea - $nombre- $impresora - $tipo<br>";
    

	if($producto!="---"){
 		$sql="insert into r_comandas_detalles 
		 (folio,cuenta,hora,cantidad,producto,precio,imp1,imp1_porciento,imp2,imp2_porciento,status,motivo_can,cortesia)
		 values('$folio','$cuenta','$hora','$cantidad','$producto','$precio','$imp1','$imp1_porciento','$imp2','$imp2_porciento','$status','$motivo','$cortesia')";

 		$sql="insert into r_comandas_detalles 
		 (folio,cuenta,hora,cantidad,producto,precio,imp1,imp1_porciento,imp2,imp2_porciento,status,motivo_can)
		 values('$folio','$cuenta','$hora','$cantidad','$producto','$precio','$imp1','$imp1_porciento','$imp2','$imp2_porciento','$status','$motivo')";
		pg_query($con,$sql)	or $imprimo_comanda="nel";
		$LogSQL.="$sql <br>";
		
		$LogDesc.="CUENTA:$cuenta, $cantidad - $nombre $ $precio, ST: $status<BR>";
	}
    if($tipo=="" and $imprimo_comanda=="simon"){
       for($x=0;$x<count($arre);$x++){
          // echo $arre[$x]["impresora"]." - ".$impresora."<br>";
          if($arre[$x]["impresora"]==$impresora){
					  $arre[$x]["texto"].="$cantidad $nombre - $comentario \n";
                                          //echo "$cantidad $nombre - $comentario <br>";
                                          //echo $arre[$x]["texto"]."$x <br>";
                                        }
			 }
		}


 	// codigo log
    $fecha_log=date("d/m/Y H:i:s");
    $cad= "$maquina_log - $fecha_log ---- $sql \r\n";
    fwrite($fp, $cad);
    //--------------------

 	//$sql="insert into r_comandas_detalles values('$folio','$cuenta','$hora','$cantidad','$producto','$precio','$imp1','$imp1_porciento','$imp2','$imp2_porciento','$status','$motivo','$modificador')";
 	//echo "$sql<br>";
 }
 
 fclose($fp);//codigo log

 $_SESSION["la_cuenta_comanda"]=$cuenta_comanda;  
 $_SESSION["descuento_comanda"]=0;  
 $_SESSION["folio_comanda"]=$folio;  


 $mesero_nombre=pg_este("select nombre from r_meseros where clave like '$mesero'","nombre",$con);
 $fecha_=invierte_fecha($fecha);
 $titulos_comanda="Fecha: $fecha_        Hora: $hora \n\n";
 $titulos_comanda.="Mesero: $mesero  $mesero_nombre\n";
 $titulos_comanda.="Seccion: $seccion        Mesa: $mesa\n";
 $titulos_comanda.="========================================\n";

  
 if ($imprime != "NO" and $imprimo_comanda=="simon") { 
    $filecito=file("rutita.txt");
    $aqui_se_guardaran=$filecito[0];
    for($x=0;$x<count($arre);$x++){
   
       if($arre[$x]["texto"]!=""){
	   		$seconds = date("s");
			$archivo_nomb=$aqui_se_guardaran."comandita_$folio"."_".$seconds."$x.dat";			
 			$file=fopen($archivo_nomb,"w"); 
            //fwrite($file,chr(27)."g".chr(1));
            fwrite($file,chr(27)."t".chr(16));  // Pagina de caracteres Latin-1
 			fwrite($file,$titulos_comanda);  
 			fwrite($file,$arre[$x]["texto"]);  
 			fwrite($file,"\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n"); 
		            $cortar  = chr(27); 
		            $cortar .= "i";
		            fwrite($file, $cortar);    

 			fclose($file);
 			
			$archivo_nomb=$aqui_se_guardaran."comandita_$folio"."_".$seconds."$x.prt";			
 			$file=fopen($archivo_nomb,"w");
 			$la_impre=$arre[$x]["impresora"];
 			fwrite($file,$la_impre);  

 			fwrite($file,"\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n"); 
 			fclose($file);
 		}
    }
    //$x = exec("rawprint \\\\eridanus\\ticket $archivo_nomb"); 
    //unlink($archivo_nomb);   
  }


 
  if($seccion=="DOM"){


   	pg_query($con,"delete from r_comandas_domicilio where comanda = '$folio'");   


    $clienton=obten("mi_cliente_domicilio");      
    $zona=obten("zona");
	  $sucursal = pg_este("select sucursal from cc_zonas where clave like '$zona'","sucursal",$con);
    $status = obten("status");
    $observa = obten("observa");
    if ($imprime != "NO")    
       $status = "H";       

    $syn="N";
    $mi_sucursal = pg_este("select valor from r_configuracion where clave like 'SUC'","valor",$con);   
    if($mi_sucursal!=$sucursal) $syn="S";
    
   	$sql="insert into r_comandas_domicilio values('$folio','$clienton','$sucursal','$status','$syn','$observa')";
 	  pg_query($con,$sql);
 }  

 $hay_activos=pg_este("select folio from r_comandas_detalles where folio = '$folio' and status like 'A'","folio",$con);
 $hay_encheque=pg_este("select folio from r_comandas_detalles where folio = '$folio' and status like 'H'","folio",$con);
 $cheque_pendiente=pg_este("select folio from r_cheques where comanda = '$folio' and status like 'A'","folio",$con);
 $cheque_pagado=pg_este("select folio from r_cheques where comanda = '$folio' and status like 'P'","folio",$con);
 $cheque_cancelado=pg_este("select folio from r_cheques where comanda = '$folio' and status like 'C'","folio",$con);
 
 if($hay_activos==""){
	 if ($hay_encheque==""){    
	       pg_query($con,"update r_comandas set status='C' where folio = '$folio'");
		   $LogSQL.="update r_comandas set status='C' where folio = '$folio' <br>";
	 }else{
	      if( $cheque_pendiente==""){
	         pg_query($con,"update r_comandas set status='P' where folio = '$folio'");
			 $LogSQL.="update r_comandas set status='P' where folio = '$folio' <br>";
	      }else{
	         pg_query($con,"update r_comandas set status='H' where folio = '$folio'");
			 $LogSQL.="update r_comandas set status='H' where folio = '$folio'<br>";
		  }
		}
		if($cheque_pendiente=="" and $cheque_pagado=="" and $cheque_cancelado!=""){
		  pg_query($con,"update r_comandas set status='C' where folio = '$folio'");
		  $LogSQL.="update r_comandas set status='C' where folio = '$folio'<br>";
		}
  }	

  pg_query($con,"COMMIT");
  
  $LogOperacion="COM";
  
  // No funciona el log.
  //guardaLOG($LogOperacion, $LogDesc, $LogSQL);  
  
  //echo "cargado=SI&otro=no";
  return "cargado=SI&otro=no";
}

// Generar el cheque 
function cheque($con){
  $la_cuenta=obten("la_cuenta_comanda"); 
  $descuento=obten("descuento_comanda"); 
  if($descuento=="")$descuento=0;
  $folio=obten("folio_comanda"); 

  $error="nel";
  pg_query($con,"Begin");
  
  $sql="update r_comandas_detalles set status='H',cuenta_original=cuenta where folio = '$folio' and status not like 'C' and cuenta = '$la_cuenta'";
  pg_query($con,$sql) or $error="simon";
  
  $hay_activos=pg_este("select count(folio) as cuantos from r_comandas_detalles where folio = '$folio' and status like 'A'","cuantos",$con);
  if($hay_activos == "" or $hay_activos == 0){
  	$sql="update r_comandas set status='H' where folio = '$folio'";
  	$result= pg_query($con,$sql) or $error="simon";
  }	

	$sql="select * from r_comandas_detalles where folio = '$folio' and status not like 'C' and cuenta = '$la_cuenta'";
	//echo $sql;
  $result= pg_query($con,$sql);
	$cuenta_ant=0;  
	$total_cuenta=0;
  while($row=pg_fetch_array($result)){
    $cuenta=$row["cuenta"];
    $cantidad=$row["cantidad"];
    $producto=$row["producto"];
    $precio=$row["precio"];
    $total=$cantidad*$precio;
   
    $total_cuenta+=$total;
    //echo "$cuenta - $cantidad - $producto - $precio - $total<br>";    
		
		$cuenta_ant=$cuenta;
  }

    //echo "$cuenta_ant -  $cuenta <br>" ;
    /*
    if($cuenta_ant != $cuenta && $cuenta_ant !=0){
      $id=pg_este("select max(id)+1 as nuevo_id from r_cheques","nuevo_id",$con); if($id=="")$id=1;
      $folio_cheque=pg_este("select valor from r_configuracion where clave like 'CHE'","valor",$con);
			$hora=date("H:i:s");
			$caja=-1;
			$subtotal=$total_cuenta;
			$total_=$total_cuenta-($total_cuenta*($descuento/100));
			$status='A';
			$fechita=date("Y/m/d");

      $sql="insert into r_cheques values('$id','$folio_cheque','$hora','$caja','$folio','$cuenta_ant','','$subtotal','$descuento','$total_','$status','$fechita')";
      //echo "$sql<br>";
      pg_query($con,$sql) or die (pg_last_error($con));
			//echo "<b>TOTAL CUENTA ($cuenta_ant) : $total_cuenta</b><br><br>";
      $total_cuenta=0;
		  
		  $folio_cheque++;
			$sql="update r_configuracion set valor='$folio_cheque' where clave like 'CHE'";
			//echo $sql;
  		pg_query($con,$sql);  
        imprime_cheque($id,"",$con);
  	}
  	*/

  $id=pg_este("select max(id)+1 as nuevo_id from r_cheques","nuevo_id",$con);
  if($id=="")$id=1;
  $folio_cheque=pg_este("select valor from r_configuracion where clave like 'CHE'","valor",$con);
	$hora=date("H:i:s");
	$caja=-1;
	$subtotal=$total_cuenta;
	$total_=$total_cuenta-($total_cuenta*($descuento/100));
	$status='A';
  $fechita=date("Y/m/d");
  
  $sql="insert into r_cheques values('$id','$folio_cheque','$hora','$caja','$folio','$cuenta_ant','','$subtotal','$descuento','$total_','$status','$fechita')";
  //echo "$sql<br>";
  $result= pg_query($con,$sql) or $error="simon";
	//echo "<b>TOTAL CUENTA ($cuenta_ant) : $total_cuenta</b><br><br>";
  $total_cuenta=0;
  

  $folio_cheque++;
	$sql="update r_configuracion set valor='$folio_cheque' where clave like 'CHE'";
	$result= pg_query($con,$sql) or $error="simon";

  if($error=="nel"){
    pg_query($con,"Commit");
	$copias=pg_este("select valor from r_configuracion where clave like 'CTI'","valor",$con);   
	$copias+=1;
  	imprime_cheque($id,"",$con,$copias);
  	//imprime_cheque($id,"especial",$con); // Imprimir el cheque en la segunda impresora.

  }else{
    pg_query($con,"Rollback");
  }

   return $id;  // Regresar el id del cheque.
}

function movtos_es($fecha,$hora,$tipo_mov,$referencia,$observacion,$almacen,$tipo,$con){
  $modifico=obten("login_jefazo");
  $aplico="N";
  $id=pg_este("select max(id)+1 as maximo from r_movimientos_es","maximo",$con);
  if ($id=='')$id=1;

  $folio=pg_este("select folio from r_tipos_movimientos_es where clave like '$tipo_mov'","folio",$con);

  $sql="insert into r_movimientos_es (id,tipo,folio,movimiento,almacen,proveedor,referencia_documento,referencia_observacion,fecha,hora,modifico,status)
	       values('$id','$tipo','$folio','$tipo_mov','$almacen','','$referencia','$observacion','$fecha','$hora','$modifico','A')";
  
  //echo "$sql<br>";
  pg_query($con,$sql);
  $folio++;
  pg_query($con,"update r_tipos_movimientos_es set folio = '$folio' where clave like '$tipo_mov'");
  
  return $id;
}

function movtos_es_detalles($id,$almacen,$producto,$cantidad,$con){
  $tipo=pg_este("select tipo from r_movimientos_es where id = '$id'","tipo",$con);
  //echo "select tipo from movimientos_es where id like '$id'";
  $j=count($producto);
  for($i=0;$i<$j;$i++){
		$clave = $producto[$i];
		$registro = pg_estos("select costo,impuesto1,impuesto2 from r_productos where clave = '$clave'",$con);
		if ($registro[0] == "ok") {
			$costo = $registro[1];
			$imp1 = $registro[2];
			$imp2 = $registro[3];
			$imp1_tasa=pg_este("select impuesto from r_impuestos where clave = '$imp1'","impuesto",$con);
			$imp2_tasa=pg_este("select impuesto from r_impuestos where clave = '$imp2'","impuesto",$con);
			if ($imp1_tasa == "") $imp1_tasa = 0;
			if ($imp2_tasa == "") $imp2_tasa = 0;
		} else {
			$costo = 0;
			$imp1 = '';
			$imp2 = '';
			$imp1_tasa = 0;
			$imp2_tasa = 0;		
		}  
	  $sql="insert into r_movimientos_es_detalles (id,producto,cantidad,precio,descuento,imp1,imp2,imp1_valor,imp2_valor)
		       values('$id','$producto[$i]','$cantidad[$i]',$costo,'0','$imp1','$imp2',$imp1_tasa,$imp2_tasa)";
  
  	//echo "$sql<br>";
  	pg_query($con,$sql);
  	
  	//echo "<br> update productos_almacenes set existencia_actual = existencia_actual + $cantidad[$i] where producto like '$producto[$i]' and almacen like '$almacen'";
  	if($tipo=="E"){
  	//echo "E<br>";
  		pg_query($con,"update r_productos_almacenes set existencia = existencia + $cantidad[$i] where producto like '$producto[$i]' and almacen like '$almacen'");
    }		
  	if($tipo=="S"){
  	//echo "S<br>";
  		pg_query($con,"update r_productos_almacenes set existencia = existencia - $cantidad[$i] where producto like '$producto[$i]' and almacen like '$almacen'");
    }		
  	
	}
}

// Obtener el costo de la receta.
function costo_receta($receta,$alm,$con) {

   $costo_total = 0;
   $costo_totalI = 0;
   $tiene_ingredientes=pg_este("select tiene_ingredientes from r_productos where clave = '$receta'","tiene_ingredientes",$con);

   if ($tiene_ingredientes != "SI") {
      // Obtener el costo de la tabla de existencias
	  $costos = pg_estos("select costo,costoimp from r_productos_almacenes where producto = '$receta' and almacen='$alm'",$con);
	  if ($costos[0] == "ok") {
	     $costo = $costos[1];
		 $costoimp = $costos[2];
	  } else {
	  	$costo = 0;
		  $costoimp = 0;
	  }
      //echo "Costo: ".$costo."<br>";	  
	  return array($costo,$costoimp);   
   } else {
	  $sql="select * from r_recetas where producto = '$receta'";
	  $result = pg_query($con,$sql) or die(pg_last_error($con));

      $costo_total = 0;
	  while ($row = pg_fetch_array($result)){
	     $ingrediente = $row["ingrediente"];
		 $cantidad    = $row["cantidad"];
		 $alm         = $row["almacen"];
	     $porciones=pg_este("select porciones from r_productos where clave = '$ingrediente'","porciones",$con);
	    
		 if($porciones>0 ){
		    $la_cantidad = ($cantidad / $porciones);
		 }else{
		    $la_cantidad=0;
		 }

		 if($ingrediente !== $receta) {
			$costo = costo_receta($ingrediente,$alm,$con);
			$costo_total += $costo[0] * $la_cantidad;
			$costo_totalI += $costo[1] * $la_cantidad;

		 }	
	  }
	  return array($costo_total,$costo_totalI);
   } 
}

// Costear las recetas que utilizan los productos que vienen en el arreglo productos.
function costear_recetas($productos,$alm,$con,$nivel) {
   if ($nivel > 5) return;
   $c = count($productos);
   for ($x = 0; $x < $c; $x++) {
	  $clave = $productos[$x];
      $sql = "select producto,almacen from r_recetas where ingrediente = '$clave'";
	  //echo "Nivel: ".$nivel." ".$sql."<br>";
	  
      $result = pg_query($con,$sql);        
	  // Buscar las recetas del producto.
	  if ($nivel > 1) {

         $costo_receta = costo_receta($clave,$alm,$con);
      
         // Actualizar el catalogo de productos, con el nuevo costo.
         $sql = "UPDATE r_productos SET costo = ".$costo_receta[0].", costoimp = ".$costo_receta[1]." WHERE clave = '$clave'";
		 //echo "Nivel: ".$nivel." ".$sql."<br>";
         pg_query($con,$sql);
         $sql = "UPDATE r_productos_almacenes SET costo = ".$costo_receta[0].", costoimp = ".$costo_receta[1]." WHERE producto = '$clave'";
         pg_query($con,$sql);	  
	  }
      while($row=pg_fetch_array($result)){			             
         $clave=$row["producto"];
		 $almacen=$row["almacen"];
	     //echo $nivel."Clave: ".$clave."<br>";
         $costo_receta = costo_receta($clave,$almacen,$con);
      
         // Actualizar el catalogo de productos, con el nuevo costo.
         $sql = "UPDATE r_productos SET costo = ".$costo_receta[0].", costoimp = ".$costo_receta[1]." WHERE clave = '$clave'";
		 //echo "Nivel: ".$nivel." ".$sql."<br>";
         pg_query($con,$sql);
         $sql = "UPDATE r_productos_almacenes SET costo = ".$costo_receta[0].", costoimp = ".$costo_receta[1]." WHERE producto = '$clave'";
         pg_query($con,$sql);

         // Si hay recetas que contienen este ingrediente, tambien se recostean de forma recursiva.
		 $sql = "SELECT producto FROM r_recetas WHERE ingrediente = '$clave'";
		 $result2 = pg_query($con, $sql);
		 $productos2 = array();
		 $cont = 0;
		 while ($row2=pg_fetch_array($result2)) {
		    $clave = $row2["producto"];
			
		    array_push($productos2,$clave);
			$cont++;
		 }
		 if ($cont > 0) {
		    costear_recetas($productos2,$almacen,$con,$nivel+1);
		 }
      }	
	    
   }
}


function insumos($receta,$cantidad,$almacen,$con){
  $retorno="";

  $tiene_ingredientes=pg_este("select tiene_ingredientes from r_productos where clave like '$receta'","tiene_ingredientes",$con);
        
  if($tiene_ingredientes != "SI"){
    $retorno.="$receta,$cantidad,$almacen|";
  }else{
	  $sql="select * from r_recetas where producto like '$receta'";
	  $result = pg_query($con,$sql) or die(pg_last_error($con));
	  while ($row = pg_fetch_array($result)){
	    $ingrediente=$row["ingrediente"];
	    $cant=$row["cantidad"];
	    $alm=$row["almacen"];
	    $porciones=pg_este("select porciones from r_productos where clave like '$ingrediente'","porciones",$con);
	    
			if($porciones>0 ){
			    $la_cantidad=$cantidad * ($cant / $porciones);
			}else{
			    $la_cantidad=0;
			}
			
			if($ingrediente !== $receta)
			  $retorno.=insumos($ingrediente,$la_cantidad,$alm,$con);
	  }
	}
  return $retorno;
}

function insumosM($receta,$cantidad,$almacen,$tiene_ingredientes,$con){
  $retorno="";
  
  if($tiene_ingredientes != "SI"){
    $retorno.="$receta,$cantidad,$almacen|";
	}else{
	  $sql="select * from r_recetasm where modificador like '$receta'";
	  //echo $sql;
	  $result = pg_query($con,$sql) or die(pg_last_error($con));
	  while ($row = pg_fetch_array($result)){
	    $ingrediente=$row["ingrediente"];
	    $cant=$row["cantidad"];
	    $alm=$row["almacen"];
	    $porciones=pg_este("select porciones from r_productos where clave like '$ingrediente'","porciones",$con);
	    
			if($porciones>0 ){
			    $la_cantidad=$cantidad * ($cant / $porciones);
			}else{
			    $la_cantidad=0;
			}
			$retorno.=insumos($ingrediente,$la_cantidad,$alm,$con);
	  }
	}
  return $retorno;
}

function hoy($esto){
   $factor=7200;

   $dia=date("d",mktime(date("H,i,s,m,d,y"))+$factor);
   $mes=date("m",mktime(date("H,i,s,m,d,y"))+$factor);
   $anio=date("Y",mktime(date("H,i,s,m,d,y"))+$factor);


   if($esto=="dia"){$retorno=$dia;}
   if($esto=="mes"){$retorno=$mes;}
   if($esto=="anio"){$retorno=$anio;}
   if($esto=="fecha"){$retorno="$anio-$mes-$dia";}

   if($esto=="semana"){
      $semana=date("D",mktime(date("H,i,s,m,d,y"))+$factor) ;
      if($semana == "Mon"){$semana = "Lunes ";}
      if($semana == "Tue"){$semana = "Martes ";}
      if($semana == "Wed"){$semana = "Mi&eacute;rcoles ";}
      if($semana == "Thu"){$semana = "Jueves ";}
      if($semana == "Fri"){$semana = "Viernes ";}
      if($semana == "Sat"){$semana = "S&aacute;bado ";}
      if($semana == "Sun"){$semana = "Domingo ";}
      $retorno=$semana;
   }

   if($esto=="nombre_mes"){
      if($mes == "1"){$mes = "Enero";}
      if($mes == "2"){$mes = "Febrero";}
      if($mes == "3"){$mes = "Marzo";}
      if($mes == "4"){$mes = "Abril";}
      if($mes == "5"){$mes = "Mayo";}
      if($mes == "6"){$mes = "Junio";}
      if($mes == "7"){$mes = "Julio";}
      if($mes == "8"){$mes = "Agosto";}
      if($mes == "9"){$mes = "Septiembre";}
      if($mes == "10"){$mes = "Octubre";}
      if($mes == "11"){$mes = "Noviembre";}
      if($mes == "12"){$mes = "Diciembre";}
      $retorno=$mes;
   }

 
  return $retorno;
}




function obten($cad){
  if(isset($_GET[$cad])){
    $retorno=$_GET[$cad];
  }else{
    if(isset($_POST[$cad])){
      $retorno=$_POST[$cad];
    }else{
      if(isset($_SESSION[$cad])){
        $retorno=$_SESSION[$cad];
      }else{
        $retorno="";
      }
    }
  }
  return $retorno;
}



function obten_save($este){
if(isset($_GET[$este])){
  $aux=$_GET[$este];
}else{
 if(isset($_POST[$este])){
    $aux=$_POST[$este];
  }else{
    if(isset($_SESSION[$este])){
      $aux=$_SESSION[$este];
    }else{
      $aux='';
    }
  }
}
  $_SESSION[$este]=$aux;
  return $aux;
}

function modifica_estos($con,$tabla,$id,$cadena){
 $retorno="vientos";
 $j=count($cadena);
 for($i=0;$i<$j;$i++){
    $valorzote=$cadena[$i]["valor"];
    //$valorzote=str_replace("\"","",$valorzote);
    $valorzote=str_replace("'","",$valorzote);
    //$valorzote=str_replace("\\","",$valorzote);
    $sql="UPDATE $tabla set ".$cadena[$i]["campo"]." = '".$valorzote."' where id = '$id'; ";
    //echo "$sql<br>";
    pg_query($con, $sql) or $retorno="madres";
    if($retorno=="madres")break;
 }
 return $retorno;
}

function inserta_estos($con,$tabla,$cadena){
 $retorno="vientos";
 $j=count($cadena);
 $nombres="";
 $valores="";
 for($i=0;$i<$j;$i++){
    $valorzote=$cadena[$i]["valor"];
    //$valorzote=str_replace("\"","",$valorzote);
    $valorzote=str_replace("'","",$valorzote);
    //$valorzote=str_replace("\\","",$valorzote);
    $nombres.=$cadena[$i]["campo"].",";
    $valores.="'".$valorzote."',";
 }
 $nombres=substr($nombres,0,strlen($nombres)-1);
 $valores=substr($valores,0,strlen($valores)-1);
 $id=pg_este("select max(id)+1 as maximo from $tabla","maximo",$con);
 if ($id=='')$id=1;
 $sql = "INSERT INTO $tabla (id,$nombres) VALUES ('$id',$valores)";
 //echo $sql;
 pg_query($con,$sql) or $retorno="madres";
 
 return $retorno;
}

function invierte_fecha($fechita){
    if($fechita!=""){
      if(strpos($fechita,'-')>0){
        $fecha=explode('-',$fechita);
      }else{
        $fecha=explode('/',$fechita);
      }
      $dia=$fecha[2];
      $mes=$fecha[1];
      $anio=$fecha[0];
      $fechita="$dia/$mes/$anio";
    }
  return $fechita;
}

function pg_este($sql,$campo,$con){
 $result = pg_query($con,$sql);
 if($row=pg_fetch_array($result)){
   $retorno=$row[$campo];
 }else{
   $retorno="";
 }
 return $retorno;
}

// Devuelve los campos encontrados en una consulta
function pg_estos($sql,$con){

 // La posicion cero contiene el status de la consulta
 // y los campos empiezan a partir de la posicion uno.
 $valores[0] = "error";
 
 $result = pg_query($con,$sql);
 if($row=pg_fetch_array($result,NULL,PGSQL_NUM)){
   $ind = 1;
   $valores[0] = "ok";
   
   for ($x = 0; $x < count($row); $x++) {
      $valores[$ind++]=$row[$x];
   }
 }

 return $valores;
}

function alerta($texto){
  $retorno="
     <script language=javascript>
       alert ('$texto')
     </script>
  ";
  return $retorno;
}

function alerta_bota($texto,$url){
  $retorno="
     <script language=javascript>
        alert ('$texto')
     </script>
    <META HTTP-EQUIV='Refresh' CONTENT='0;URL=$url'>
  ";
  return $retorno;
}

function indice($seccion,$max,$con){
  $result=mysql_query("SELECT n FROM indices where seccion like '$seccion'");
  if ($row = mysql_fetch_array($result)){
      $n=$row["n"];
  }
  $n++;
  if($n>$max)$n=0;
  mysql_query("UPDATE indices set n = $n WHERE seccion like '$seccion'");
  return $n;
}


function me_das_permiso($seccion){
  $retorno="nel";
  
  $permisos=obten("permisos_jefazo");
  if($permisos=="NUBE")
  	$retorno="simon";

  if($permisos=="")$permisos="|";
  $permisos=explode("|",$permisos);
  $j=count($permisos);

  for($i=0;$i<$j;$i++){
    if($seccion==$permisos[$i]){
      $retorno="simon";
      break;
    }
  }
  return $retorno;
}


function ultimo($tabla,$con){
  $sql="SELECT max(id) as ultimo from $tabla";
  $result = pg_query($con,$sql) or die (pg_last_error($con));
  if($row=pg_fetch_array($result)){
    $ultimo=$row["ultimo"];
  }else{
    $ultimo=0;
  }
  return $ultimo;
}


function sugiere_clave($tabla,$con){
  $retorno=pg_este("SELECT count(id) as cuantos from $tabla","cuantos",$con);
  $retorno=$retorno+1;
  return $retorno;
}

function denegado(){
  echo '
  <br><br><br><br>
   <center>
     <font face="verdana" color="#CC0000" size="5"><b>Acceso Denegado</b></font>
      <br><br>
     <font face="verdana" color="#000000" size="3"><b>Usted no tiene permiso para ingresar a esta sección</b></font>
      <br><br>
     <a href="principal.php"><font face="verdana" color="#CC0000" size="2"><b>Continuar</b></font></a>
   </center>
  ';
}

function dias_intermedios($fecha1,$fecha2){
  $fecha1=explode("/",$fecha1);
  $segundos1=mktime(0,0,0,$fecha1[1],$fecha1[0],$fecha1[2]);
  $fecha2=explode("/",$fecha2);
  $segundos2=mktime(0,0,0,$fecha2[1],$fecha2[0],$fecha2[2]);
//  echo "$segundos1<br>";
//  echo "$segundos2<br><br>";

  $ir=0;
  $segundos=$segundos1;
  while($segundos<=$segundos2){
//    echo "$segundos<br>";
    $dia=date("d/m/y",$segundos);
    $segundos+=86400;
    $retorno[$ir]=$dia;
    $ir++;
  }

  return $retorno;
}


function checa_dias($fecha1,$fecha2){
  $fecha1=explode("/",$fecha1);
  $segundos1=mktime(0,0,0,$fecha1[1],$fecha1[0],$fecha1[2]);
  $fecha2=explode("/",$fecha2);
  $segundos2=mktime(0,0,0,$fecha2[1],$fecha2[0],$fecha2[2]);

  if($segundos1>$segundos2)$retorno="1";
  if($segundos1<$segundos2)$retorno="2";
  if($segundos1==$segundos2)$retorno="0";

  return $retorno;
}


function dime_mes($i){
  $retorno="";
  if($i==1)$retorno="Enero";
  if($i==2)$retorno="Febrero";
  if($i==3)$retorno="Marzo";
  if($i==4)$retorno="Abril";
  if($i==5)$retorno="Mayo";
  if($i==6)$retorno="Junio";
  if($i==7)$retorno="Julio";
  if($i==8)$retorno="Agosto";
  if($i==9)$retorno="Septiembre";
  if($i==10)$retorno="Octubre";
  if($i==11)$retorno="Noviembre";
  if($i==12)$retorno="Diciembre";

  return $retorno;
}



function imprimeitor($titulo,$estos,$sql,$con){
	
  $e=explode("|",$estos);
  $c=count($e);
  $fecha_hora=date("d/m/Y  h:i:s");
  $retorno= "<title>:: MEDISA - CHEF Touch!  :: </title>";
  $retorno.= '
    <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
    <table width="95%" border="0" cellspacing="0" cellpadding="0" align="center">
      <tr>
        <td bgcolor="#CC0000" align="center" colspan="'.$c.'" height="40">
          <font face="Helvetica" size="2" color="#FFFFFF">
            <b>'.$titulo.'</b><br>
            '.strtoupper(pg_este("select nombre from r_empresa","nombre",$con)).'<br>
            CHEF - '.$fecha_hora.'
          </font>
        </td>
      </tr>
      <tr>
  ';
  $campos="";
  $alineado ="";  
  for($i=0;$i<$c;$i++){
    
    $l=explode("=",$e[$i]); 
    $campos.=$l[1];
    if($i<$c-1) $campos.=",";
    
    $este=$l[0];
    if(strpos($l[1],'numeric')>0) $alineado ="align='right'";
    //if($este=="Modifico") $alineado ="align='right'";
    //if($este=="Status") $alineado ="align='right'";
    
    $retorno.= "<td $alineado bgcolor='#F0F0F0'><font face='Helvetica' size='1' color='#000000'><b>$este</b></font></td>";
  }
  $retorno.= '
      </tr>
      <tr>
  ';
  $aux=explode("from",$sql);
  $resto_query=$aux[1];
  $sql="SELECT $campos from $resto_query";
  $sql=str_replace("+","|",$sql);
  //echo $sql;
  
  $result = pg_query($con,$sql) or die(pg_last_error($con));
  while ($row = pg_fetch_array($result)){
     $alineado ="";	
     for($i=0;$i<$c;$i++){
      $este=$e[$i];      
      $aux=explode("=",$este);
      $este=trim($aux[1]);

      if(strpos($este,'numeric')>0) $alineado ="align='right'";
      //if($aux[0]=="Modifico") $alineado ="align='right'";
      //if($aux[0]=="Status") $alineado ="align='right'";


      $aux=explode(" as ",$este);
      if(count($aux)>1)$este=trim($aux[1]);
      
      //echo "$este<br>";
      $aux=explode(".",$este);
      if(count($aux)>1)$este=$aux[1];
      			
      $tipo_dato = pg_field_type($result,$i);
      $campo_data=$row[$este];
      if($tipo_dato=="date")$campo_data=invierte_fecha($campo_data);
      if($tipo_dato=="float8") {
			   $campo_data=number_format($campo_data,2,".",",");     
         $alineado ="align='right'";
      }
      $retorno.= "<td $alineado width='$ancho_celda'><font face='Helvetica' size='1' color='#000000'>$campo_data</font></td>";
    }
  $retorno.= '
      </tr>
      <tr>
  ';
  }
  $retorno.= '
      </tr>
     </table>
  ';
   
  require_once("dompdf/dompdf_config.inc.php");
      
  $dompdf = new DOMPDF();
  $dompdf->load_html($retorno);
  $dompdf->render();
  $dompdf->stream("reporte_chef.pdf");

}

function lista_abonos(){
  return "Efectivo=EFEC|Cortesia=CORTESI|Deposito bancario=DEPOSIT|Cheque=CHEQUE|Tarjeta de Credito=TARJETA";
}

function dime_abono($este){
  $l=lista_abonos();
  $l=explode("|",$l);
  $cuantos=count($l);
  for($i=0;$i<$cuantos;$i++){
  	$a=explode("=",$l[$i]);
  	if($a[1]==$este){
  	  return $a[0];
  	}
  }
}


function ultimo_dia($anho,$mes){
   if (((fmod($anho,4)==0) and (fmod($anho,100)!=0)) or (fmod($anho,400)==0)) {
       $dias_febrero = 29;
   } else {
       $dias_febrero = 28;
   }
   switch($mes) {
       case 1: return 31; break;
       case 2: return $dias_febrero; break;
       case 3: return 31; break;
       case 4: return 30; break;
       case 5: return 31; break;
       case 6: return 30; break;
       case 7: return 31; break;
       case 8: return 31; break;
       case 9: return 30; break;
       case 10: return 31; break;
       case 11: return 30; break;
       case 12: return 31; break;
   }
}

function fechas_intermedias($entrada,$salida){
  $factor=86400;
  $factor=90000;
  $f=invierte_fecha($entrada);
  $s=invierte_fecha($salida);
  $retorno="";

 	$aux=invierte_fecha(str_replace('-0','-',$entrada));
	$retorno.="$aux|";
  
  //echo "$entrada - $salida";
  
  if($s[1]=="/")$s="0".$s;
  
  while($f!=$s){
		$f=explode("/",$f);
  	$f=date("Y/m/d",mktime(0,0,0,$f[1],$f[0],$f[2])+$factor);
  	
   	$aux=invierte_fecha(str_replace('/0','/',$f));
   	$f=invierte_fecha($f);
   	
   	if($aux[1]=="/")$aux="0".$aux;
   	
   	if($f!=$s){
   	  //echo "$s - $f<br>";
    	$retorno.="$aux|";
    }	
    //break;
    	
  }
  

  return $retorno;
}

// Redondear a multiplos de 5.
function round5($val) {
  return (int) 5 * round($val / 5);
}

function inserta_cadena($cad,$orig,$inicio,$frec) {

   
   $long = strlen($orig);
   
   if (($long <= $inicio) || ($frec < 1))
      return $orig;
	
   $temp = substr($orig,0,$inicio);	
   $ind = $inicio;	  
   while ($ind < $long) {
      $temp = $temp.$cad.substr($orig,$ind,$frec);
	  $ind += $frec;
   }
   return $temp;
}

function javita($que){
  if($que=="ini") echo'<script language="javascript">'."\n";
  if($que=="fin") echo'</script>'."\n";
}

function imp(){
 echo "var imp = new ActiveXObject('printeitor.imp');\n"; 
}

function imp_inicia($impresora,$alineacion,$tamanio){
 echo " imp.inicia('$impresora', '$alineacion', '$tamanio');\n"; 
}

function imp_fuente($fuente,$tamanio){
 echo " imp.fuente('$fuente',$tamanio);\n";  
}
  
function imp_cuadro($x1,$y1,$x2,$y2){
 echo " imp.cuadro($x1,$y1,$x2,$y2);\n";  
}
 
function imp_linea($x1,$y1,$x2,$y2){
 echo " imp.linea($x1,$y1,$x2,$y2);\n";  
}


function imp_escribe($x,$y,$texto,$alineacion){
 echo " imp.escribe($x,$y,'$texto','$alineacion');\n";
} 

function imp_imprime(){
	echo " imp.imprime();\n";
}
	
function imp_lista($formato){
	echo "document.write(imp.lista_impresoras($formato));\n";
}


function imprime_pdfGrupos($titulo,$subtitulo,$estos,$sql,$con,$grupos){

  require('fpdf/fpdf.php');
  $empresa=strtoupper(pg_este("select nombre from r_empresa","nombre",$con));
  $e=explode("|",$estos);
  $c=count($e);
  // Inicializar arreglo para totales.
  for($i=0;$i<$c;$i++){
     $parciales[$i] = 0;
     $totales[$i] = 0;
  }
  
  $documento = new FPDF('P','mm','Letter');
  $documento->AddPage();
  $documento->SetMargins(5,5,5);
  $documento->SetAutoPageBreak(false);
  $documento->SetFillColor(255,255,255);
  $documento->SetFont('Arial','',8);
	
  $campos="";
  for($i=0;$i<$c;$i++){

    $l=explode("=",$e[$i]); 
    $campos.=$l[1];
    if($i<$c-1) $campos.=",";
    
    $campo=$l[0];
    $ancho=$l[2];
    $alineado=$l[3];
    $labels[$i]= array($campo,$ancho,$alineado);
  }
  encabezado_pdf($documento,$labels,$titulo,$subtitulo,$empresa);
  
/*  
  $sql=strtoupper($sql);
  
  $aux=explode("FROM",$sql);
  $resto_query=$aux[1];
  $sql="SELECT $campos from $resto_query";
  $sql=str_replace("+","|",$sql);
*/

  //echo $sql;
  
  $result = pg_query($con,$sql) or die(pg_last_error($con));
  if ($subtitulo == "")
     $y=35;
  else
	   $y=40;   
  $l=0;
  $ant="";
  while ($row = pg_fetch_array($result)){
    //------------------------
    $cambio="No";
    if($ant!="" and $ant!= $row[$grupos]){
      $cambio="si";
		  // Imprimir parciales
		  $x=10;
		  for($i=0;$i<$c;$i++){
		      $este=$e[$i];      
		      $aux=explode("=",$este);
		      $este=trim($aux[1]);
		      $ancho_celda=$aux[2];
		      $alineado=$aux[3];
		      $sumar=$aux[4];
		      if ($sumar=="S") {
		         $campo_data=number_format($parciales[$i],2,'.',',');
		
		         $documento->SetXY($x,$y);
		         $documento->Cell($ancho_celda,4,$campo_data,"T",0,$alineado,true);
		      }
		      $x=$x+$ancho_celda+1;    
		  }
		  
		  for($i=0;$i<$c;$i++)
		     $parciales[$i] = 0;
		  $y+=6;
		  $l++;
		}
		$ant=$row[$grupos];
		//------------------------

     $x=10;
     for($i=0;$i<$c;$i++){
      $este=$e[$i];      
      $aux=explode("=",$este);
      $este=trim($aux[1]);
      $ancho_celda=$aux[2];
      $alineado=$aux[3];
      $sumar=$aux[4];

      if(strpos($este,'numeric')>0) $alineado ="R";
      //if($aux[0]=="Modifico") $alineado ="align='right'";
      //if($aux[0]=="Status") $alineado ="align='right'";

      $aux=explode(" as ",$este);
      if(count($aux)>1)$este=trim($aux[1]);
      
      //echo "$este<br>";
      $aux=explode(".",$este);
      if(count($aux)>1)$este=$aux[1];
      			
      
			if(strpos($_SERVER["PHP_SELF"], "clientes.php")>0 || strpos($_SERVER["PHP_SELF"], "proveedores.php")>0 || strpos($_SERVER["PHP_SELF"], "deposito_garantia.php")>0){
      		$tipo_dato = "";			
      		//if($i==5)$tipo_dato = "date";//pg_field_type($result,$i);
      }else{		
      	$tipo_dato = pg_field_type($result,$i);
      }	
      
			$campo_data=$row[$este];
      
      if ($sumar=="S") {
         $totales[$i]+=$campo_data;
         $parciales[$i]+=$campo_data;
      }
      if($tipo_dato=="date")$campo_data=invierte_fecha($campo_data);
      if($tipo_dato=="numeric")$campo_data=number_format($campo_data,2,'.',',');

      $documento->SetXY($x,$y);
      $documento->Cell($ancho_celda,4,"$campo_data",0,0,$alineado,true);
      $x=$x+$ancho_celda+1;    
    }

    $y+=4;
    $l++;
     if ($l>50) {
        $documento->AddPage();        
        encabezado_pdf($documento,$labels,$titulo,$subtitulo,$empresa);
        if ($subtitulo == "")
           $y=35;
        else
				   $y=40;   
        $l=0;
     }    
    
  }
  
  // Imprimir parciales
  $x=10;
  for($i=0;$i<$c;$i++){
      $este=$e[$i];      
      $aux=explode("=",$este);
      $este=trim($aux[1]);
      $ancho_celda=$aux[2];
      $alineado=$aux[3];
      $sumar=$aux[4];
      if ($sumar=="S") {
         $campo_data=number_format($parciales[$i],2,'.',',');

         $documento->SetXY($x,$y);
         $documento->Cell($ancho_celda,4,$campo_data,"T",0,$alineado,true);
      }
      $x=$x+$ancho_celda+1;    
  }
  $y+=6;
  
  // Imprimir totales
  $x=10;
  for($i=0;$i<$c;$i++){
      $este=$e[$i];      
      $aux=explode("=",$este);
      $este=trim($aux[1]);
      $ancho_celda=$aux[2];
      $alineado=$aux[3];
      $sumar=$aux[4];
      if ($sumar=="S") {
         $campo_data=number_format($totales[$i],2,'.',',');

         $documento->SetXY($x,$y);
         $documento->Cell($ancho_celda,4,$campo_data,"T",0,$alineado,true);
      }
      $x=$x+$ancho_celda+1;    
  }

  $documento->Output("reporte","I");
}


function imprime_pdf($titulo,$subtitulo,$estos,$sql,$con){

  require('fpdf\fpdf.php');
  $empresa=strtoupper(pg_este("select nombre from r_empresa","nombre",$con));
  $e=explode("|",$estos);
  $c=count($e);
  // Inicializar arreglo para totales.
  for($i=0;$i<$c;$i++){
     $totales[$i] = 0;
  }
  
  $documento = new FPDF('P','mm','Letter');
  $documento->AddPage();
  $documento->SetMargins(5,5,5);
  $documento->SetAutoPageBreak(false);
  $documento->SetFillColor(255,255,255);
  $documento->SetFont('Arial','',8);
	
  $campos="";
  for($i=0;$i<$c;$i++){

    $l=explode("=",$e[$i]); 
    $campos.=$l[1];
    if($i<$c-1) $campos.=",";
    
    $campo=$l[0];
    $ancho=$l[2];
    $alineado=$l[3];
    $labels[$i]= array($campo,$ancho,$alineado);
  }
  encabezado_pdf($documento,$labels,$titulo,$subtitulo,$empresa);
  
/*  
  $sql=strtoupper($sql);
  
  $aux=explode("FROM",$sql);
  $resto_query=$aux[1];
  $sql="SELECT $campos from $resto_query";
  $sql=str_replace("+","|",$sql);
*/

  //echo $sql;
  
  $result = pg_query($con,$sql) or die(pg_last_error($con));
  if ($subtitulo == "")
     $y=35;
  else
	   $y=40;   
  $l=0;
  while ($row = pg_fetch_array($result)){
     $x=10;
     for($i=0;$i<$c;$i++){
      $este=$e[$i];      
      $aux=explode("=",$este);
      $este=trim($aux[1]);
      $ancho_celda=$aux[2];
      $alineado=$aux[3];
      $sumar=$aux[4];      

      if(strpos($este,'numeric')>0) $alineado ="R";
      //if($aux[0]=="Modifico") $alineado ="align='right'";
      //if($aux[0]=="Status") $alineado ="align='right'";

      $aux=explode(" as ",$este);
      if(count($aux)>1)$este=trim($aux[1]);
      
      //echo "$este<br>";
      $aux=explode(".",$este);
      if(count($aux)>1)$este=$aux[1];
      			
      $tipo_dato = pg_field_type($result,$i);
      $campo_data=$row[$este];
 
      if (($sumar=="S") || ($sumar=="s")) {
         $totales[$i]+=$campo_data;
      }
      if($tipo_dato=="date")$campo_data=invierte_fecha($campo_data);
      if($tipo_dato=="numeric")$campo_data=number_format($campo_data,2,'.',',');

      $campo_data = utf8_decode($campo_data);
      if($este=="_")$campo_data="_____________";
      $documento->SetXY($x,$y);
      $documento->Cell($ancho_celda,4,$campo_data,0,0,$alineado,true);
      $x=$x+$ancho_celda+1;    
    }
    $y+=4;
    $l++;
     if ($l>56) {
        $documento->AddPage();        
        encabezado_pdf($documento,$labels,$titulo,$subtitulo,$empresa);
        if ($subtitulo == "")
           $y=35;
        else
				   $y=40;   
        $l=0;
     }    
    
  }
  // Imprimir totales
  $x=10;
  for($i=0;$i<$c;$i++){
      $este=$e[$i];      
      $aux=explode("=",$este);
      $este=trim($aux[1]);
      $ancho_celda=$aux[2];
      $alineado=$aux[3];
      $sumar=$aux[4];
      if ($sumar=="S") {
         $campo_data=number_format($totales[$i],2,'.',',');

         $documento->SetXY($x,$y);
         $documento->Cell($ancho_celda,4,$campo_data,"T",0,$alineado,true);
      } else if ($sumar=="s") {      
         $campo_data=number_format($totales[$i],0,'.',',');

         $documento->SetXY($x,$y);
         $documento->Cell($ancho_celda,4,$campo_data,"T",0,$alineado,true);

      }
      $x=$x+$ancho_celda+1;    
  }

  $documento->Output("reporte","I");
}

function encabezado_pdf($doc,$labels,$titulo,$subtitulo,$empresa) {

  //Select Arial bold 15
  $doc->SetFont('Arial','B',15);
  //Framed title
  $doc->SetXY(10,10);
  $doc->SetFillColor(220,220,220);       
  $doc->Cell(0,10,$empresa,0,0,'C',true);
  $doc->SetFillColor(255,255,255);
  $doc->SetXY(10,20);
  $doc->SetFont('Arial','B',10);
  $doc->Cell(0,6,$titulo,0,0,'C');
  $y = 20;
  if ($subtitulo != "") {
     $y = $y + 5;
     $doc->SetXY(10,$y);
     $doc->Cell(0,6,$subtitulo,0,0,'C');
  }
  $y = $y + 5;  
  $doc->SetXY(10,$y);
	$doc->Cell(0,6,date("d/m/Y  h:i:s"),0,0,'C');   
  $doc->SetFont('Arial','',8);
  // Titulos de los campos  
  $x=10;
  $y = $y + 5;
  $c = count($labels);
  for($i=0;$i<$c;$i++){
     $doc->SetXY($x,$y);
     $doc->Cell($labels[$i][1],4,$labels[$i][0],1,0,$labels[$i][2],true);
     $x=$x+$labels[$i][1]+1;      
  }

}

//// FUNCIONES PDF ////

function pdf_fuente($elpdf,$fuente,$tipo,$tamanio) {
  $elpdf->SetFont($fuente,$tipo,$tamanio);
}

function pdf_imagen($elpdf,$image,$px,$py,$b,$a) {
  $elpdf->SetXY($px,$py);
  $elpdf->Image($image,$px,$py,$b,$a,'');
}

function pdf_escribe($elpdf,$texto,$px,$py) {
  $elpdf->SetXY($px, $py);
  $elpdf->Cell(1,1,$texto);
}

function pdf_escribeD($elpdf,$texto,$px,$py) {
  $elpdf->SetXY($px, $py);
  $elpdf->Cell(1,1,$texto, 0, 0,'R');
}

function pdf_escribeC($elpdf,$texto,$px,$py) {
  $elpdf->SetXY($px, $py);
  $elpdf->Cell(1,1,$texto, 0, 0,'C');
}

function pdf_escribe_relleno($elpdf,$texto,$px,$py,$b,$a,$relleno) {
  $elpdf->SetFillColor(240,240,240);
  $elpdf->SetXY($px, $py);
  $elpdf->Cell($b,$a,$texto,'',0,'',$relleno);
}

function pdf_escribe_rellenoD($elpdf,$texto,$px,$py,$b,$a,$relleno) {
  $elpdf->SetFillColor(240,240,240);
  $elpdf->SetXY($px, $py);
  $elpdf->Cell($b,$a,$texto,0,0,'R',$relleno);
}

function pdf_escribe_rellenoC($elpdf,$texto,$px,$py,$b,$a,$relleno) {
  $elpdf->SetFillColor(240,240,240);
  $elpdf->SetXY($px, $py);
  $elpdf->Cell($b,$a,$texto,0,0,'C',$relleno);
}

function pdf_escribeV($elpdf,$texto,$px,$py,$paso) {
  $elpdf->SetXY($px, $py);
  for($i=0;$i<strlen($texto);$i++){
    $elpdf->SetXY($px, $py);
    $elpdf->Cell(1,1,$texto[$i]);
    $py+=$paso;
  }
}

function pdf_linea($elpdf,$px1,$py1,$px2,$py2) {
  $elpdf->line($px1,$py1,$px2,$py2);
}

function pdf_cuadro($elpdf,$x,$y,$b,$a,$estilo) {
  $elpdf->Rect($x, $y, $b, $a, $estilo);
}

function pdf_margen($elpdf,$x,$y,$b,$a) {
  pdf_linea($elpdf,$x,$y,$x+$b,$y);
  pdf_linea($elpdf,$x,$y+$a,$x+$b,$y+$a);
  pdf_linea($elpdf,$x,$y,$x,$y+$a);
  pdf_linea($elpdf,$x+$b,$y,$x+$b,$y+$a);
}


// FIN FUNCIONES elpdfa


function guardaLOG($LogOperacion, $LogDesc, $LogSQL){
  $LogFecha=date("Y/m/d");
  $LogHora=date("H:i:s");
  $LogMaquina=gethostbyaddr($_SERVER['REMOTE_ADDR']);
  
  $conLog=conectarLog();
  
  $LogSQL= str_replace("'", "''", $LogSQL);
  $sql="insert into trace (fecha, hora, maquina,operacion, descripcion, sql)  
        values('$LogFecha','$LogHora','$LogMaquina','$LogOperacion','$LogDesc','$LogSQL')";
  pg_query($conLog,$sql);
  pg_close($conLog);

}

?>
