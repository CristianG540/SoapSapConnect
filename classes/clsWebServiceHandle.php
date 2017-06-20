<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\BrowserConsoleHandler;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Clase para conectarse al webservice de sap y realizar diferentes peticiones
 *
 * @author user
 */
class WebServiceHandle {
    /**
     * Url del wsdl del servicio para loguearse y desloguearse de sap
     * @var string
     */
    protected $loginService;
    /**
     * Url del wsdl del servicio para enviar las ordenes a sap
     * @var string
     */
    protected $ordersService;
    /**
     * Variable donde guardo la instancia de monolog para hacer el log y debug de los datos
     * @var string
     */
    protected $log;

    /**
     * Inicia la insancia de monolog
     */
    function __construct() {
        $this->log = new Logger('ClaseConexionSap');
        $this->log->pushHandler(new StreamHandler(dirname(dirname(__FILE__)).'/logs/info.log', Logger::DEBUG));
        $this->log->pushHandler(new ChromePHPHandler());
        $this->log->pushHandler(new BrowserConsoleHandler());
    }

    public function login() {
        $login = new nusoap_client($this->loginService, true);
        $error  = $login->getError();
        if(!$error){
            $params = array(
                'DatabaseServer'  => '192.168.10.102', //string
                'DatabaseName'    => 'MERCHANDISING', //string
                'DatabaseType'    => 'dst_MSSQL2012', //DatabaseType
                'CompanyUsername' => 'manager', //string
                'CompanyPassword' => 'Pa$$w0rd', //string
                'Language'        => 'ln_Spanish', //Language
                'LicenseServer'   => '192.168.10.102:30000' //string
            );
            $soapRes = $login->call('Login', $params);
            $error  = $login->getError();
            if($error){
               $this->log->error($error);
            }
            $this->log->info(json_encode($soapRes));
            return $soapRes['SessionID'];
        }else{
            $this->log->error($error);
            return false;
        }
    }

    public function __get($property)
    {
        switch ($property)
        {
            case 'loginService':
                return $this->loginService;
            //etc.
        }
    }

    public function __set($property, $value)
    {
        switch ($property)
        {
            case 'loginService':
                $this->loginService = $value;
                break;
            //etc.
        }
    }
}
