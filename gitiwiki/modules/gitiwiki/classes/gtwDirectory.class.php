<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/


class gtwDirectory extends gtwFileBase {

    function exists() {
        return ($this->treeGitObject != null);
    }

    function save($message, $authorName, $authorMail) {
        throw new Exception('not implemented');
    }

    function moveTo($newPath, $message, $authorName, $authorMail, $commit = null) {
        throw new Exception('not implemented');
    }

    function remove($message, $authorName, $authorMail) {
        throw new Exception('not implemented');
    }

    function isHtmlContent() {
        return true;
    }

    function getContent() {
        if (!$this->treeGitObject)
            return '';
        $ct = '<ul>';
        foreach($this->treeGitObject->nodes as $node) {
            $ct .= '<li>'.$node->name.'</li>';
        }
        return $ct . '</ul>';
    }
}