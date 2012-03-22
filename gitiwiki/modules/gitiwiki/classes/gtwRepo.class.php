<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/

require_once(dirname(__FILE__).'/glip/glip.php');

class gtwRepo {
    /**
     * @var array
     * configuration parameters of the repository
     */
    protected $config;
    
    /**
     * @var Git
     * the git object representing the repository
     */
    protected $repo;


    function __construct($repoName) {
        $conf = jApp::config();
        if (!isset($conf->{'gwrepo_'.$repoName})) {
            throw new Exception ('the repository does not exists');
        }

        $this->config = $conf->{'gwrepo_'.$repoName};

        $this->repo = new Git($this->config['path']);
    }

    /**
     * searches a file at the given path. It has a "multiview" support, and
     * has a "redirection" support by checking the meta file corresponding to th e given path
     * @param string $path  the path of the file into the repository
     * @param string $commitId the bin hash of a commit corresponding to a branch tip, if we want to search in
     *                  an other branch than the default branch
     */
    function getFile($path, $commitId = null) {

        // verify that the path does not contain names begining by '.' -> not found
        foreach (explode('/', $path) as $p) {
            if (strlen($p) && $p[0] == '.') {
                //jLog::log("get $path : forbidden");
                return null;
            }
        }

        if (!$commitId) {
            $commitId = $this->repo->getTip($this->config['branch']);
        }

        // extract the dir path and the file name from the given path
        if (substr($path, -1,1) == '/') {
            $name = 'index';
            $implicitName  = true;
        }
        else {
            $name = basename($path);
            $path = dirname($path);
            if ($path == '.')
                $path = '';
            $implicitName = false;
        }

        // retrieve the object corresponding to the dir
        $commit = $this->repo->getObject($commitId);
        $hash = $commit->find($path);
        if (!$hash) {
            //jLog::log("get $path : don't find the path at the given commit");
            return null;
        }

        $treeObject = $this->repo->getObject($hash);
        if (!$treeObject) {
            //jLog::log("get $path : don't find the tree object");
            return null;
        }

        if (! ($treeObject instanceof GitTree)) {
            // the path does not correspond to a directory, but to a file
            // in case when a leading / was provided, so we could ignore this / and
            // display the content of the file
            if ($implicitName) {
                //FIXME return a gtwDirectory ? gtwFile ?
                return array(basename($path), $treeObject->data);
            }
            else {
                // the directory of the file is not a directory. Error
                throw new Exception ('Unexpected content at this path');
            }
        }

        // ok, the dir path is really a directory
        $node = null;

        // does the given filename correspond to a file/dir ?
        if (isset($treeObject->nodes[$name])) {
            $node = $treeObject->nodes[$name];
            if (!$node->is_dir) {
                // this is a file, good !
                // FIXME: returns a gtwFile
                $fileObject = $this->repo->getObject($node->object);
                return array($name, $fileObject->data);
            }

            // the given "path/name" is a directory

            if ($implicitName)
                // error we don't expect to find a directory 'index' under the given path
                throw new Exception ('Unexpected content at this path');

            //jLog::log("get $path/$name : it is a directory. Try multiview for $path/$name/index");

            // so the path indicates a directory
            // is there a file with the same name + a known extension?
            // => compatibility with dokuwiki storage
            $fileResult = $this->checkMultiview($treeObject, $name);
            if ($fileResult) {
                //jLog::log("get $path/$name  : it is a directory. found $path/$name/".$fileResult[0]);
                // ok, we found a file
                return $fileResult;
            }

            // this sub directory becomes the base directory
            $treeObject = $this->repo->getObject($node->object);
            $name = 'index';
            $node = null;
        }

        //jLog::log("get $path/$name : not found. Try multiview");

        // multiview : try to find a file with the given name + a known extension
        $fileResult = $this->checkMultiview($treeObject, $name);
        if ($fileResult) {
            //jLog::log("get $path/$name : found $path/$name/".$fileResult[0]);
            return $fileResult;
        }
        else {
            //jLog::log("get $path/$name : not found definitively");
            return null;
        }
    }
    
    protected function checkMultiview($treeObject, $name) {
        $metaDirObject = $this->getMetaDirObject($treeObject);

        if ($metaDirObject && isset($metaDirObject->nodes[$name.'.ini'])) {
            // the file does not exist, let's see if there is a redirection
            $obj = $this->repo->getObject($metaDirObject->nodes[$name.'.ini']->object);
            if ($obj) {
                $ini = @parse_ini_string($obj->data, true);
                if (isset($ini['redirection']) && $ini['redirection'] != '') {
                    // FIXME, return a gtwRedirection
                    return array('redirection' => $ini['redirection']);
                }
            }
        }

        $extList = array('.html', '.htm', '.wiki', '.md', '.txt');
        foreach($extList as $ext) {
            $n = $name.$ext;
            if (isset($treeObject->nodes[$n])) {
                $node = $treeObject->nodes[$n];
                if (!$node->is_dir) {
                    $fileObject = $this->repo->getObject($node->object);
                    return array($node->name, $fileObject->data);
                }
            }
            else if ($metaDirObject && isset($metaDirObject->nodes[$n.'.ini'])) {

                // the file does not exist, let's see if there is a redirection
                $obj = $this->repo->getObject($metaDirObject->nodes[$n.'.ini']->object);
                if ($obj) {
                    $ini = @parse_ini_string($obj->data, true);
                    if (isset($ini['redirection']) && $ini['redirection'] != '') {
                        // FIXME, return a gtwRedirection
                        return array('redirection' => $ini['redirection']);
                    }
                }
            }
        }
        return null;
    }

    protected function getMetaDirObject($treeObject) {
        if (!isset($treeObject->nodes['.meta'])) {
            return null;
        }
        $metaDirObject = $treeObject->nodes['.meta'];
        if (!$metaDirObject->is_dir) {
            return null;
        }
        $metaDirObject = $this->repo->getObject($metaDirObject->object);
        if (!$metaDirObject || !($metaDirObject instanceof GitTree)) {
            return null;
        }
        return $metaDirObject;
    }
}