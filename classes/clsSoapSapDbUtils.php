<?php

class SoapSapDbUtils extends ObjectModel {
    public $id;
    public $sessionId;
    public $codCliente;
    public $created_at;

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
}

