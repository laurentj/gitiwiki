<?php
/**
* @package   gitiwiki
* @subpackage gitiwiki
* @author    Laurent Jouanneau
* @copyright 2012 laurent Jouanneau
* @link      http://jelix.org
* @license    GNU PUBLIC LICENCE
*/
$dirname = dirname(__FILE__);
require_once($dirname.'/glip/glip.php');
require_once($dirname.'/gtwFilebase.class.php');
require_once($dirname.'/gtwFile.class.php');
require_once($dirname.'/gtwDirectory.class.php');
require_once($dirname.'/gtwRedirection.class.php');

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
        if (isset($this->config['generators']) && isset($conf->{$this->config['generators']})) {
            $this->config['generators'] = $conf->{$this->config['generators']};
        }
        else if (isset($conf->gitiwikiGenerators)) {
            $this->config['generators'] = $conf->gitiwikiGenerators;
        }
        else {
            $this->config['generators'] = array();
        }

        $this->repo = new Git($this->config['path']);
    }

    /**
     * @return Git
     */
    function git() {
        return $this->repo;
    }

    function config() {
        return $this->config;
    }

    /**
     * searches a file at the given path. It has a "multiview" support, and
     * has a "redirection" support by checking the meta file corresponding to th e given path
     * @param string $path  the path of the file into the repository
     * @param string $commitId the bin hash of a commit corresponding to a branch tip, if we want to search in
     *                  an other branch than the default branch
     * @todo  support of redirection a a path component, to allow to indicate a redirection
     *     of a directory content to another directory
     */
    function findFile($path, $commitId = null) {

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

        // retrieve the object corresponding to the dir
        $commit = $this->repo->getObject($commitId);
        $this->loadRedirections($commit);

        foreach($this->redirections as $regexp=>$target) {
            if (preg_match('!'.$regexp.'!', $path, $m)) {
                if ($target == '')
                    return null;
                if (count($m) > 1) {
                    array_shift($m);
                    array_unshift($m, $target);
                    return new gtwRedirection(call_user_func_array('sprintf',$m));
                }
                else {
                    return new gtwRedirection($target);
                }
            }
        }

        // extract the dir path and the file name from the given path
        if (substr($path, -1,1) == '/') {
            $name = 'index';
            $implicitName  = true;
            //jLog::log("get $path : implicite index");
        }
        else {
            $name = basename($path);
            $path = dirname($path);
            if ($path == '.')
                $path = '';
            $implicitName = false;
            //jLog::log("get $path/$name : explicit page $name");
        }


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
            //jLog::log("get $path: treeobject is not a tree");
            // the path does not correspond to a directory, but to a file
            // in case when a leading / was provided, so we could ignore this / and
            // display the content of the file
            if ($implicitName) {
                $path = rtrim($path, '/');
                $name = basename($path);
                $path = dirname($path);
                if ($path == '.')
                    $path = '/';
                $hash = $commit->find($path);
                if (!$hash) {
                    return null;
                }
                $treeObject = $this->repo->getObject($hash);
                if (!$treeObject) {
                    return null;
                }
                return new gtwFile($this, $treeObject, $path, $name);
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
                //jLog::log("get $path/$name : it is a file, good");
                return new gtwFile($this, $treeObject, $path, $name);
            }

            // the given "path/name" is a directory

            if ($implicitName)
                // error we don't expect to find a directory 'index' under the given path
                throw new Exception ('Unexpected content at this path');

            //jLog::log("get $path/$name : it is a directory. Try multiview for $path/$name to get index (dokuwiki compatibility)");

            // so the path indicates a directory
            // is there a file with the same name + a known extension?
            // => compatibility with dokuwiki storage
            $fileResult = $this->checkMultiview($treeObject, $path, $name);
            if ($fileResult) {
                jLog::log("get $path/$name  : it is a directory. found $path/".$fileResult->getName());
                // ok, we found a file
                return $fileResult;
            }

            //jLog::log("get $path/$name : it is a directory. Try multiview for $path/$name/index");

            // this sub directory becomes the base directory
            $treeObject = $this->repo->getObject($node->object);
            $name = 'index';
            $node = null;
            $implicitName = true;
        }
        else if ($implicitName) {
            //jLog::log("get $path/$name : not found. Try multiview.");
            $fileResult = $this->checkMultiview($treeObject, $path, $name);
            if ($fileResult)
                return $fileResult;
            //jLog::log("get $path/$name with multiview not found. Try multiview on $path");
            $path = rtrim($path, '/');
            $name = basename($path);
            $path = dirname($path);
            if ($path == '.')
                $path = '/';
            $hash = $commit->find($path);
            if (!$hash) {
                return null;
            }
            $treeObject = $this->repo->getObject($hash);
            if (!$treeObject) {
                return null;
            }
        }
        else {
            //jLog::log("get $path/$name : not found. Try multiview");
        }

        // multiview : try to find a file with the given name + a known extension
        $fileResult = $this->checkMultiview($treeObject, $path, $name);
        if ($fileResult || ! $implicitName)
            return $fileResult;
        //jLog::log("get $path : directory view");
        return new gtwDirectory($this, $treeObject, $path);
    }
    
    protected function checkMultiview($treeObject, $path, $name) {
        $metaDirObject = $this->getMetaDirObject($treeObject);
        $file = new gtwFile($this, $treeObject, $path, $name);
        $file->setMetaDirObject($metaDirObject);

        $redir = $file->getMeta('redirection');

        if ($redir) {
            return new gtwRedirection($redir);
        }

        $extList = array('.html', '.htm', '.wiki', '.md', '.txt');
        foreach($extList as $ext) {
            $n = $name.$ext;
            $file = new gtwFile($this, $treeObject, $path, $n);
            if ($file->exists()) {
                return $file;
            }
            $file->setMetaDirObject($metaDirObject);
            $redir = $file->getMeta('redirection');
            if ($redir) {
                return new gtwRedirection($redir);
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

    protected $redirections = array();
    protected function loadRedirections($commit) {
        $this->redirections = array();
        $hash = $commit->find('.redirections');
        if (!$hash)
            return;
        $object = $this->repo->getObject($hash);
        if (!$object || !($object instanceof GitBlob)) {
            return;
        }
        $lines = explode("\n", $object->data);
        foreach($lines as $line) {
            if (trim($line) == '')
                continue;
            list($old,$new) = explode("=>", $line);
            $old = trim($old);
            if ($old)
                $this->redirections[trim($old)] = trim($new);
        }
    }
}