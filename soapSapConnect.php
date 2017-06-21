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

require_once('vendor/autoload.php');
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
            $this->installDB();
    }

    public function installDB(){
        $dbPrefijo = _DB_PREFIX_;
        return Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS `{$dbPrefijo}soapSapConnect_order` (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `sessionId` VARCHAR(300) NOT NULL COMMENT 'Session id que entrega el sap' ,
                `codCliente` VARCHAR(200) NOT NULL COMMENT 'La cedula del usario cliente que hace la compra' ,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion, mas o menos el momento en que se logea en sap' ,
                `numOrden` varchar(100) DEFAULT NULL COMMENT 'Numero de la orden en sap',
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
            DROP TABLE `{$dbPrefijo}soapSapConnect_order`
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

        return $this->display(__FILE__, 'displayHeaderContent.tpl');
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

    public function hookActionCustomerAccountAdd($params){
        $this->log->warning('Se lanzo el hook ActionCustomerAccountAdd revisar: '.json_encode($params));
    }

    public function hookActionPaymentConfirmation($params) {
        $this->log->warning('Se lanzo el hook de ActionPaymentConfirmation revisar: '.json_encode($params));
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

        $this->log->info('hookActionValidateOrder ', $params );
        $address = new Address(intval($params['cart']->id_address_delivery));
        $productos = array_map(function ($val){
            return [
                'referencia' => $val['reference'],
                'cantidad'   => $val['cart_quantity']
            ];
        }, $params['order']->product_list);
        $orden = [
            'id'             => $params['order']->id,
            'fecha_creacion' => $params['order']->date_add,
            'productos'      => $productos
        ];

        try {
            $wsDataObj = new SoapSapDbUtils();

            $wsConnection = new WebServiceHandle();
            $wsConnection->cliente = [
                'codCliente' => $address->dni,
                'nombre'     => $params['customer']->firstname,
                'apellidos'  => $params['customer']->lastname,
                'email'      => $params['customer']->email,
            ];
            $wsConnection->loginService = 'http://b1ws.igbcolombia.com/B1WS/WebReferences/LoginService.wsdl';
            $wsConnection->ordersService = 'http://b1ws.igbcolombia.com/B1WS/WebReferences/OrdersService.wsdl';


            if( $wsConnection->login() ){
                $this->log->info('***************************Se abrio correctamente la sesion SAP**************************************');
                $wsDataObj->sessionId = $wsConnection->sessionId;
                $wsDataObj->codCliente = $address->dni;
            }else{
                $this->log->error('***************************No se pudo iniciar la sesion en SAP.**************************************');
            }

            $numOrden = $wsConnection->order($orden);
            if( $numOrden ){
                $wsDataObj->created_at = date("Y-m-d H:i:s");
                $wsDataObj->numOrden = $numOrden;
                $wsData = $wsDataObj->add();
                if( !wsData ){
                    $this->log->error('fallo al crear el reg en BD', $wsData );
                }
            }


            if( $wsConnection->logout() ){
                $this->log->info('****************************Se cerro la sesion correctamente en SAP***********************************');
            }else{
                $this->log->error('***************************No se pudo cerrar la sesion en SAP*****************************************');
            }
        } catch (Exception $e) {
            $this->log->error('Hubo un error en el catch de las conexiones al sap.', $e);
        }


        /*
        $wsDataObj = new SoapSapDbUtils();
        $wsDataObj->sessionId = 'test';
        $wsDataObj->codCliente = 'test';
        $wsData = $wsDataObj->add();
        if($wsData){
            $this->log->info('Se creo correctamente el reg en BD', $wsData );
        }else{
            $this->log->erros('fallo al crear el reg en BD', $wsData );
        }
        */

        return true;
    }

}
