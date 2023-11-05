<?php
error_reporting(E_ALL);
require_once(__DIR__.'/../../gitiwiki/application.init.php');

jApp::setEnv('jelixtests');
if (file_exists(jApp::tempPath())) {
    jAppManager::clearTemp(jApp::tempPath());
} else {
    jFile::createDir(jApp::tempPath(), intval("775",8));
}
jApp::loadConfig('index/config.ini.php');
