<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brice Tencé
 */

class gtwSphinxSourceListener extends jEventListener {

   function onListFields ($event) {
       $type = $event->getParam('type');
       if( $type == 'gitiwiki' ) {
           $event->Add( array('title', 'page') );
       }
   }


   function onListContent ($event) {
       $type = $event->getParam('type');
       if( $type == 'gitiwiki' ) {
           $repo = $event->getParam('repo');
           $bookindex = $event->getParam('bookindex');
           jClasses::inc( 'gtwsphinx~gtwSphinxSource' );
           $sphinxSrc = new gtwSphinxSource( $repo, $bookindex );
           $event->Add( $sphinxSrc->listContent() );
       }
   }
}
