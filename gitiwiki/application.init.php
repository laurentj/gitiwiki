<?php
/**
* @package   gitiwiki
* @subpackage
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

$appPath = __DIR__.'/';
require (__DIR__.'/../lib/jelix/init.php');

jApp::initPaths(
    $appPath,
    $appPath.'www/',
    $appPath.'var/',
    $appPath.'var/log/',
    $appPath.'var/config/',
    $appPath.'scripts/'
);
jApp::setTempBasePath(realpath($appPath.'../temp/gitiwiki/').'/');
