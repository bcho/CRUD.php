<?php

require_once 'CRUD.php';

use CURD;


CRUD::configure('sqlite::memory:');

// `get_db` return a `PDO` instance.
CRUD::getCursor()->exec("
    CREATE TABLE IF NOT EXISTS user (
        id INTEGER PRIMARY KEY,
        name TEXT,
        password TEXT
    );
");


class UserModel extends CURDModel
{
    /**
     * table name
     */
    protected $table = 'user';

    /**
     * primary key
     */
    protected $pk = 'id';
}


// create a record
$id = UserModel::create(array(
    'name' => 'larry',
    'password' => 'larryspassword'
));
var_dump($id === 1);

// read a record
$user = UserModel::readOne(array(
    'id' => 1
));
// or
$user = UserModel::readOne(1);
// should be a associate array
// array(
//      'id' => 1,
//      'name' => 'larry',
//      'password' => 'larryspassword'
// )
var_dump($user);

// or
$users = UserModel::readMany(array(
    'name' => 'larry'
));
// should be a records array
// array(
//      1 => array(
//              'id' => 1,
//              'name' => 'larry',
//              'password' => 'larryspassword'
//          )
// )
var_dump($user);

// update a record
$user = UserModel::readOne(array(
    'id' => 1
));
$user['password'] = 'larrysnewpassword';
$user = UserModel::update($user['id'], $user);
// should be a associate array
// array(
//      'id' => 1,
//      'name' => 'larry',
//      'password' => 'larrysnewpassword'
// )
var_dump($user);

// delete a record
UserModel::delete(array(
    'id' => 1
));
// or
UserModel::delete(1);
