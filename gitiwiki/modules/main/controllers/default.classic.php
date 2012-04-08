<?php
/**
* @package   gitiwiki
* @subpackage main
* @author    Laurent Jouanneau
* @copyright 2012 Laurnent Jouanneau
* @link      http://innophi.com
* @license    All rights reserved
*/

class defaultCtrl extends jController {
    /**
    *
    */
    function index() {
        $rep = $this->getResponse('html');
        $rep->body->assign('MAIN', '<h2>Home page</h2><ul>
                           <li><a href="'.jUrl::get('gitiwiki~default:index', array(), jUrl::XMLSTRING).'">wikis list</a></li>
                           </ul>');
        return $rep;
    }
}
