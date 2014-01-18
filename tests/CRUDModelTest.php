<?php

require_once __DIR__ . "/../CRUD.php";

use \CRUD\CRUD;
use \CRUD\CRUDModel;


class UserModel extends CRUDModel
{
    protected static $table = 'user';
    protected static $pk = 'id';
    public static $schema = '
        CREATE TABLE IF NOT EXISTS user (
            id INTEGER PRIMARY KEY,
            name TEXT,
            password TEXT
        );';
}


class CRUDModelTest extends PHPUnit_Framework_TestCase
{
    private static $dbFile = '/tmp/test.db';

    public function setup()
    {
        CRUD::configure("sqlite://" . static::$dbFile);
        $cur = CRUD::getCursor();
        $cur->exec(UserModel::$schema);

        $this->cur = $cur;
    }

    public static function tearDownAfterClass()
    {
        \unlink(static::$dbFile);
    }

    public function testCreate()
    {
        $id = UserModel::create(array(
            'name' => 'test',
            'password' => 'foobar'
        ));
        $r = $this->cur->query("SELECT count(*) FROM user WHERE id = $id");
        $this->assertEquals(1, $r->fetch()[0]);
        $r = $this->cur->query('SELECT count(*) FROM user WHERE password = "foobar"');
        $this->assertEquals(1, $r->fetch()[0]);
        
        $id = UserModel::create(array(
            'id' => 42,
            'name' => 'test',
            'password' => 'foobar'
        ));
        $r = $this->cur->query("SELECT count(*) FROM user WHERE id = $id");
        $this->assertEquals(1, $r->fetch()[0]);
    }

    public function testReadOne()
    {
        $this->cur->exec('INSERT INTO user (name, password) VALUES ("t", "t")');
        $id = $this->cur->lastInsertId();
        $record = UserModel::readOne($id);
        $this->assertEquals('t', $record['name']);
        $this->assertEquals('t', $record['password']);
        $this->assertEquals($id, $record['id']);

        $record = UserModel::readOne(array(
            'name' => 't'
        ));
        $this->assertEquals('t', $record['name']);
        $this->assertEquals('t', $record['password']);
        $this->assertEquals($id, $record['id']);
    }

    public function testReadMany()
    {
        $this->cur->exec('INSERT INTO user (name, password) VALUES ("m", "t")');
        $this->cur->exec('INSERT INTO user (name, password) VALUES ("m", "t")');
        $records = UserModel::readMany(array(
            'name' => 'm'
        ));
        $this->assertEquals(2, count($records));
    }

    public function testUpdate()
    {
        $this->cur->exec('INSERT INTO user (name, password) VALUES ("m", "t")');
        $id = $this->cur->lastInsertId();
        $record = UserModel::update($id, array(
            'name' => 'updated'
        ));
        $r = $this->cur->query('SELECT count(*) FROM user WHERE name = "updated"');
        $this->assertEquals(1, $r->fetch()[0]);
        
        $record = UserModel::update($id, array(
            'id' => '1',
            'name' => 'updated'
        ));
        $r = $this->cur->query('SELECT count(*) FROM user WHERE name = "updated"');
        $this->assertEquals(1, $r->fetch()[0]);
    }

    public function testDelete()
    {
        $this->cur->exec('INSERT INTO user (name, password) VALUES ("t", "t")');
        $id = $this->cur->lastInsertId();
        $this->assertTrue(UserModel::delete($id));
        $r = $this->cur->query("SELECT count(*) FROM user WHERE id = $id");
        $this->assertEquals(0, $r->fetch()[0]);

        $this->cur->exec('INSERT INTO user (name, password) VALUES ("t", "t")');
        $r = $this->cur->query('SELECT count(*) FROM user WHERE name = "t"');
        $this->assertTrue(UserModel::delete(array(
            'name' => 't'
        )));
        $r = $this->cur->query('SELECT count(*) FROM user WHERE name = "t"');
        $this->assertEquals(0, $r->fetch()[0]);
    }
}
