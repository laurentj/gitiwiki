<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


class gitiwikiModuleInstaller extends jInstallerModule {

    function install() {
        if ($this->firstConfExec()) {
            $conf = $this->config->getMaster();
            if (!$conf->isSection('gitiwikiGenerators')) {
                $ini = new jIniFileModifier(dirname(__FILE__).'/config.ini');
                $conf->merge($ini);
            }
        }
    }
}