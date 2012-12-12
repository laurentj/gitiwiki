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
     
        $conf = jIniFile::read(jApp::configPath('profiles.ini.php'));
        $list = array();
        foreach($conf as $prop=> $val) {
            if (is_array($val) && preg_match('/^gtwrepo\:(.*)$/', $prop, $m)) {
                if (isset($val[$labelAttr]))
                    $list[$val['order']] = array( 'name'=>$m[1], 'label'=>$val[$labelAttr] );
                else
                    $list[$val['order']] = array( 'name'=>$m[1], 'label'=>$m[1] );
            }
        }

        ksort( $list, SORT_NUMERIC );
        if( strtoupper($order) == 'DESC' ) {
            $list = array_reverse( $list );
        }

        $this->_tpl->assign( 'repos', $list );
    }
}
