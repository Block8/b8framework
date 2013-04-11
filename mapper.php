<?php

require_once('b8/Registry.php');
require_once('b8/View.php');
require_once('b8/View/UserView.php');
require_once('b8/Database.php');
require_once('b8/Database/Map.php');

b8\Registry::getInstance();
b8\Database::setDetails('b8', 'b8', 'block8');
b8\Database::setReadServers(array('localhost'));

$map = new b8\Database\Map(b8\Database::getConnection('read'));
$rtn = $map->generate();

foreach($rtn as $table => $t)
{
	$modelTemplate = new b8\View\UserView(file_get_contents(B8_PATH . 'Database/Generator/ModelTemplate.phtml'));
	$modelTemplate->appNamespace    = 'Api';
	$modelTemplate->name            = $table;
	$modelTemplate->table           = $t;

	$model = $modelTemplate->render();

	$baseModelTemplate = new b8\View\UserView(file_get_contents(B8_PATH . 'Database/Generator/BaseModelTemplate.phtml'));
	$baseModelTemplate->appNamespace    = 'Api';
	$baseModelTemplate->name            = $table;
	$baseModelTemplate->table           = $t;

	$baseModel = $baseModelTemplate->render();

	print $baseModel . PHP_EOL;
}