<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

class defaultCtrl extends jController {
    /**
    *
    */
    function index() {

        $config = jApp::config()->gitiwiki;
        if (!isset($config['showRepositoriesList']) || !$config['showRepositoriesList']) {
            try {
                $defaultRepo = \jProfiles::get('gtwrepo', 'default');
                if ($defaultRepo && $defaultRepo['_name']) {
                    if (isset($defaultRepo['urlName']) && $defaultRepo['urlName']) {
                        $name = $defaultRepo['urlName'];
                    }
                    else {
                        $name = $defaultRepo['_name'];
                    }
                    $rep = $this->getResponse('redirect');
                    $rep->action = 'gitiwiki~wiki:page';
                    $rep->params = array('repository'=> $name,'page'=> '/');
                    return $rep;
                }
            }
            catch (Exception $e) {
            }
        }

        $rep = $this->getResponse('html');

        $conf = \Jelix\IniFile\Util::read(jApp::varConfigPath('profiles.ini.php'));
        $list = array();
        foreach($conf as $prop=> $val) {
            if (is_array($val) && preg_match('/^gtwrepo\:(.*)$/', $prop, $m)) {
                if (isset($val['title']))
                    $list[$m[1]] = $val['title'];
                else
                    $list[$m[1]] = $m[1];
            }
        }

        $tpl = new jTpl();
        $tpl->assign ('repolist', $list);
        $rep->body->assign('MAIN', $tpl->fetch('repolist'));
        return $rep;
    }
}
