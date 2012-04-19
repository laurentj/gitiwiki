<?php
/**
* @package   gitiwiki
* @subpackage 
* @author    Laurent Jouanneau
* @copyright 2012 Laurnent Jouanneau
* @link      http://innophi.com
* @license    All rights reserved
*/

require_once (dirname(__FILE__).'/../application.init.php');

checkAppOpened();

require_once (JELIX_LIB_CORE_PATH.'jCmdlineCoordinator.class.php');

require_once (JELIX_LIB_CORE_PATH.'request/jCmdLineRequest.class.php');

jApp::setCoord(new jCmdlineCoordinator('cmdline/manage.ini.php'));
jApp::coord()->process(new jCmdLineRequest());

