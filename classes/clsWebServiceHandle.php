<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
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
     * Inicia la instancia de monolog
     */
    function __construct() {
        $this->log = new Logger('ClaseConexionSap');
        $this->log->pushHandler(new StreamHandler(dirname(dirname(__FILE__)).'/logs/info.log', Logger::DEBUG));
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
            $this->log->info("respuesta logout: ". json_encode($this->utf8ize($soapRes) ));
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

        $error = $this->ordersService->getError();
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

            /**
             * Me trae la peticion en xml pulpo de lo que se envio por soap al sap
             * algo asi como soap envelope bla, bla
             */
            $this->log->info('Request orden es: '.$this->ordersService->request);
            /**
             * Lo mismo que el anterior, pero en vez de traer la peticion, trae la respuesta
             */
            $this->log->info('Response orden es: '.$this->ordersService->response);
            /**
             * Me devuelve el string con todo el debug de todos los procesos que ha hecho nusoap
             * para activarlo hay q setear el nivel de debug a mas de 0 ejemplo: "$this->ordersService->setDebugLevel(9);"
             */
            $this->log->info('Debug orden es: '.$this->ordersService->debug_str);

            $error = $this->ordersService->getError();
            if($error){
                $this->log->error('Error al hacer el pedido SAP: '. json_encode($error) );
                return false;
            }
            $this->log->info("respuesta del pedido a SAP: ". json_encode($this->utf8ize($soapRes)) );
            return true;
        }else{
            $this->log->error('Error al procesar la orden SAP: '. json_encode($error) );
            return false;
        }
    }
    /**
     * solve JSON_ERROR_UTF8 error in php json_encode
     * esta funcionsita me corrije un error que habia al tratar de hacerle json encode aun array con tildes
     * en algunos textos
     * @param  array $mixed El erray que se decia corregir
     * @return array        Regresa el mismo array pero corrigiendo errores en la codificacion
     */
    public function utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } else if (is_string ($mixed)) {
            return utf8_encode($mixed);
        }
        return $mixed;
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
                $this->loginService->setDebugLevel(0);
                break;
            case 'ordersService':
                $this->ordersService = new nusoap_client($value, true);
                $this->ordersService->setDebugLevel(0);
                break;
            case 'cliente':
                $this->cliente = $value;
                break;
            //etc.
        }
    }
}
