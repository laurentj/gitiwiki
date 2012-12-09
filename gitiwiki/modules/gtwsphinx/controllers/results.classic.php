<?php
/**
* @package   gitiwiki
* @subpackage gtwsphinx
* @author    Brice Tencé
* @copyright 2012 Brice Tencé
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

class resultsCtrl extends jController {
    /**
    *
    */
    function page() {

        $repoName = $this->param('repository');
        $searchString = $this->param('search');
        $page = $this->param('page', 0);
        $limit = $this->param('limit', 0);

        if( $limit < 1 || $page < 1 ) {
            $rep = $this->getResponse( 'redirect' );
            $rep->action = 'gtwsphinx~results:page';
            $rep->params = array( 'repository'=>$repoName, 'search'=>$searchString, 'page'=>1, 'limit'=>10 );
            return $rep;
        }

        $rep = $this->getResponse('html');

        jClasses::inc('gitiwiki~gtwRepo');
        $repo = new gtwRepo($repoName);
        $repoConfig = $repo->config();
        $basePath = jUrl::get('gitiwiki~wiki:page@classic', array('repository'=>$repo->getName(), 'page'=>''));

        $sphinxIndex = $repoConfig['sphinxIndex'];

        $sphinxSrv = jClasses::getService( 'sphinxsearch~sphinx' );
        $resultsInfos = $sphinxSrv->resultsInfos( $searchString, $sphinxIndex, ($page-1)*$limit, $limit, $searchStats );
        $results = array();
        $titles = array();
        $docs = array();
        foreach( $resultsInfos as $resInfos ) {
            if( $resInfos['repo'] != $repoName ) {
                trigger_error( "Got repo '".$resInfos['repo']."' but asked for '$repoName'.This should not happen !" , E_USER_WARNING );
                continue;
            }
            $file = $repo->findFile( $resInfos['path'] );
            if( ! $file instanceof gtwFile ) {
                trigger_error( "Got path '".$resInfos['path']."' in search results (repo : '$repoName', search string '$searchString') and it does not correspond to a gtwFile.This should not happen !" , E_USER_WARNING );
                continue;
            } else {
                $titles[] = $resInfos['title'];
                $docs[] = $file->getHtmlContent($basePath);
                $results[] = array(
                    'url' => jUrl::get('gitiwiki~wiki:page@classic',
                                            array('repository'=>$repo->getName(), 'page'=>$resInfos['path'])),
                    );
            }
        }

        $highlightedTitles = $sphinxSrv->getHighlighted( $titles, $sphinxIndex, $searchString, 1000 );
        $highlightedExtracts = $sphinxSrv->getHighlighted( $docs, $sphinxIndex, $searchString );
        for( $i=0; $i < count($results); $i++ ) {
            $results[$i]['extract'] = $highlightedExtracts[$i];
            $results[$i]['title'] = $highlightedTitles[$i];
        }

        $tpl = new jTpl();
        $content = jZone::get( 'sphinxsearch~results', array(
            'searchSel' => 'gtwsphinx~results:page',
            'searchParams' => array( 'repository'=>$repoName, 'search'=>$searchString, 'limit'=>10 ),
            'string' => $searchString,
            'results' => $results,
            'page' => $page,
            'limit' => $limit,
            'total' => $searchStats['total'] ) );
        $rep->body->assign('MAIN', $content);
        $rep->body->assign('currentRepoName', $repoName);
        return $rep;
    }
}
