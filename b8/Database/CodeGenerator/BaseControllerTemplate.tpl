<?php

/**
 * {@table.php_name} base controller for table: {@name}
 */

namespace {@appNamespace}\Controller\Base;
require_once(APPLICATION_PATH . '{@appNamespace}/Controller/Base/{@table.php_name}ControllerBase.php');
use b8\Controller\RestController;

/**
 * {@table.php_name} base Controller
 * @see {@appNamespace}\Controller\{@table.php_name}
 * @uses {@appNamespace}\Store\{@table.php_name}Store
 * @uses {@appNamespace}\Model\{@table.php_name}
 */
class {@table.php_name}ControllerBase extends RestController
{
	protected $_modelName	    = '{@table.php_name}';
	protected $_resourceName	= '{@table.php_name.toLowerCase}s';
	protected $_modelClass	    = '\{@appNamespace}\Model\{@table.php_name}';
}
