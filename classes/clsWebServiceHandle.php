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
     * Url del wsdl del servicio "BusinessPartners" para crear un cliente nuevo
     * en sap
     * @var string
     */
    protected $newUserService;
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

    /**
     * Este metodo es que se logue en el sap y recupera el id de la sesion
     * @return string me retorna el id de la sesion, aun de todas formas lo asigna al valor protegido de la clase
     */
    public function login() {
        $error  = $this->loginService->getError();
        if(!$error){
            $params = [
                'DatabaseServer'  => '192.168.10.102', //string
                'DatabaseName'    => 'VELEZ', //string
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

    /**
     * Este metodo es el encargado de cerrar la sesion en sap, recibe el id de la
     * sesion que se desea cerrar
     * @param  string $sessionId Id de la sesion que se desea cerrar
     * @return bool            me regresa un falso o un verdadero dependiendo de si se proceso o no el logout
     */
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

    /**
     * El metodo order se encarga de enviar la peticion al servicio de ordenes del webservice
     * y procesarla debidamente
     * @param  array $order     Este array recibe la orden que llega desde la pagina de prestashop
     * tiene los productos, la fecha en que se creo y el id de la orden
     * @param  string $sessionId Opcionalmente se le puede enviar el id de la sesion en sap con el que
     * se quiere procesar la orden
     * @return integer  me regresa el numero de la orden dependiendo de si se proceso o no la orden
     * si no se procesa retorna un false
     */
    public function order($order, $sessionId = '') {
        $id = ($sessionId) ? $sessionId : $this->sessionId;

        /**
         * El metodo "Add" del webservice pide unos headers entonces los agrego
         */
        $paramsH = [
            'SessionID'   => $id,
            'ServiceName' => 'OrdersService'
        ];
        $this->ordersService->setHeaders(['MsgHeader' => $paramsH]);

        /**
         * Con un reduce meto todos los productos de al array en un texto con el formato que pide el
         * webservice
         * @var array
         */
        $products = array_reduce($order['productos'], function($carry, $item){
            $carry .= '<DocumentLine>'
                            . "<ItemCode>{$item['referencia']}</ItemCode>"
                            . "<Quantity>{$item['cantidad']}</Quantity>"
                    . '</DocumentLine>';
            return $carry;
        }, '');

        $error = $this->ordersService->getError();
        if(!$error){
            /**
             * Armo la estructura xml que le voy a enviar al metodo Add del webservice
             */
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
             * Me trae la peticion en xml crudo de lo que se envio por soap al sap
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
            // Verifico que no haya ningun error, tambien reviso si existe exactamente la ruta del array que especifico
            // si esa rut ano existe significa que algo raro paso muy posiblemente un error
            $error = $this->ordersService->getError();
            if($error || !isset($soapRes['DocumentParams']['DocEntry'])){
                $this->log->error('Error al hacer el pedido SAP: '. json_encode($error) );
                $this->log->error("respuesta del error pedido a SAP: ". json_encode($this->utf8ize($soapRes)) );
                return false;
            }
            $this->log->info("respuesta del pedido a SAP: ". json_encode($this->utf8ize($soapRes)) );
            return $soapRes['DocumentParams']['DocEntry'];
        }else{
            $this->log->error('Error al procesar la orden SAP: '. json_encode($error) );
            return false;
        }
    }

    /**
     * El metodo newUser se encarga de enviar la peticion al servicio de BusinessPartners
     * de SAP, lo que me crea un nuevo cliente en sap que me permite realizar ordenes
     * @param  array $user un array de los datos del cliente con el sgte formato
     *  [
            'id'       => string,
            'fullName' => string,
            'name'     => string,
            'lastName' => string,
            'address'  => string,
            'email'    => string,
            'telCel'   => number,
            'telHome'  => number,
            'dateAdd'  => year-month-day hour:minute:second
        ]
     * @param  string $sessionId Opcionalmente se le puede enviar el id de la sesion en sap con el que
     * se quiere procesar la orden
     * @return integer  me regresa el id que se le asigno en sap al usuario
     */
    public function newUser($user, $sessionId = '') {
        $id = ($sessionId) ? $sessionId : $this->sessionId;

        /**
         * El metodo "Add" del webservice pide unos headers entonces los agrego
         */
        $paramsH = [
            'SessionID'   => $id,
            'ServiceName' => 'BusinessPartnersService'
        ];
        $this->newUserService->setHeaders(['MsgHeader' => $paramsH]);

        $error = $this->newUserService->getError();
        if(!$error){
            /**
             * Armo la estructura xml que le voy a enviar al metodo Add del webservice
             */
            $soapRes = $this->newUserService->call('Add', ''
                . '<Add>'
                    . '<BusinessPartner>'
                        . "<CardCode>c{$user['id']}</CardCode>"
                        . "<CardName>{$user['fullName']}</CardName>"
                        . "<CardType>cCustomer</CardType>"
                        . "<Address>{$user['address']}</Address>"
                        . "<MailAddress>{$user['email']}</MailAddress>"
                        . "<Phone1>{$user['telCel']}</Phone1>"
                        . "<Phone2>{$user['telHome']}</Phone2>"
                        . "<FederalTaxID>{$user['id']}</FederalTaxID>"
                        . "<U_BPCO_RTC>PN</U_BPCO_RTC>"
                        . "<U_BPCO_TDC>13</U_BPCO_TDC>"
                        . "<U_BPCO_CS>05001</U_BPCO_CS>"
                        . "<U_BPCO_TP>01</U_BPCO_TP>"
                        . "<U_TRASP>14</U_TRASP>"
                        . "<U_BPCO_Nombre>{$user['name']}</U_BPCO_Nombre>"
                        . "<U_BPCO_1Apellido>{$user['lastName']}</U_BPCO_1Apellido>"
                        . "<U_FEC_CREA>{$user['dateAdd']}</U_FEC_CREA>"
                    . '</BusinessPartner>'
                . '</Add>'
                );

            /**
             * Me trae la peticion en xml crudo de lo que se envio por soap al sap
             * algo asi como soap envelope bla, bla
             */
            $this->log->info('Request orden es: '.$this->newUserService->request);
            /**
             * Lo mismo que el anterior, pero en vez de traer la peticion, trae la respuesta
             */
            $this->log->info('Response orden es: '.$this->newUserService->response);
            /**
             * Me devuelve el string con todo el debug de todos los procesos que ha hecho nusoap
             * para activarlo hay q setear el nivel de debug a mas de 0 ejemplo: "$this->ordersService->setDebugLevel(9);"
             */
            $this->log->info('Debug orden es: '.$this->newUserService->debug_str);
            // Verifico que no haya ningun error, tambien reviso si existe exactamente la ruta del array que especifico
            // si esa rut ano existe significa que algo raro paso muy posiblemente un error
            $error = $this->newUserService->getError();
            if($error){
                $this->log->error('Error al crear el cliente SAP: '. json_encode($error) );
                $this->log->error("respuesta del error crear cliente a SAP: ". json_encode($this->utf8ize($soapRes)) );
                return false;
            }
            if(isset($soapRes['Code']['Subcode']['Value'])){
                $this->log->error('Error al crear el cliente SAP: '. json_encode($this->utf8ize($soapRes['Reason'])) );
                return false;
            }
            $this->log->info("respuesta del pedido a SAP: ". json_encode($this->utf8ize($soapRes)) );
            return $soapRes["BusinessPartnerParams"]["CardCode"];
        }else{
            $this->log->error('Error al procesar la creacion del cliente SAP: '. json_encode($error) );
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
            case 'newUserService':
                return $this->newUserService;
            case 'cliente':
                return $this->cliente;
            case 'sessionId':
                return $this->sessionId;
            //etc.
        }
    }

    public function __set($property, $value)
    {
        switch ($property)
        {
            case 'loginService':
                $this->loginService = new nusoap_client($value, true);
                /**
                 * recibe un valor del 0 al 9 donde 0 es deshabilitado
                 * con este "setDebugLevel" habilito que el nusoap recoja informacion sobre todo el proceso que realiza en el
                 * webservice, para recuperar la informacion se usaria algo como: $this->ordersService->debug_str
                 */
                $this->loginService->setDebugLevel(0);
                break;
            case 'ordersService':
                $this->ordersService = new nusoap_client($value, true);
                $this->ordersService->setDebugLevel(0);
                break;
            case 'newUserService':
                $this->newUserService = new nusoap_client($value, true);
                $this->newUserService->setDebugLevel(0);
                break;
            case 'cliente':
                $this->cliente = $value;
                break;
            //etc.
        }
    }
}
