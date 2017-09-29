<?php

function texto($nombre,$valor,$parametros,$tamanio,$maximo){
  return "<input type='text' value='$valor' name='$nombre' id='$nombre' size='$tamanio' class='form-control input-sm' maxlength='$maximo' $parametros>";
}

function numero($nombre,$valor,$parametros,$tamanio,$maximo){
  return "<input type='number' value='$valor' name='$nombre' id='$nombre' size='$tamanio' class='form-control input-sm' maxlength='$maximo' $parametros>";
}

function decimal($nombre,$valor,$parametros,$tamanio,$maximo){
  return "<input type='number' step='any' value='$valor' name='$nombre' id='$nombre' size='$tamanio' class='form-control input-sm' maxlength='$maximo' $parametros>";
}

function oculto($nombre,$valor,$parametros){
  return "<input type='hidden' value='$valor' name='$nombre' id='$nombre' $parametros>";
}

function contrasenia($nombre,$valor,$parametros,$tamanio,$maximo){
  return "<input type='password' class='form-control' value='$valor' name='$nombre' id='$nombre' size='$tamanio' maxlength='$maximo' $parametros>";
}

function memo($nombre,$valor,$parametros,$filas,$columnas){
  return "<textarea name='$nombre' id='$nombre' class='form-control' cols='$columnas' rows='$filas' $parametros>$valor</textarea>";
}

function radio($nombre,$valor,$parametros,$checa){
  $checked="";
  if($checa==$valor)$checked="checked";
  return "<input type='radio' value='$valor' name='$nombre' id='$nombre' $parametros $checked>";
}

function checkbox($nombre,$valor,$parametros,$checa){
  $checked="";
  if($checa==$valor)$checked="checked";
  return "<input type='checkbox' value='$valor' name='$nombre' id='$nombre' $parametros $checked>";
}

function fecha($nombre,$valor,$parametros,$tamanio,$maximo,$forma){
  $retorno = "  
	<input class='datepicker form-control' id='$nombre' name='$nombre' value='$valor'>		
	<script>	$('#$nombre').datepicker({	});	</script>		
  ";
  return $retorno;
}

function select_x($nombre,$valor,$parametros,$cadena){
  $retorno='
         <select class="form-control  input-sm" name="'.$nombre.'" id="'.$nombre.'" '.$parametros.'>'."\n";
        $opciones=explode("|",$cadena);
        $j=count($opciones);
        for($i=0;$i<$j;$i++){
          $opcion=explode("=",$opciones[$i]);
          if($opcion[1]==$valor){$selecciona="selected";}else{$selecciona="";}
          $retorno.= '<option '.$selecciona.' value="'.$opcion[1].'">'.$opcion[0].'</option>'."\n";
        }
    $retorno.='
         </select>
  ';
  return $retorno;
}
function select_g($nombre,$valor,$parametros,$cadena){
  $retorno='
         <select name="'.$nombre.'" id="'.$nombre.'" '.$parametros.'>'."\n";
        $opciones=explode("|",$cadena);
        $j=count($opciones);
        for($i=0;$i<$j;$i++){
          $opcion=explode("=",$opciones[$i]);
          if($opcion[1]==$valor){$selecciona="selected";}else{$selecciona="";}
          $retorno.= '<option '.$selecciona.' value="'.$opcion[1].'">'.$opcion[0].'</option>'."\n";
        }
    $retorno.='
         </select>
  ';
  return $retorno;
}

function select_tabla($nombre,$valor,$parametros,$tabla,$campo_nombre,$campo_clave,$con){
  $retorno='
         <select class="form-control" name="'.$nombre.'" id="'.$nombre.'" '.$parametros.'>
          <option value=""></option>'."\n";
        $sql="select $campo_clave,$campo_nombre from $tabla order by $campo_clave";
        $result = mysql_query($sql, $con) or die(mysql_error());
        while($row=mysql_fetch_array($result)){
          if($row["$campo_clave"]==$valor){$selecciona="selected";}else{$selecciona="";}
          $retorno.= '<option '.$selecciona.' value="'.$row["$campo_clave"].'">'.$row["$campo_nombre"].'</option>'."\n";
        }
    $retorno.='
         </select>
  ';
  return $retorno;
}

function select_sqlG($nombre,$valor,$parametros,$sql,$campo_nombre,$campo_clave,$con){
  $retorno='
         <select name="'.$nombre.'" id="'.$nombre.'" '.$parametros.'>
          <option value=""></option>'."\n";
        $result = pg_query($con,$sql) or die (pg_last_error($con));
        while($row=pg_fetch_array($result)){
				
				  if(strpos($campo_nombre,"|")===false){
						$nombres=$row["$campo_nombre"];
					}else{
	          $campos=explode("|",$campo_nombre);
	          $cuantos=count($campos);
	          $nombres="";
	          for($i=0;$i<$cuantos;$i++)
					  	$nombres.=$row["$campos[$i]"]." ";
					}
        
          if((" ".$row["$campo_clave"]." ") == (" ".$valor." ")){$selecciona="selected";}else{$selecciona="";}
          $retorno.= '<option '.$selecciona.' value="'.$row["$campo_clave"].'">'.$nombres.'</option>'."\n";
        }
    $retorno.='
         </select>
  ';
  return $retorno;
}

function select_sql($nombre,$valor,$parametros,$sql,$campo_nombre,$campo_clave,$con){
  $retorno='
         <select class="form-control  input-sm" name="'.$nombre.'" id="'.$nombre.'" '.$parametros.'>
          <option value=""></option>'."\n";
        $result = pg_query($con,$sql) or die (pg_last_error($con));
        while($row=pg_fetch_array($result)){
				
				  if(strpos($campo_nombre,"|")===false){
						$nombres=$row["$campo_nombre"];
					}else{
	          $campos=explode("|",$campo_nombre);
	          $cuantos=count($campos);
	          $nombres="";
	          for($i=0;$i<$cuantos;$i++)
					  	$nombres.=$row["$campos[$i]"]." ";
					}
        
          if((" ".$row["$campo_clave"]." ") == (" ".$valor." ")){$selecciona="selected";}else{$selecciona="";}
          $retorno.= '<option '.$selecciona.' value="'.$row["$campo_clave"].'">'.$nombres.'</option>'."\n";
        }
    $retorno.='
         </select>
  ';
  return $retorno;
}

function select_sqlM($nombre,$valor,$parametros,$sql,$campo_nombre,$campo_clave,$con){
  $retorno='
         <select class="form-control" name="'.$nombre.'" id="'.$nombre.'" '.$parametros.'>
          <option value=""></option>'."\n";
        $result = pg_query($con,$sql) or die (pg_last_error($con));
        while($row=pg_fetch_array($result)){
				
				  if(strpos($campo_nombre,"|")===false){
						$nombres=$row["$campo_nombre"];
					}else{
	          $campos=explode("|",$campo_nombre);
	          $cuantos=count($campos);
	          $nombres="";
	          for($i=0;$i<$cuantos;$i++)
					  	$nombres.=$row["$campos[$i]"]." ";
					}
        
          $selecciona="";
          for ($i=0;$i<count($valor);$i++){        
             if($row["$campo_clave"]==$valor[$i])
						    $selecciona="selected";
					}
          $retorno.= '<option '.$selecciona.' value="'.$row["$campo_clave"].'">'.$nombres.'</option>'."\n";
        }
    $retorno.='
         </select>
  ';
  return $retorno;
}

function select_sqlT($nombre,$valor,$parametros,$sql,$campo_nombre,$campo_clave,$con){
$retorno='
         <select class="form-control" name="'.$nombre.'" id="'.$nombre.'" '.$parametros.'>
          <option value="***">Todas</option>'."\n";
        $result = pg_query($con,$sql) or die (pg_last_error($con));
        while($row=pg_fetch_array($result)){
				
				  if(strpos($campo_nombre,"|")===false){
						$nombres=$row["$campo_nombre"];
					}else{
	          $campos=explode("|",$campo_nombre);
	          $cuantos=count($campos);
	          $nombres="";
	          for($i=0;$i<$cuantos;$i++)
					  	$nombres.=$row["$campos[$i]"]." ";
					}
        
          if((" ".$row["$campo_clave"]." ") == (" ".$valor." ")){$selecciona="selected";}else{$selecciona="";}
          $retorno.= '<option '.$selecciona.' value="'.$row["$campo_clave"].'">'.$nombres.'</option>'."\n";
        }
    $retorno.='
         </select>
  ';
  return $retorno;
}

function dialogo_auto($aceptar, $cancelar) {
return '	  
     <div id="autoriza" style="background-color:#22ccdd; display: none; z-index: 20;">
	   <table>   
         <tr style="background-color: #0000ff; color: #ffffff;">
		   <td align="center" colspan="2">Autorizaci&oacute;n</td>
         </tr>
         <tr>
		   <td>Usuario:</td>
		   <td><input type="text" id="auto_usu" /></td>         
         </tr>
		 <tr>         
		   <td>Contrase&ntilde;a:</td>
		   <td><input type="password" id="auto_pass" /></td>         
         </tr>
		 <tr>
		   <td align="left">   
             <input type="button" value="Aceptar" onClick="'.$aceptar.'"/>  
		   </td>         
		   <td align="right">   
             <input type="button" value="Regresar" onClick="'.$cancelar.'"/>  
		   </td>         
         </tr> 
	   </table>     
     </div>

';

}
?>