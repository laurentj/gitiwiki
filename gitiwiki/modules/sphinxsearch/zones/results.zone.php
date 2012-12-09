<?php
/**
* @package   sphinxsearch
* @author    Brice Tencé
* @copyright 2012 Brice Tencé
* @licence  http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public Licence, see LICENCE file
*/

class resultsZone extends jZone {
    protected $_tplname = 'results.zone';

    protected function _prepareTpl(){

        //expected params : total, limit, results, page, string, searchSel, searchParams

        $total = $this->param('total');
        $limit = $this->param('limit');
        $maxPage = ceil($total/$limit);
        $this->_tpl->assign( 'maxPage', $maxPage );
    }
}
