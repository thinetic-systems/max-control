<?php


/*

 Common functions

*/





function __unserialize($string) {
    $tmp=array();
    if($string == "") return $tmp;
    $unserialized = stripslashes($string);
    $unserialized = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $unserialized );
    $tmp=@unserialize($unserialized);
    return $tmp;
}



function leer_datos($nombre="", $type='plain'){
    if (isset($_POST[$nombre]) ){
        $txt=$_POST[$nombre];
    }
    elseif ( isset($_GET[$nombre]) ){
        $txt=$_GET[$nombre];
    }
    else{
        $txt="";
    }
    return sanitizeOne( $txt , $type );
}



function truncate($substring, $max = 50, $rep = '...') {
    if(strlen($substring) < 1){
       $string = $rep;
    }else{
       $string = $substring;
    }

    $leave = $max - strlen ($rep);

    if(strlen($string) > $max){
       return substr_replace($string, $rep, $leave);
    }else{
       return $string;
    }
}

function human_size($size,$dec=1){
    $size_names= array('Byte','KByte','MByte','GByte', 'TByte','PB','EB','ZB','YB','NB','DB');
    $name_id=0;
    while($size>=1024 && ($name_id<count($size_names)-1)){
        $size/=1024;
        $name_id++;
    }
    return round($size,$dec).' '.$size_names[$name_id];
}


function time_start() {
    global $starttime;
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    $starttime = $mtime;
}
 
function time_end() {
    global $starttime;
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    return ($mtime - $starttime);
}


/*

    Sanitize class
    Copyright (C) 2007 CodeAssembly.com  

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see http://www.gnu.org/licenses/
*/
/**

 * Sanitize only one variable .
 * Returns the variable sanitized according to the desired type or true/false 
 * for certain data types if the variable does not correspond to the given data type.
 * 
 * NOTE: True/False is returned only for telephone, pin, id_card data types
 *
 * @param mixed The variable itself
 * @param string A string containing the desired variable type
 * @return The sanitized variable or true/false
 */



function sanitizeOne($var, $type) {
    switch ( $type ) {
        case 'int': // integer
        $var = (int) $var;
        break;

        case 'str': // trim string
        $var = trim ( $var );
        break;

        case 'nohtml': // trim string, no HTML allowed
        $var = htmlentities ( trim ( $var ), ENT_QUOTES );
        break;

        case 'plain': // trim string, no HTML allowed, plain text
        $var =  htmlentities ( trim ( $var ) , ENT_NOQUOTES )  ;
        break;

        case 'upper_word': // trim string, upper case words
        $var = ucwords ( strtolower ( trim ( $var ) ) );
        break;

        case 'ucfirst': // trim string, upper case first word
        $var = ucfirst ( strtolower ( trim ( $var ) ) );
        break;

        case 'lower': // trim string, lower case words
        $var = strtolower ( trim ( $var ) );
        break;

        case 'urle': // trim string, url encoded
        $var = urlencode ( trim ( $var ) );
        break;

        case 'trim_urle': // trim string, url decoded
        $var = urldecode ( trim ( $var ) );
        break;
    }
    return $var;
}





/**
 * Sanitize an array.
 * 
 * sanitize($_POST, array('id'=>'int', 'name' => 'str'));
 * sanitize($customArray, array('id'=>'int', 'name' => 'str'));
 *
 * @param array $data
 * @param array $whatToKeep
 */



function sanitize( &$data, $whatToKeep ) {
        $data = array_intersect_key( $data, $whatToKeep ); 
        
        foreach ($data as $key => $value) {
                $data[$key] = sanitizeOne( $data[$key] , $whatToKeep[$key] );
        }
}

/* see http://www.phpbuilder.com/columns/sanitize_inc_php.txt */




function NTLMHash($Input) {
  // Convert the password from UTF8 to UTF16 (little endian)
  $Input=iconv('UTF-8','UTF-16LE',$Input);

  // Encrypt it with the MD4 hash
  $MD4Hash=bin2hex(mhash(MHASH_MD4,$Input));

  // You could use this instead, but mhash works on PHP 4 and 5 or above
  // The hash function only works on 5 or above
  //$MD4Hash=hash('md4',$Input);

  // Make it uppercase, not necessary, but it's common to do so with NTLM hashes
  $NTLMHash=strtoupper($MD4Hash);

  // Return the result
  return($NTLMHash);
}

function LMhash_DESencrypt($string)
{
    $key = array();
    $tmp = array();
    $len = strlen($string);

    for ($i=0; $i<7; ++$i)
        $tmp[] = $i < $len ? ord($string[$i]) : 0;

    $key[] = $tmp[0] & 254;
    $key[] = ($tmp[0] << 7) | ($tmp[1] >> 1);
    $key[] = ($tmp[1] << 6) | ($tmp[2] >> 2);
    $key[] = ($tmp[2] << 5) | ($tmp[3] >> 3);
    $key[] = ($tmp[3] << 4) | ($tmp[4] >> 4);
    $key[] = ($tmp[4] << 3) | ($tmp[5] >> 5);
    $key[] = ($tmp[5] << 2) | ($tmp[6] >> 6);
    $key[] = $tmp[6] << 1;
    
    $is = mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($is, MCRYPT_RAND);
    $key0 = "";
    
    foreach ($key as $k)
        $key0 .= chr($k);
    $crypt = mcrypt_encrypt(MCRYPT_DES, $key0, "KGS!@#$%", MCRYPT_MODE_ECB, $iv);

    return bin2hex($crypt);
}

function LMhash($string)
{
    $string = strtoupper(substr($string,0,14));

    $p1 = LMhash_DESencrypt(substr($string, 0, 7));
    $p2 = LMhash_DESencrypt(substr($string, 7, 7));

    return strtoupper($p1.$p2);
}

?>