<?php


/*
*
*  Modulo equipos
*
*/
global $gui;
global $permisos;
global $site;
global $module_actions;


global $url;

$module=$url->get("module");
$action=$url->get("action");
$subaction=$url->get("subaction");

if(DEBUG) {
    error_reporting(E_ALL);
}

if ( ! $permisos->is_connected() ) {
    $url->ir("","");
}

/*************************************************/

if ( $permisos->is_admin() ) {
    $module_actions=array(
        "aulas" => "Aulas",
        "ver" => "Equipos");
    if ($action == "")
        $url->ir($module, "aulas");
}
elseif ( $permisos->is_tic() ) {
    $module_actions=array(
        "aulas" => "Aulas");
    if ($action == "")
        $url->ir($module, "aulas");
}
else {
    $gui->session_error("Sólo pueden acceder al módulo de equipos los Administradores o Coordinadores TIC.");
    $url->ir("","");
}



/*************************************************/

if ($action == "")
    $url->ir($module, "ver");


function ver($module, $action, $subaction) {
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $url->ir($module, "aulas");
    }
    
    $button=leer_datos('button');
    $gui->debug("button='$button'");
    
    
    if( $button == "Limpiar cache WINS"){
        $url->ir($module, "purgewins");
    }
    
    if($button == "Actualizar MAC e IP de todos"){
        $url->ir($module, "update");
    }

    $filter=leer_datos('Filter');
    $aula=leer_datos('aula');
    // mostrar lista de equipos
    global $ldap;
    
    if ($aula != '')
        $equipos=$ldap->get_computers_from_aula($aula);
    else
        $equipos=$ldap->get_computers( $filter );

    //$gui->debuga($equipos);

    $urlform=$url->create_url($module, $action);
    
    $pager=new PAGER($equipos, $urlform, 0, $args='', NULL);
    $pager->processArgs( array('Filter', 'skip', 'aula', 'sort') );
    
    $equipos=$pager->getItems();
    $pager->sortfilter="(cn|ipHostNumber|macAddress|aula)";
    
    $aulas=$ldap->get_aulas_cn();
    //$gui->debuga($aulas);
    
    $data=array("equipos" => $equipos, 
                "aulas" => $aulas,
                "aula" => $aula,
                "filter" => $filter, 
                "urlform" => $urlform, 
                "urleditar"=>$url->create_url($module,'editar'),
                "urlborrar"=>$url->create_url($module,'borrar'),
                "urlupdate"=>$url->create_url($module,'update'),
                "pager" => $pager);
    $gui->add( $gui->load_from_template("ver_equipos.tpl", $data) );
}

function editar($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden editar equipos.");
        $url->ir($module, "aulas");
    }
    
    $hostname=$url->get("subaction");
    global $ldap;
    $equipo=$ldap->get_computers($hostname.'$');
    
    if( ! $equipo ){
        $gui->session_error("Equipo '$hostname' no encontrado");
        $url->ir($module, "ver");
    }
    
    $aulas=$ldap->get_aulas();
    $urlform=$url->create_url($module, 'guardar');
    
    $data=array("hostname"=>$hostname, 
                "aulas" => $aulas,
                "u"=>$equipo[0],
                "urlform"=>$urlform,
                "action" => "Editar");
    
    $gui->add( $gui->load_from_template("editar_equipo.tpl", $data ) );
}


function update($module, $action, $subaction){
    global $gui, $url;
    $data=array("urlaction"=>$url->create_url($module, 'updatedo'));
    $gui->add( $gui->load_from_template("update_equipos.tpl", $data) );
}

function updatedo($module, $action, $subaction){
    global $gui, $url;
    global $ldap;
    $equipos=$ldap->get_computers();
    foreach($equipos as $equipo) {
        if($equipo->macAddress == '' || $equipo->macAddress == '00:00:00:00:00:00') {
            $equipo->getMACIP();
        }
        else {
            //$gui->debuga($equipo);
            $gui->session_info("El equipo ".$equipo->cn." ya tiene MAC registrada, no se actualiza.");
        }
    }
    if(!DEBUG) {
        $url->ir($module, "ver");
    }
}


function purgewins($module, $action, $subaction){
    global $gui, $url;
    $data=array("urlaction"=>$url->create_url($module, 'purgewinsdo'));
    $gui->add( $gui->load_from_template("purgewins.tpl", $data) );
}

function purgewinsdo($module, $action, $subaction){
    global $gui, $url;
    global $ldap;
    $ldap->purgeWINS();
    $gui->session_info("Cache WINS borrada.");
    if(! DEBUG)
        $url->ir($active_module, "ver");
}



function borrar($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden borrar equipos.");
        $url->ir($module, "aulas");
    }
    
    $equipos=leer_datos("hostnames");
    $equiposarray=preg_split("/,/", $equipos);
    $data=array(
            "urlaction"=>$url->create_url($module, 'borrardo'),
            "equipos" =>$equipos,
            "equiposarray" => $equiposarray
                );
    $gui->add( $gui->load_from_template("borrar_equipo.tpl", $data) );
}

function borrardo($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden borrar equipos.");
        $url->ir($module, "aulas");
    }
    
    $gui->debug( "<pre>". print_r($_POST, true) . "</pre>" );
    $equipos=leer_datos('equipos');
    
    if ($equipos == '') {
        $gui->session_error("No se han seleccionado equipos");
        $url->ir($module, "ver");
    }
    
    global $ldap;
    $equiposarray=preg_split('/,/', $equipos);
    $gui->debuga($equiposarray);
    foreach($equiposarray as $equipo) {
        $obj=$ldap->get_computers($equipo);
        if ( isset($obj[0]) ) {
            $obj[0]->delComputer();
             $gui->session_info("Equipo '$equipo' borrado del dominio.");
        }
        else {
            $gui->session_error("El equipo '$equipo' no se ha encontrado");
        }
    }
    if(! DEBUG)
        $url->ir($module, "ver");
}

function guardar($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden editar equipos.");
        $url->ir($module, "aulas");
    }
    
    $hostname=leer_datos('hostname');
    global $ldap;
    $equipos=$ldap->get_computers($hostname.'$');
    if ( ! isset($equipos[0]) )
        $url->ir($module, "ver");
    
    $equipo=$equipos[0];
    //$gui->debuga($_POST);
    /*
    Array
    (
        [macAddress] => 08:00:27:fc:4f:4
        [ipHostNumber] => 192.168.1.14
        [bootFile] => 
        [aula] => aula primaria 2
        [Editar] => Guardar
        [hostname] => pc4
    )
    */
    sanitize($_POST, array('ipHostNumber'=>'net',
                           'macAddress' => 'mac',
                           'bootFile' => 'plain',
                           'hostname' => 'plain',
                           'aula' => 'plain')
            );
    $gui->debuga($_POST);
    //$gui->debug( "<pre>" . print_r($_POST,true) . "</pre>");
    $equipo->set($_POST);
    $equipo->description=$_POST['ipHostNumber'] . '/' . $_POST['macAddress'];
    $equipo->aula=$_POST['aula'];
    $res=$equipo->save();
    
    if ($res) {
        $gui->session_info("Equipo guardado correctamente.");
        if(! DEBUG)
            $url->ir($module, "ver");
    }
    else {
        $gui->session_error("Error guardando datos, por favor inténtelo de nuevo.");
        if(! DEBUG)
            $url->ir($module, "editar", $hostname);
    }
}

/*****************   aulas   ************************/

function veraulas($module, $action, $subaction){
    global $gui, $url, $permisos;
    $button=leer_datos('button');
    $gui->debug("button='$button'");
    if( $button !='' && $button != "Buscar"){
        $url->ir($module, "aulas", "nueva");
    }
    
    // mostrar lista de aulas
    global $ldap;
    $filter=leer_datos('Filter');
    $filtertxt='';
    if($filter != '') $filtertxt="*$filter*";
    $aulas=$ldap->get_aulas($filtertxt);
    $urlform=$url->create_url($module, $action);
    
    $pager=new PAGER($aulas, $urlform, 0, $args='', NULL);
    $pager->processArgs( array('Filter', 'skip', 'sort') );
    $aulas=$pager->getItems();
    $pager->sortfilter="(cn)";
    
    $mode='admin';
    if ( $permisos->is_tic() ) {
        $mode='tic';
    }
    
    $data=array("aulas" => $aulas, 
                "filter" => $filter,
                "urlform" => $urlform,
                "mode" => $mode,
                "urlprofesores"=>$url->create_url($module,'aulas', 'miembros'),
                "urlequipos"=>$url->create_url($module,'aulas', 'equipos'),
                "urlborrar" =>$url->create_url($module,'aulas', 'borrar'),
                "urladd" =>$url->create_url($module,'aulas', 'nueva'),
                "pager" => $pager);
    $gui->add( $gui->load_from_template("ver_aulas.tpl", $data) );
}

function aulasmiembros($module, $action, $subaction){
    global $gui, $url;
    $aula=leer_datos('args');
    global $ldap;
    $miembros=$ldap->get_teacher_from_aula($aula);
    $gui->debuga($miembros);
    
    $urlform=$url->create_url($module, $action, 'guardar');
    
    $data=array("aula"=>$aula, 
                "miembros"=>$miembros, 
                "urlform" => $urlform);
    
    $gui->add( $gui->load_from_template("editar_aula.tpl", $data) );
}

function aulasguardar($module, $action, $subaction){
    global $gui, $url;
    $gui->debug( "<pre>".print_r($_POST, true)."</pre>" );
    /*
    Array
        (
            [addtogroup] => Añadir usuarios al grupo
            [adduser] => profe3
            [aula] => grupoprueba
        )
    */
    
    /*
    Array
    (
        [deluser] => profe2
        [delfromgroup] => Quitar
        [aula] => grupoprueba
    )
    */
    $editaaula=leer_datos('aula');
    
    $addusers=clean_array($_POST, 'adduser');
    $delusers=clean_array($_POST, 'deluser');
    
    $gui->debug("addusers");
    $gui->debuga($addusers);
    $gui->debug("<hr><br>delusers ");
    $gui->debuga($delusers);
    
    global $ldap;
    
    if ( count($addusers) > 0 ) {
        $aula=$ldap->get_aula($editaaula);
        foreach($addusers as $adduser) {
            // añadir usuario al grupo $grupo
            $aula->newMember($adduser);
            $gui->session_info("Usuario '$adduser' añadido al aula $editaaula.");
        }
        if (!DEBUG)
          $url->ir($module, "aulas", "miembros/$editaaula");
    }
    elseif ( count($delusers) > 0 ) {
        $aula=$ldap->get_aula($editaaula);
        foreach($delusers as $deluser) {
            // borrar usuario del grupo $grupo
            $aula->delMember($deluser);
            $gui->session_info("Usuario '$deluser' eliminado del aula $editaaula.");
        }
        if (!DEBUG)
          $url->ir($module, "aulas", "miembros/$editaaula");
    }
    else {
        $gui->session_error("No se ha seleccionado ningún profesor.");
        $url->ir($module, "aulas", "miembros/$editaaula");
    }
}


/****************************************************/
function aulasequipos($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden editar los equipos de las aulas.");
        $url->ir($module, "aulas");
    }
    
    //samba-tool dns add 192.168.1.1 madrid.local MAX70A A 192.168.1.100 -Uadministrator

    $aula=leer_datos('args');
    global $ldap;
    $all=$ldap->get_computers_in_and_not_aula($aula);
    
    $gui->debuga($all);
    
    $urlform=$url->create_url($module, $subaction, 'guardar');
    
    $data=array("aula"=>$aula, 
                "equipos"=>$all, 
                "urlform" => $urlform);
    
    $gui->add( $gui->load_from_template("editar_aula_equipos.tpl", $data) );
}

function equiposguardar($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden editar los equipos de las aulas.");
        $url->ir($module, "aulas");
    }
    
    $gui->debug( "<pre>".print_r($_POST, true)."</pre>" );
    
    /* Add computer
    Array
    (
        [addtogroup] => Añadir usuarios al grupo
        [addcomputer] => wxp64
        [aula] => grupoprueba
    )
    */
    /* del computer
    Array
    (
        [delcomputer] => mario-desktop
        [delfromgroup] => Quitar
        [aula] => aula primaria 1
    )
    */
    
    $aula=leer_datos('aula');
    $addcomputers=clean_array($_POST, 'addcomputer');
    $delcomputers=clean_array($_POST, 'delcomputer');
    
    $gui->debug("addcomputers");
    $gui->debuga($addcomputers);
    $gui->debug("<hr><br>delcomputers ");
    $gui->debuga($delcomputers);
    
    global $ldap;

    $aulas=$ldap->get_aulas($aula);
    if( sizeof($aulas) != 1 ) {
        $gui->session_error("Aula '$aula' no encontrada.");
        $url->ir($module, "aulas", "equipos");
        return;
    }


    if ( count($addcomputers) > 0 ) {
        foreach($addcomputers as $addcomputer) {
            if( $aulas[0]->add_computer($addcomputer) ) {
                $gui->session_info("Equipo '$addcomputer' añadido al aula '$aula' correctamente.");
                $equipo=$ldap->get_computers($addcomputer .'$');
                $equipo[0]->boot($aula);
            }
            else {
                $gui->session_error("No se puedo añadir el equipo '$addcomputer' al aula '$aula'.");
            }
        }
        if (!DEBUG)
          $url->ir($module, "aulas", "equipos/$aula");
    }
    elseif ( count($delcomputers) > 0 ) {
        foreach($delcomputers as $delcomputer) {

            if( $aulas[0]->del_computer($delcomputer) ) {
                $gui->session_info("Equipo '$delcomputer' quitado del aula '$aula' correctamente.");
                $equipo=$ldap->get_computers($delcomputer .'$');
                $equipo[0]->boot('default');
            }
            else {
                $gui->session_error("No se pudo quitar el equipo '$delcomputer' del aula '$aula'");
            }
        }

        if (!DEBUG)
          $url->ir($module, "aulas", "equipos/$aula");
    }

    else {
        $gui->session_error("No se ha seleccionado ningún equipo.");
        $url->ir($module, "aulas", "equipos/$aula");
    }
}


function aulasnueva($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden crear aulas.");
        $url->ir($module, "aulas");
    }
    
    $group=new GROUP();
    $urlform=$url->create_url($module, $action, 'aulaguardar');
    
    $data=array("u"=>$group,
                "urlform"=>$urlform,
                "action" => "Editar");
    
    $gui->add( $gui->load_from_template("add_aula.tpl", $data ) );
}

function aulasaulaguardar($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden crear aulas.");
        $url->ir($module, "aulas");
    }
    
    $gui->debuga($_POST);
    /*
    Array
    (
        [cn] => aaaaa
        [description] => 
        [add] => Añadir
    )
    */
    
    if ( leer_datos('cn') == '' ) {
        $gui->session_error("Error, identificador de aula vacío.");
        $url->ir($module, "nueva");
    }
    
    $group=new AULA($_POST);
    if ( $group->newAula() )
        $gui->session_info("Aula '".$group->cn."' añadida correctamente.");
    
    if (!DEBUG)
        $url->ir($module, "aulas");
}

function aulasborrar($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden borrar aulas.");
        $url->ir($module, "aulas");
    }
    
    $aula=leer_datos('args');
    $urlform=$url->create_url($module, $action, 'aulaborrar');
    $data=array("aula" => $aula,
                "urlform"=>$urlform);
    
    $gui->add( $gui->load_from_template("del_aula.tpl", $data) );
}

function aulasborrardo($module, $action, $subaction){
    global $gui, $url, $permisos;
    
    if ( ! $permisos->is_admin() ) {
        $gui->session_error("Los Coordinadores TIC no pueden borrar aulas.");
        $url->ir($module, "aulas");
    }
    
    $gui->debug( "<pre>".print_r($_POST, true)."</pre>" );
    /*
    Array
    (
        [aula] => aula primaria 4
        [confirm] => Confirmar
    )
    */
    $aula=leer_datos('aula');

    if ($aula == '') {
        $gui->session_error("No se pudo encontrar el aula '$aula'");
        $url->ir($module, "aulas");
    }

    global $ldap;
    $aulas=$ldap->get_aula($aula);
    
    $gui->debug( "<pre>". print_r($aulas, true) . "</pre>" );
    
    if ($aulas->cn != $aula) {
        $gui->session_error(" El aula '$aula' no existe.");
        $url->ir($module, "aulas");
    }
    
    if ( $aulas->delAula() )
        $gui->session_info("Aula '$aula' borrada.");
    
    if (!DEBUG)
        $url->ir($module, "aulas");
}



switch($action) {
    case "ver":          ver($module, $action, $subaction); break;
    case "editar":       editar($module, $action, $subaction); break;
    case "update":       update($module, $action, $subaction); break;
    case "updatedo":     updatedo($module, $action, $subaction); break;
    case "purgewins":    purgewins($module, $action, $subaction); break;
    case "purgewinsdo":  purgewinsdo($module, $action, $subaction); break;
    case "borrar":       borrar($module, $action, $subaction); break;
    case "borrardo":     borrardo($module, $action, $subaction); break;
    case "guardar":      guardar($module, $action, $subaction); break;
    
    case "aulas":
            switch($subaction) {
                case "":            veraulas($module, $action, $subaction); break;
                case "miembros":    aulasmiembros($module, $action, $subaction); break;
                case "guardar":     aulasguardar($module, $action, $subaction); break;
                case "equipos":     aulasequipos($module, $action, $subaction); break;
                case "nueva":       aulasnueva($module, $action, $subaction); break;
                case "aulaguardar": aulasaulaguardar($module, $action, $subaction); break;
                case "borrar":      aulasborrar($module, $action, $subaction); break;
                case "aulaborrar":  aulasborrardo($module, $action, $subaction); break;
                
                default: $gui->session_error("Subaccion desconocida '$subaction' en módulo equipos/aulas");
            }
            break;
    
    case "equipos":
            switch($subaction) {
                case "guardar":     equiposguardar($module, $action, $subaction); break;
                
                default: $gui->session_error("Subaccion desconocida '$subaction' en módulo equipos/equipos");
            }
            break;
    
    default: $gui->session_error("Accion desconocida '$action' en modulo equipos");
}



