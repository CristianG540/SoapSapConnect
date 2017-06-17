<?php

class SoapSapDbUtils extends ObjectModel {
    public $id;
    public $sessionId;
    public $codCliente;
    public $created_at;
    protected $bar;

    /**
     * @see   ObjectModel::$definition
     */
    public static $definition = [
        'table'     => "soapSapConnect_order",
        'primary'   => "id",
        'multilang' => false,
        'fields'    => [
            'sessionId'  => [
                'type' => self::TYPE_STRING,
                'required' => true
            ],
            'codCliente' => [
                'type' => self::TYPE_STRING,
                'required' => true
            ],
            'created_at' => [
                'type' => self::TYPE_STRING,
                'required' => false
            ]
        ]

    ];

    public function __get($property)
    {
        switch ($property)
        {
            case 'bar':
                return $this->bar;
            //etc.
        }
    }

    public function __set($property, $value)
    {
        switch ($property)
        {
            case 'bar':
                $this->bar = $value;
                break;
            //etc.
        }
    }
}
/*
$foo = new Foo();
$foo->bar = 1;
$foo->bar++;
*/
