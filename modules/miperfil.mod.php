<?php


/*
*
*  Modulo miperfil
*
*/
global $gui;
global $permisos;
global $site;
global $module_actions;



global $url;

$active_module=$url->get("module");
$active_action=$url->get("action");
$active_subaction=$url->get("subaction");

if ( $permisos->is_templogin() ) {
    $url->ir("");
}

if ( ! $permisos->is_connected() ) {
    $url->ir("");
}

$module_actions=array(
        "editar" => "Cambiar contraseña",
);

// si no se pasa acción ir a editar
if ($url->get("action") == "") {
    $url->ir($active_module, "editar");
}

if ($active_action == "editar") {
    $username=$_SESSION['username'];
    global $ldap;
    $user=$ldap->get_user($username);

    if($user) {
        $urlform=$url->create_url($active_module, 'guardar');

        //$gui->debuga($user);
        
        $data=array("username"=>$username, 
                    "u"=>$user,
                    "urlform"=>$urlform,
                    "action" => "Editar");
        
        //$gui->add("<pre>".print_r($data, true)."</pre>");
        
        $gui->add( $gui->load_from_template("miperfil.tpl", $data ) );
    }
    
}


if ($active_action == "guardar") {
    $username=$_SESSION['username'];
    global $ldap;
    $usuario=$ldap->get_user($username);
    
    $gui->debug( "<pre>" . print_r($_POST,true) . "</pre>");
    
    
    $new=leer_datos('newpwd');
    if ( $new != '') {
        $new2=leer_datos('newpwd2');
        if ($new != $new2) {
            $gui->session_error("Las contraseñas no coinciden");
            $url->ir($active_module, "editar");
        }
        else {
            $usuario->update_password($new, $usuario->cn);
        }
    }
    
    sanitize($_POST, array('cn'=>'cnsn',
                           'givenname' => 'cnsn',
                           'sn' => 'cnsn',
                           'description' => 'cnsn'));
    $gui->debug( "<pre>" . print_r($_POST,true) . "</pre>");
    $usuario->set($_POST);
    
    if( $usuario->description == '' ) {
        $usuario->description=array();
    }
    $res=$usuario->save( array('cn', 'givenname', 'sn', 'description') );
    
    if ($res)
        $gui->session_info("Datos guardados correctamente");
    else
        $gui->session_error("Error guardando datos, por favor inténtelo de nuevo.");
    
    if(! DEBUG)
        $url->ir($active_module, "editar");
}

