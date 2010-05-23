<?php
/*
*  1.- Instalación del servicio
*     1.1.- Entrar al Windows como administrador del dominio
*     1.2.- Abrir una consola "cmd" y escribir:
*                net share admin$
*     1.3.- Desde el max-server ejecutar
*                winexe -U EBOX/admin //192.168.1.132 "ipconfig /all"
*
*     1.4.- Deberíamos haber visto la salida de ipconfig en la consola
*     1.5.- En el windows, Mi PC -> Propiedades -> Administrar ->
*                       -> Servicios y aplicaciones -> Servicios
*     1.6.- Buscar servicio "winexesvc" (suele ser el'último), 
*           botón derecho -> Propiedades
*     1.7.- Tipo de Inicio => Automático (iniciar si estuviera parado)
*
*
*                http://eol.ovh.org/winexe/
*/

class WINEXE {
    var $ip='';
    var $hostname='';
    var $alive=false;
    
    
    function WINEXE($ip='') {
        global $gui;
        $this->ip=$ip;
        $this->initialized=false;
    }
    
    function init() {
        global $gui;
        if ( ($this->ip != $this->checkIP($this->ip) ) ){
            $this->hostname=$this->ip;
            $this->ip=$this->getIpAddress($this->hostname);
        }
        
        $this->basecmd=WINEXE ." -U " . LDAP_DOMAIN . '/' . LDAP_ADMIN . '%' . LDAP_PASS;
        $this->basecmd.= ' --interactive=0 ';
        if ( $this->ip != '' )
            $this->basecmd.= " //" . $this->ip . " ";
        else
            $this->basecmd.= " //" . $this->hostname . " ";
        $this->initialized=true;
    }
    
    function windowsexe( $targetcmd ) {
        global $gui;
        if ( ! $this->initialized )
            $this->init();
        $cmd=$this->basecmd . "'". $targetcmd ."'";
        $gui->debug($cmd);
        exec($cmd, &$output);
        $gui->debug("<pre>".print_r($output, true)."</pre>");
        return $output;
    }
    
    
    function linuxexe($targetcmd) {
        global $gui;
        if ( ! $this->initialized )
            $this->init();
        
        $targetcmd="sudo $targetcmd";
        $gui->debug("WINEXE:linuxexe() cmd='$targetcmd'");
        
        // test for libssh2-php
        if (!function_exists("ssh2_connect")) {
            die("WINEXE:linuxexe() function ssh2_connect doesn't exist, install libssh2-php package and restart apache");
            return false;
        }
        
        if(!($con = ssh2_connect($this->ip, LINUX_PORT))){
            $gui->debug("WINEXE:linuxexe() fail: unable to establish connection");
            return false;
        }
        
        //$gui->debug("WINEXE:linuxexe() connected, now auth".print_r($con, true));
        
        // try to authenticate with username root, password secretpassword
        if( ! ssh2_auth_password($con, "test", "test") ) {
            $gui->debug("WINEXE:linuxexe() fail: unable to authenticate");
            return false;
        }
        
        //$gui->debug("WINEXE:linuxexe() auth ok, now exec command");
        
        // logged, execute the command
        $gui->debug("WINEXE:linuxexe(".$this->ip.") cmd='$targetcmd'");
        if ( ! ($stream = ssh2_exec($con, $targetcmd)) ) {
            $gui->debug("WINEXE:linuxexe()fail: unable to execute command");
            return false;
        }
        
        // collect returning data from command
        stream_set_blocking($stream, true);
        $data = "";
        while ($buf = fread($stream,4096)) {
            $data .= $buf;
        }
        fclose($stream);
        return preg_split("#\\n#i",$data);
    }
    
    function getIpAddress($hostname) {
        /*
        * net lookup wxp
            192.168.1.132
        */
        global $gui;
        $cmd="net lookup $hostname";
        exec($cmd, &$output);
        if ( isset($output[0]) ) {
            $gui->debug("WINEXE:getIpAddress($hostname)=".$output[0]);
            return $this->checkIP($output[0]);
        }
        $gui->debug("WINEXE:getIpAddress($hostname) ERROR, can't resolve hostname");
        return "";
    }
    
    function getMacAddress($hostname) {
        global $gui;
        $ip=$hostname;
        if ( ($hostname != $this->checkIP($hostname) ) ){
            // se nos ha pasado algo que no es una IP
            $ip=$this->getIpAddress($hostname);
        }
        // leemos /proc/net/arp
        /*
        cat /proc/net/arp 
        IP address       HW type     Flags       HW address            Mask     Device
        10.0.2.2         0x1         0x2         52:54:00:12:35:02     *        eth0
        192.168.1.2      0x1         0x2         00:1a:6b:6a:be:c9     *        eth1
        192.168.1.132    0x1         0x2         08:00:27:2e:50:ff     *        eth1
        */
        $mac='';
        $file_handle = fopen('/proc/net/arp', 'r');
        while (!feof($file_handle) ) {
            $line_of_text = fgets($file_handle);
            $parts = preg_split ("/\s+/", $line_of_text);
            if ( $parts[0] == $ip ) {
                $mac=$parts[3];
            }
        }
        fclose($file_handle);
        
        return $mac;
    }
    
    function checkIP($ip) {
        $cIP = ip2long($ip);
        $fIP = long2ip($cIP);
        return $fIP;
    }
    
    function isLinux() {
        global $gui;
        $open=false;
        
        if (! $this->is_alive())
            return false;
        
        //$gui->debug("isLinux(): try to open ".LINUX_PORT." port in".$this->ip);
        $fp = @fsockopen($this->ip, LINUX_PORT, $errno, $errstr);
        if (!$fp) {
            $gui->debug("isLinux(".$this->ip."):ERROR: $errno - $errstr");
        } else {
            $gui->debug("isLinux(".$this->ip."):port open");
            $open=true;
            fclose($fp);
        }
        return $open;
    }
    
    function is_alive () {
        global $gui;
        $this->alive=false;
        $this->init();
        // no ejecutar pings a cosas que no existen
        if ($this->ip == '' || $this->ip =='0.0.0.0')
            return false;
        
        //$gui->debug("is_alive()".$this->ip);
        $str = exec("ping -c 1 -w 1 ".$this->ip, $input, $result);
        if ($result == 0) {
            $gui->debug("is_live(".$this->ip.") host is alive");
            $this->alive=true;
        }
        else {
            $gui->debug("is_live(".$this->ip.") host unreachable");
        }
        return $this->alive;
    }
    
    function poweroff() {
        if (! $this->isLinux() )
            return $this->windowsexe('shutdown -s -t '.POWEROFF_REBOOT_TIMEOUT.' -c "Apagado remoto desde max-control"');
        else {
            return $this->linuxexe('shutdown -h now');
        }
    }
    
    function reboot() {
        if (! $this->isLinux() )
            return $this->windowsexe('shutdown -r -t '.POWEROFF_REBOOT_TIMEOUT.' -c "Reinicio remoto desde max-control"');
        else {
            return $this->linuxexe('shutdown -r now');
        }
    }
    




/* end of class WINEXE */
}
?>