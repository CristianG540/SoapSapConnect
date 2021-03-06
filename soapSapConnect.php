<?php
/**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require __DIR__.'/vendor/autoload.php';
require_once('classes/clsSoapSapDbUtils.php');
require_once('classes/clsWebServiceHandle.php');

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SoapSapConnect extends Module
{
    protected $config_form = false;
    private $log;

    public function __construct()
    {
        $this->name = 'soapSapConnect';
        $this->tab = 'administration';
        $this->version = '0.0.1';
        $this->author = 'Cristian Gonzalez V';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Soap Sap Connect');
        $this->description = $this->l('Modulo de prueba para conectarse a sap');

        $this->confirmUninstall = $this->l('Esta seguro de desinstalar el modulo ?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7');
        // create a log channel
        $this->log = new Logger('SoapSapConnect');
        $this->log->pushHandler(new StreamHandler($this->local_path.'/logs/info.log', Logger::DEBUG));
        $this->log->pushHandler(new BrowserConsoleHandler());
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayNav') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('actionCustomerAccountAdd') &&
            $this->installDB();
    }

    public function installDB(){
        $dbPrefijo = _DB_PREFIX_;
        return Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS `{$dbPrefijo}soapsapconnect_order` (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `sessionId` VARCHAR(300) NOT NULL COMMENT 'Session id que entrega el sap' ,
                `codCliente` VARCHAR(200) NOT NULL COMMENT 'La cedula del usario cliente que hace la compra' ,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion, mas o menos el momento en que se logea en sap' ,
                `numOrden` varchar(100) DEFAULT NULL COMMENT 'Numero de la orden en sap',
                `numOrdenPS` varchar(100) DEFAULT NULL COMMENT 'Numero de la orden en PRESTASHOP',
                PRIMARY KEY (`id`)
            ) ENGINE = InnoDB;
        ");
    }

    public function uninstall()
    {
        //Configuration::deleteByName('SOAPSAPCONNECT_LIVE_MODE');
        return $this->uninstallDB() && parent::uninstall();
    }

    public function uninstallDB(){
        $dbPrefijo = _DB_PREFIX_;
        return Db::getInstance()->execute("
            DROP TABLE `{$dbPrefijo}soapsapconnect_order`
        ");
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitSoapSapConnectModule')) == true) {

        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayNav($params){

        $this->context->smarty->assign([
            'testVar1' => 'variable de prueba'
        ]);

        //$this->log->warning('Foo');
        //$this->log->error('Bar');
        //$this->log->info('My logger is now ready', ["attr1"  => "La madre", "attr2"  => "ni herido"]);

        /*return $this->display(__FILE__, 'displayHeaderContent.tpl');*/
        // CODIGO DE PRUEBA USANDO LA LIBERIA SOAP NATIVA DE PHP
        /*
        // Create a new client object using a WSDL URL
        $soap = new SoapClient('http://b1ws.igbcolombia.com/B1WS/WebReferences/LoginService.wsdl', [
            # This array and its values are optional
            'soap_version' => SOAP_1_1,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            # Helps with debugging
            'trace' => TRUE,
            'exceptions' => TRUE
        ]);

        $this->log->info('functions', $soap->__getFunctions() );
        $this->log->info('types', $soap->__getTypes() );

        try {
            $params2 = array(
                'DatabaseServer'  => '192.168.10.102', //string
                'DatabaseName'    => 'MERCHANDISING', //string
                'DatabaseType'    => 'dst_MSSQL2012', //DatabaseType
                'CompanyUsername' => 'manager', //string
                'CompanyPassword' => 'Pa$$w0rd', //string
                'Language'        => 'ln_Spanish', //Language
                'LicenseServer'   => '192.168.10.102:30000' //string
            );
            $response = $soap->Login($params2);
            $this->log->info('Respuesta del ws: '.json_encode($response));
        } catch (Exception $e) {
            $this->log->info('Request: '. $soap->__getLastRequest() );
            $this->log->info('Response: '. $soap->__getLastResponse() );
            $this->log->error('Error en la peticion:'.json_encode($e->getMessage()) );
        } */

        //Configuration::get('myVariable'); // : retrieves a specific value from the database.
        //Configuration::getMultiple(array('myFirstVariable', 'mySecondVariable', 'myThirdVariable')); // : retrieves several values from the database, and returns a PHP array.
        //Configuration::updateValue('myVariable', $value); // : updates an existing database variable with a new value. If the variable does not yet exist, it creates it with that value.
        //Configuration::deleteByName('myVariable'); // : deletes the database variable.
        //

    }

    public function hookActionPaymentConfirmation($params) {
        $this->log->warning('*** **** *** *** *** *** *** *** ** *** *** *** *** *** ** *********************');

        $this->log->warning('Se lanzo el hook de ActionPaymentConfirmation revisar: '.json_encode($params));
        try {
            /**
             * Recupero los datos de la orden por el id
             */
            $order = new OrderCore($params['id_order']);
            $dOrder = $order->getFields();

            // Asi saco los datos de la direccion con la que se hizo la orden, lo comento por que por le momento no es necesario
            // $address = new Address(intval($params['cart']->id_address_delivery));

            // el array map funciona parecido al _.map de underscore
            $productos = array_map(function ($val){
                $precioBase = (int)$val['price_without_reduction'];
                $precioDescuento = (int)$val['price_with_reduction'];
                $descuento = ($precioBase-$precioDescuento)/$precioBase*100;
                return [
                    'referencia' => $val['reference'],
                    'cantidad'   => $val['quantity'],
                    'descuento'  => $descuento
                ];
            }, $params['cart']->getProducts(true) );

            // Datos basico del pedido
            $orden = [
                'id'             => $dOrder['id_order'],
                'reference'      => $dOrder['reference'], // La referencia de la orden, esto lo asigna el prestashp para identificar el pedido
                'fecha_creacion' => $dOrder['date_add'],
                'productos'      => $productos
            ];

            /**
             * Con la clase DbQuery hago una consulta a la bd y traigo los datos
             * del cliente por id, el id del cliente lo saque de la orden que
             * consulte previamente para mas info mirar aqui:
             * http://doc.prestashop.com/display/PS15/Diving+into+PrestaShop+Core+development#DivingintoPrestaShopCoredevelopment-TheDBQueryclass
             */
            $query = new DbQuery();
            $query
                ->select('*')
                ->from('customer')
                ->where('`id_customer` = '.$dOrder['id_customer'])
                ->orderBy('`id_customer` ASC');
            $dCustomer = Db::getInstance()->executeS($query);

            /**
             * Esta es la instancia de la clase con la que guardo los datos en la bd
             * @var SoapSapDbUtils
             */
            $wsDataObj = new SoapSapDbUtils();

            /**
             * Instancia de la clase con la que manejo las conexiones al webservice
             */
            $wsConnection = new WebServiceHandle();
            /**
             * Seteo los datos basicos del cliente
             */
            $wsConnection->cliente = [
                'codCliente' => $dCustomer[0]['cedula'],
                'nombre'     => $dCustomer[0]['firstname'],
                'apellidos'  => $dCustomer[0]['lastname'],
                'email'      => $dCustomer[0]['email']
            ];


            /**
             * defino las urls de los wsdl de los servicios del webservice
             */
            $wsConnection->loginService = 'http://b1ws.igbcolombia.com/B1WS/WebReferences/LoginService.wsdl';
            $wsConnection->ordersService = 'http://b1ws.igbcolombia.com/B1WS/WebReferences/OrdersService.wsdl';


            /**
             * Me conecto al webservice y verifico si hay error o no
             */
            if( $wsConnection->login() ){
                $this->log->info('***************************Se abrio correctamente la sesion SAP**************************************');
                $wsDataObj->sessionId = $wsConnection->sessionId;
                /**
                 * Seteo la cedula en la instancia del active record para mas
                 * adelante guardarlo en la base de datos
                 */
                $wsDataObj->codCliente = $dCustomer[0]['cedula'];
            }else{
                $this->log->error('***************************No se pudo iniciar la sesion en SAP.**************************************');
            }
            /**
             * Llamo el metodo que me envia la orden al webservice y verifico que no haya error
             */
            $numOrden = $wsConnection->order($orden);
            if( $numOrden ){
                /**
                 * Guardo el resto de datos en la instancia del active record (orm)
                 */
                $wsDataObj->created_at = date("Y-m-d H:i:s");
                $wsDataObj->numOrden = $numOrden;
                $wsDataObj->numOrdenPS = $dOrder['id_order'];
            }

            /**
             * Llamo al metodo de deslogueo del webservice que me cierra la sesion en sap
             * y verifico que no hayan errores
             */
            if( $wsConnection->logout() ){
                /**
                 * ejecuto el metodo del active record que me guarda el objeto en la bd
                 * si hay algun error guardo la info en un log
                 */
                $wsData = $wsDataObj->add();
                if( !wsData ){
                    $this->log->error('fallo al crear el reg en BD', $wsData );
                }
                $this->log->info('****************************Se cerro la sesion correctamente en SAP***********************************');
            }else{
                $this->log->error('***************************No se pudo cerrar la sesion en SAP*****************************************');
            }

            $this->log->info('-Datos consulta cliente: '. json_encode($dCustomer[0]) );

            $this->log->info('-productos: '. json_encode( $params['cart']->getProducts(true) ) );

            $this->log->info('-Order: '. json_encode($dOrder) );

        } catch (Exception $e) {
            $this->log->error('Hubo un error al tratar de ahcer la cinsylta del cliente.', $e);
        }

        $this->log->warning('*** **** *** *** *** *** *** *** ** *** *** *** *** *** ** *********************');

        return true;
    }

    public function hookActionOrderStatusUpdate($params) {
        $this->log->info('Se activo el hook de ActionOrderStatusUpdate', $params);
        return true;
    }

    /**
     * After an order has been validated. Doesn't necessarily have to be paid.
     * Called during the new order creation process, right after it has been created.
     * @param  [array] $params  [
     *                             'cart' => (object) Cart object,
     *                             'order' => (object) Order object,
     *                             'customer' => (object) Customer object,
     *                             'currency' => (object) Currency object,
     *                             'orderStatus' => (object) OrderState object
     *                          ];
     */
    public function hookActionValidateOrder($params) {
        $this->log->info('hookActionValidateOrder '. json_encode($params) );
        return true;
    }

    public function hookActionCustomerAccountAdd($params){
        $this->log->warning('- Se lanzo el hook de ActionCustomerAccountAdd revisar: '.json_encode($params));

        $user = [
            'id'       => $params['_POST']['cedula'],
            'fullName' => $params['_POST']['customer_firstname'].' '.$params['_POST']['customer_lastname'],
            'name'     => $params['_POST']['customer_firstname'],
            'lastName' => $params['_POST']['customer_lastname'],
            'address'  => $params['_POST']['address1'].'-'.$params['_POST']['city'],
            'email'    => $params['_POST']['email'],
            'telCel'   => $params['_POST']['phone_mobile'],
            'telHome'  => $params['_POST']['phone'],
            'dateAdd'  => $params['newCustomer']->date_add
        ];

        /**
        * Instancia de la clase con la que manejo las conexiones al webservice
        */
        $wsConnection = new WebServiceHandle();
        /**
        * defino las urls de los wsdl de los servicios del webservice
        */
        $wsConnection->loginService = 'http://b1ws.igbcolombia.com/B1WS/WebReferences/LoginService.wsdl';
        /**
        * Me conecto al webservice y verifico si hay error o no
        */
        if( $wsConnection->login() ){
           $this->log->info('***************************Se abrio correctamente la sesion SAP**************************************');

            /**
             * defino las urls de los wsdl de los servicios del webservice
             */
            $wsConnection->newUserService = 'http://b1ws.igbcolombia.com/B1WS/WebReferences/BusinessPartnersService.wsdl';
            $nitUser = $wsConnection->newUser($user);

            if( $nitUser ){
                $this->log->info('SE CREO EL CLIENTE CORRECTAMENTE');
            }

            /**
            * Llamo al metodo de deslogueo del webservice que me cierra la sesion en sap
            * y verifico que no hayan errores
            */
            if( $wsConnection->logout() ){
               $this->log->info('****************************Se cerro la sesion correctamente en SAP***********************************');

            }else{
               $this->log->error('***************************No se pudo cerrar la sesion en SAP*****************************************');
            }

        }else{
           $this->log->error('***************************No se pudo iniciar la sesion en SAP.**************************************');
        }

        return true;
    }

}
