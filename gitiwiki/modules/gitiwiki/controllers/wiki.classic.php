<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


//require_once(dirname(__FILE__).'/../classes/glip/glip.php');
//jClasses::inc('wikiFile');

class wikiCtrl extends jController {
    /**
    *
    */
    function index() {
        $rep = $this->getResponse('html');

        $rep->body->assign('MAIN', '<p> <a href="'.jUrl::get('gitiwiki~wiki:page', array('repository'=>'default', 'page'=>'page'), jUrl::XMLSTRING).'">a page</a></p>');

        return $rep;
    }


    function page() {
        $rep = $this->getResponse('html');

        $rep->body->assign('MAIN', '<p> the page</p>');

        return $rep;
    }



    protected function getFile($path, $repoName) {
        $conf = jApp::config();
        if (!isset($conf->{'jwiki_'.$name})) {
            return null;
        }

        $conf = $conf->{'jwiki_'.$name};

        $repo = new Git($conf['path']);
        if ($commit = $this->param('commit')) {
            
        }
        else
            $commit = $repo->getTip($conf['branch']);
        
        
        return new wikiFile($path, $repo, $commit);
        
        
    }

}
