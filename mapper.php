<?php

require_once('b8/Registry.php');
require_once('b8/Model.php');
require_once('b8/View.php');
require_once('b8/View/UserView.php');
require_once('b8/Database.php');
require_once('b8/Database/Map.php');
require_once('b8/Database/CodeGenerator.php');
require_once('b8/Database/Generator.php');

b8\Registry::getInstance();
b8\Database::setDetails('gen', 'b8', 'block8');
b8\Database::setReadServers(array('localhost'));
b8\Database::setWriteServers(array('localhost'));

$g = new b8\Database\Generator(b8\Database::getConnection('write'), 'Api', '/www/b8/Api/Model/Base/');
$g->generate();