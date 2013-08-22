<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012-2013 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/
namespace Gitiwiki\Storage;

class Directory extends FileAbstract {

    function exists() {
        return ($this->treeGitObject != null);
    }

    function save($message, $authorName, $authorMail) {
        throw new \Exception('not implemented');
    }

    function moveTo($newPath, $message, $authorName, $authorMail, $commit = null) {
        throw new \Exception('not implemented');
    }

    function remove($message, $authorName, $authorMail) {
        throw new \Exception('not implemented');
    }

    function isStaticContent() {
        return false;
    }
    function getName() {
        return basename($this->path);
    }
    function getPathFileName() {
        return $this->path;
    }

    /**
     * @return string the real path + the file name into the repository (basepath + path + name)
     */
    function getRealPathFileName() {
        $conf = $this->repo->config();
        return $conf['basepath'].ltrim($this->path,'/');
    }

    function getHtmlContent($basePath) {
        if (!$this->treeGitObject)
            return '';
        $ct = '<ul>';
        $conf = $this->repo->config();
        $extList = $conf['branches'][$this->commitHash->hex()]['multiviews'];

        foreach($this->treeGitObject as $name=>$node) {
            $pos = strrpos($name, '.');
            if ($pos !== false) {
                $ext = substr($name, $pos);
                if (in_array($ext, $extList))
                    $name = substr($name, 0, $pos);
            }
            $ct .= '<li><a href="'.$basePath.$this->path.'/'.$name.'">'.$name.'</a></li>';
        }
        return $ct . '</ul>';
    }

    function getContent() {
        if (!$this->treeGitObject)
            return '';
        $ct = array();
        $conf = $this->repo->config();
        $extList = $conf['branches'][$this->commitHash->hex()]['multiviews'];

        foreach($this->treeGitObject as $name=>$node) {
            $pos = strrpos($name, '.');
            if ($pos !== false) {
                $ext = substr($name, $pos);
                if (in_array($ext, $extList))
                    $name = substr($name, 0, $pos);
            }
            $ct[] = $name;
        }
        return $ct;
    }
}