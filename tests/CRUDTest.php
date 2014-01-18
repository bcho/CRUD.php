<?php

require_once __DIR__ . "/../CRUD.php";

use \CRUD\CRUD;


class CRUDTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dsnProvider
     */
    public function testDSNConfigurate($driverName, $dsn)
    {
        CRUD::configure($dsn);
        $db = CRUD::getCursor();
        $this->assertEquals($driverName,
            $db->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $db = null;

        CRUD::configure('dsn', $dsn);
        $db = CRUD::getCursor();
        $this->assertEquals($driverName,
            $db->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $db = null;

        CRUD::configure(array(
            'dsn' => $dsn
        ));
        $db = CRUD::getCursor();
        $this->assertEquals($driverName,
            $db->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $db = null;
    }

    public function dsnProvider()
    {
        return array(
            array('sqlite', 'sqlite::memory:'),
            // array('mysql', 'mysql:host=localhost;dbname=test')
        );
    }
}
