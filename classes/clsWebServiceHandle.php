<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
//use Monolog\Handler\ChromePHPHandler;
//use Monolog\Handler\BrowserConsoleHandler;
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
     * El id de la sesion que me da el sap, este se usa para los pedidos y para el logout
     * @var string
     */
    protected $sessionId = '';
    /**
     * Los datos del cliente
     * @var array
     */
    protected $cliente = [
        'codCliente' => '',
        'nombre'     => '',
        'apellidos'  => '',
        'email'      => '',
    ];

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
        $error  = $this->loginService->getError();
        if(!$error){
            $params = [
                'DatabaseServer'  => '192.168.10.102', //string
                'DatabaseName'    => 'MERCHANDISING', //string
                'DatabaseType'    => 'dst_MSSQL2012', //DatabaseType
                'CompanyUsername' => 'manager', //string
                'CompanyPassword' => 'Pa$$w0rd', //string
                'Language'        => 'ln_Spanish', //Language
                'LicenseServer'   => '192.168.10.102:30000' //string
            ];
            $soapRes = $this->loginService->call('Login', $params);
            $error  = $this->loginService->getError();
            if($error){
               $this->log->error('Error en el login SAP: '. json_encode($error) );
               return false;
            }
            $this->log->info("respuesta login: ".json_encode($soapRes));
            $this->sessionId = $soapRes['SessionID'];
            return $this->sessionId;
        }else{
            $this->log->error('Error en el login SAP: '. json_encode($error) );
            return false;
        }
    }

    public function logout($sessionId = '') {
        $id = ($sessionId) ? $sessionId : $this->sessionId;
        $params = [
            'SessionID' => $id
        ];
        $this->loginService->setHeaders(['MsgHeader' => $params]);
        $error = $this->loginService->getError();
        if(!$error){
            $soapRes = $this->loginService->call('Logout', '<Logout xmlns="LoginService" />');
            $error  = $this->loginService->getError();
            if($error){
               $this->log->error('Error en el logout SAP: '. json_encode($error) );
               return false;
            }
            $this->log->info("respuesta logout: ". json_encode($soapRes));
            return true;
        }else{
            $this->log->error('Error en el logout SAP: '. json_encode($error) );
            return false;
        }
    }

    public function order($order, $sessionId = '') {
        $id = ($sessionId) ? $sessionId : $this->sessionId;
        $paramsH = [
            'SessionID'   => $id,
            'ServiceName' => 'OrdersService'
        ];
        $this->ordersService->setHeaders(['MsgHeader' => $paramsH]);

        $products = array_reduce($order['productos'], function($carry, $item){
            $carry .= '<DocumentLine>'
                            . "<ItemCode>{$item['referencia']}</ItemCode>"
                            . "<Quantity>{$item['cantidad']}</Quantity>"
                    . '</DocumentLine>';
            return $carry;
        }, '');

        $error  = $this->ordersService->getError();
        if(!$error){
            $soapRes = $this->ordersService->call('Add', ''
                    . '<Add>'
                        . '<Document>'
                                . '<Confirmed>N</Confirmed>'
                                . "<CardCode>c{$this->cliente['codCliente']}</CardCode>"
                                . '<Comments>Orden via motorepuestos.com.co</Comments>'
                                . "<DocDueDate>{$order['fecha_creacion']}</DocDueDate>"
                                . "<NumAtCard>{$order['id']}</NumAtCard>"
                                . '<DocumentLines>'
                                    . $products
                                . '</DocumentLines>'
                        . '</Document>'
                    . '</Add>'
                    );
            $error  = $this->ordersService->getError();
            if($error){
                $this->log->error('Error al hacer el pedido SAP: '. json_encode($error) );
                return false;
            }
            $this->log->info("respuesta del pedido a SAP: ".json_encode($soapRes));
            return true;
        }else{
            $this->log->error('Error al procesar la orden SAP: '. json_encode($error) );
            return false;
        }
    }


    public function __get($property)
    {
        switch ($property)
        {
            case 'loginService':
                return $this->loginService;
            case 'ordersService':
                return $this->ordersService;
            case 'cliente':
                return $this->cliente;
            //etc.
        }
    }

    public function __set($property, $value)
    {
        switch ($property)
        {
            case 'loginService':
                $this->loginService = new nusoap_client($value, true);
                break;
            case 'ordersService':
                $this->ordersService = new nusoap_client($value, true);
                break;
            case 'cliente':
                $this->cliente = $value;
                break;
            //etc.
        }
    }
}
