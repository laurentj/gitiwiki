<?php

/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012-2021 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


use Jelix\Installer\Module\API\InstallHelpers;

class gitiwikiModuleInstaller extends \Jelix\Installer\Module\Installer {

    function install(InstallHelpers $helpers) {

        $conf = $helpers->getLocalConfigIni();
        if (!$conf->isSection('gitiwikiGenerators')) {
            $ini = new \Jelix\IniFile\IniModifier(__DIR__.'/config.ini');
            $conf->import($ini);
        }
    }
}