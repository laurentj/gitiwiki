<?php
/**
* @package   gitiwiki
* @subpackage main
* @author    Laurent Jouanneau
* @copyright 2012 Laurnent Jouanneau
* @link      http://innophi.com
* @license    All rights reserved
*/


class mainModuleInstaller extends jInstallerModule {

    function install() {
        //if ($this->firstDbExec())
        //    $this->execSQLScript('sql/install');

        /*if ($this->firstExec('acl2')) {
            jAcl2DbManager::addSubject('my.subject', 'main~acl.my.subject', 'subject.group.id');
            jAcl2DbManager::addRight('admins', 'my.subject'); // for admin group
        }
        */
    }
}