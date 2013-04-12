<?php

require_once('b8/Registry.php');
require_once('b8/View.php');
require_once('b8/View/UserView.php');
require_once('b8/Database.php');
require_once('b8/Database/Map.php');
require_once('b8/Database/CodeGenerator.php');

b8\Registry::getInstance();
b8\Database::setDetails('b8', 'b8', 'block8');
b8\Database::setReadServers(array('localhost'));

$cg = new b8\Database\CodeGenerator(b8\Database::getConnection('read'), 'Api', '/www/b8/Api/');
$cg->generateModels();
$cg->generateStores();
$cg->generateControllers();