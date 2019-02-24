<?php
/**
* @package app
* @subpackage app
* @author    Brice Tencé
* @copyright 2012 Brice Tencé
* @link      http://jelix.org
* @licence  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public Licence, see LICENCE file
*/

class reposListZone extends jZone {
    protected $_tplname = 'reposList.zone';
    protected $_useCache = true;

    protected function _prepareTpl(){

        $labelAttr = $this->param( 'labelAttr', 'title' );
        $order = $this->param( 'order', 'asc' ); // 'asc' or 'desc'
        //passing a 'currentRepoName' param will make the corresponding repo <li> to get a "selected" class
     
        $conf = \Jelix\IniFile\Util::read(jApp::varConfigPath('profiles.ini.php'));
        $list = array();
        foreach($conf as $prop=> $val) {
            if (is_array($val) && preg_match('/^gtwrepo\:(.*)$/', $prop, $m)) {
                if (isset($val['urlName']) && $val['urlName']) {
                    $name = $val['urlName'];
                }
                else {
                    $name = $m[1];
                }
                if (isset($val[$labelAttr]))
                    $list[] = array( 'order'=>$val['order'], 'name'=>$name, 'label'=>$val[$labelAttr] );
                else
                    $list[] = array( 'order'=>$val['order'], 'name'=>$name, 'label'=>$m[1] );
            }
        }

        if(strtoupper($order) === 'DESC') {
            usort( $list, array($this, 'desc'));
        }
        else {
            usort( $list, array($this, 'asc'));
        }
        $this->_tpl->assign( 'repos', $list );
    }

    protected function desc($a, $b) {
        $o2 = intval($a["order"]);
        $o1 = intval($b["order"]);
        if ($o1 < $o2) return -1;
        if ($o2 < $o1) return 1;
        return 0;
    }

    protected function asc($a, $b) {
        $o1 = intval($a["order"]);
        $o2 = intval($b["order"]);
        if ($o1 < $o2) return -1;
        if ($o2 < $o1) return 1;
        return 0;
    }
}
