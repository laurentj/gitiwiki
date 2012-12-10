<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brice Tencé
 */

require_once('sphinxapi.php');

class sphinx {

    private $jCacheProfile = 'sphinxsearch';
    private $maxQueryTime = 5000; //ms

    public function resetSources() {
        jCache::delete( 'documentId' );
    }

    public function xmlpipe2source( $listenerParams ) {

        $event = jEvent::notify('ListFields', $listenerParams);
        $fieldsList = $event->getResponse();

        $event = jEvent::notify('ListContent', $listenerParams);
        $sourcesList = $event->getResponse();

        $firstFields = null;
        $xmlWriterInst = null;
        foreach( $sourcesList as $sourceDocs ) {
            if( $sourceDocs ) {
                if( $firstFields === null ) {
                    $firstFields = array_shift( $fieldsList );
                    $currFields = $firstFields;
                    $xmlWriterInst = new xmlWriter;
                    $xmlWriterInst->openMemory();
                    $xmlWriterInst->setIndent(true);
                    $this->xmlpipe2header( $xmlWriterInst, $firstFields );
                    if( !jCache::get( 'documentId', $this->jCacheProfile ) ) {
                        jCache::set( 'documentId', 1, 0, $this->jCacheProfile );
                    }
                } else {
                    $currFields = array_shift( $fieldsList );
                }

                if( ! array_diff( $firstFields, $currFields ) == array() ) {
                    trigger_error( "jEvent got fields (" . implode(',', $currFields) .
                        ") which does not correspond to '" . implode(',', $firstFields) . "'. Skipping." , E_USER_WARNING );
                    continue;
                }

                foreach( $sourceDocs as $sourceDoc ) {
                    $documentId = jCache::get( 'documentId', $this->jCacheProfile );
                    $this->xmlpipe2document( $xmlWriterInst, $documentId, $sourceDoc['content'] );
                    jCache::set( $documentId, $sourceDoc['infos'], 0, $this->jCacheProfile );
                    jCache::increment( 'documentId', 1, $this->jCacheProfile );
                }
            } else {
                array_shift( $fieldsList );
            }
        }
        if( $xmlWriterInst !== null ) {
            $this->xmlpipe2footer( $xmlWriterInst );
            return $xmlWriterInst->flush();
        }
    }

    private function xmlpipe2header( $xmlWriterInst, $fields ) {

        $xmlWriterInst->startDocument('1.0', 'utf-8');
        $xmlWriterInst->startElement('sphinx:docset');

        $xmlWriterInst->startElement('sphinx:schema');

        foreach( $fields as $field ) {
            $xmlWriterInst->startElement('sphinx:field');
            $xmlWriterInst->writeAttribute("name", $field);
            $xmlWriterInst->endElement();
        }

        $xmlWriterInst->endElement();
    }

    private function xmlpipe2document( $xmlWriterInst, $id, $fieldsContent ) {

        $xmlWriterInst->startElement('sphinx:document');
        $xmlWriterInst->writeAttribute("id", $id);

        foreach( $fieldsContent as $fieldName => $fieldContent ) {
            $xmlWriterInst->startElement( $fieldName );
            $xmlWriterInst->text( $fieldContent );
            $xmlWriterInst->endElement();
        }

        $xmlWriterInst->endElement();
    }

    private function xmlpipe2footer( $xmlWriterInst ) {
        $xmlWriterInst->endElement();
    }




    public function resultsInfos( $searchString, $index, $offset=0, $limit=100, &$stats=null ) {
 
        $sp = new SphinxClient();
        $sp->SetServer('localhost', 9312);

        // SPH_MATCH_ALL will match all words in the search term
        $sp->SetMatchMode(SPH_MATCH_ALL);

        $sp->SetArrayResult(true);

        $sp->SetLimits( intval($offset), intval($limit) );
        $sp->SetMaxQueryTime( $this->maxQueryTime );
        $results = $sp->Query( $searchString, $index );
        if( !$results ) {
            $error = $sp->GetLastError();
            if( $error ) {
                trigger_error( "Sphinx search error : $error", E_USER_WARNING ); 
            }
            $warning = $sp->GetLastWarning();
            if( $warning ) {
                trigger_error( "Sphinx search warning : $warning", E_USER_WARNING ); 
            }
            return array();
        }

        if( ! array_key_exists( 'matches', $results ) ) {
            return array();
        }

        $resInfos = array();
        foreach( $results['matches'] as $res ) {
            $infos = jCache::get( $res['id'], $this->jCacheProfile );
            if( $infos ) {
                $resInfos[] = $infos;
            }
        }
        $stats = array( 'total' => $results['total'] );

        return $resInfos;
    }


    public function getHighlighted( $docs, $index, $words, $limitByDoc=100,
                                    $beforeMatch='<span class="searchMatch">',
                                    $afterMatch='</span>', $sep='<span class="searchMatchSep">&hellip;</span>' ) {

        $sp = new SphinxClient();
        $sp->SetServer('localhost', 9312);
        $fakeBeforeMatch = '@@fakeBeforeMatch@@';
        $fakeAfterMatch = '@@fakeAfterMatch@@';
        $fakeSep = '@@fakeMatchSep@@';
        $options = array(
            'before_match'          => $fakeBeforeMatch,
            'after_match'           => $fakeAfterMatch,
            'chunk_separator'       => $fakeSep,
            'limit'                 => $limitByDoc,
        );

        $highlighted = $sp->BuildExcerpts( $docs, $index, $words, $options );
        $extracts = array();
        if( $highlighted ) {
            //BuildExcerpts will decode HTML entities. Have to encode them again ...
            foreach( $highlighted as $doc ) {
                $doc = htmlspecialchars( $doc );
                $doc = str_replace( htmlspecialchars($fakeSep), $sep, $doc );
                $doc = str_replace( htmlspecialchars($fakeBeforeMatch), $beforeMatch, $doc );
                $doc = str_replace( htmlspecialchars($fakeAfterMatch), $afterMatch, $doc );
                $extracts[] = $doc;
            }
        }
        return $extracts;
    }

}

