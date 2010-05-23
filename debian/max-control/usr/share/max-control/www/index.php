<?php
// 10 minutos de session
ini_set("session.gc_maxlifetime", "600"); 
session_start();
$path=dirname(dirname(__FILE__));


// cargamos la configuracion y el modulo comun
include($path . '/conf.inc.php');
include($path . '/modules/common.inc.php');

if ( ! CONFIGURED ) {
    die("Editar el archivo /etc/max-control/conf.inc.php y poner CONFIGURED como True");
}


$site['path']=$path;

// temporizador
time_start();



// cargamos classes
include($path . '/classes/navigator.class.php');
include($path . '/classes/url.class.php');
include($path . '/classes/module.class.php');
require(SMARTY_REQUIRE);

include($path . '/classes/gui.class.php');
include($path . '/classes/permisos.class.php');
include($path . '/classes/ldap.class.php');
include($path . '/classes/winexe.class.php');


global $module_actions;
global $module_name;

$site["basedir"]=dirname($_SERVER["PHP_SELF"]);

global $permisos;
$permisos = new Permisos();


// cargar interfaz
global $gui;
$gui= new Gui();

// ver si es peticion ajax
$ajaxurl=new URLHandler();
if ( $ajaxurl->get("ajax") == "1" ) {
    include($path . '/classes/ajax.class.php');
    $ajax= new Ajax();
    $ajax->process($ajaxurl->post_array);
    $ajax->show();
    die();
}





//cargamos la clase navegador
$nav = new Navigator();




/***************   cargar login o index **************/
if (isset($gui) && isset($_SESSION["user"]) ){
  $gui->assign("logout", True );
  $gui->assign("user", $_SESSION["user"] );
  $gui->assign("logout_url", $nav->url->create_url("login", "logout") );
}
elseif ( isset($gui) ) {
  $gui->assign('login', True );
  $gui->assign("login_url", $nav->url->create_url("login", "login") );
  $gui->main_template="login.tpl";
}
/************************************************************/




if (isset($gui)) {
    $gui->debug("Session cache=" .  ini_get("session.gc_maxlifetime") );
    $gui->debug("Tiempo de ejecución: " . time_end() );
    $gui->debug("QUERY_STRING=".$_SERVER['QUERY_STRING']);
    $gui->debug_array($_POST, "index.php POST");
    $gui->debug_array($_GET, "index.php GET");
    $gui->render();
}


?>