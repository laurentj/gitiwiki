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

    protected $repoName;

    /**
     * @param string $repoName the name of the repository as registered in the configuration
     */
    function __construct($repoName) {
        $conf = jApp::config();

        $this->config = jProfiles::get('gtwrepo', $repoName, true);

        if (isset($this->config['generators']) && isset($conf->{$this->config['generators']})) {
            $this->config['generators'] = $conf->{$this->config['generators']};
        }
        else if (isset($conf->gitiwikiGenerators)) {
            $this->config['generators'] = $conf->gitiwikiGenerators;
        }
        else {
            $this->config['generators'] = array();
        }
        $this->config['branches'] = array();

        if (!isset($this->config['title']))
            $this->config['title'] = $repoName;

        $this->config['path'] = str_replace(array('app:'), array(jApp::appPath()), $this->config['path']);
        $this->repo = new Git($this->config['path']);
        $this->repoName = $repoName;
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

    function getBranchConfig($commitId='') {
        if (!$commitId) {
            $commitId = $this->repo->getTip($this->config['branch']);
        }
        $commit = $this->repo->getObject($commitId);
        return $this->loadBranchConfig($commit);
    }

    function getName() {
        return $this->repoName;
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
        //jLog::log("------------------findFile $path");

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
        $config = $this->loadBranchConfig($commit);

        $path = ltrim($path, '/');

        foreach($config['redirection'] as $regexp=>$target) {
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
        if ($path == '') {
            $name = 'index';
            $implicitName  = true;
            //jLog::log("get $path : implicit index home");
        }
        else if (substr($path, -1,1) == '/') {
            $path = rtrim($path, '/');
            $name = 'index';
            $implicitName  = true;
            //jLog::log("get $path : implicit index");
        }
        else {
            $name = basename($path);
            $path = dirname($path);
            if ($path == '.')
                $path = '';
            $implicitName = false;
            //jLog::log("get $path/$name : explicit page $name");
        }

        $hash = $commit->find($path.'/');
        if (!$hash) {
            //jLog::log("get $path : don't find the path at the given commit");
            return null;
        }

        $originalPath = $path;
        $originalName = $name;
        $originalTreeObject = $treeObject = $this->repo->getObject($hash);
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
                $name = basename($path);
                $path = dirname($path);
                if ($path == '.')
                    $path = '';
                $hash = $commit->find($path.'/');
                if (!$hash) {
                    return null;
                }
                $treeObject = $this->repo->getObject($hash);
                if (!$treeObject) {
                    return null;
                }
                return new gtwFile($this, $commitId, $treeObject, $path, $name);
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
            
            if( $node->is_dir && $implicitName ) {
                // error we don't expect to find a directory 'index' under the given path
                throw new Exception ('Unexpected content at this path');
            }

            //jLog::log("get $path/$name : it is a file or a directory. Try multiview for $path/$name to get index (dokuwiki compatibility)");

            // so the path indicates a file or a directory
            // is there a file with the same name + a known extension?
            // => compatibility with dokuwiki storage
            $fileResult = $this->checkMultiview($treeObject, $path, $name, $commitId);
            if ($fileResult) {
                //jLog::log("get $path/$name  : it is a directory. found $path/".$fileResult->getName());
                // ok, we found a file
                return $fileResult;
            }

            //jLog::log("get $path/$name : it is a directory. Try multiview for $path/$name/index");

            // this sub directory becomes the base directory
            // XXX: isn't better to do a redirection to the path with a trailing slash ?
            $treeObject = $this->repo->getObject($node->object);
            $path = ltrim($path.'/'.$name, '/');
            $name = 'index';
            $node = null;
            $implicitName = true;
        }
        else if ($implicitName) {
            //jLog::log("get $path/$name : not found. Try multiview.");
            $fileResult = $this->checkMultiview($treeObject, $path, $name, $commitId);
            if ($fileResult) {
                if( $fileResult instanceof gtwFile ) {
                    return new gtwRedirection( $fileResult->getPath() . '/' . $fileResult->getName() );
                } else {
                    return $fileResult;
                }
            }
            //jLog::log("get $path/$name with multiview not found. Try multiview on $path");
            $name = basename($path);
            $path = dirname($path);
            if ($path == '.')
                $path = '';
            $hash = $commit->find($path.'/');
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
        $fileResult = $this->checkMultiview($treeObject, $path, $name, $commitId);
        if ($fileResult || ! $implicitName)
            return $fileResult;
        //jLog::log("get $originalPath: directory view");
        return new gtwDirectory($this, $commitId, $originalTreeObject, $originalPath);
    }

    protected function checkMultiview($treeObject, $path, $name, $commitId, $checkDupContent=true) {
        $metaDirObject = $this->getMetaDirObject($treeObject);
        $file = new gtwFile($this, $commitId, $treeObject, $path, $name);
        $file->setMetaDirObject($metaDirObject);

        $extList = $this->config['branches'][$commitId]['multiviews'];

        $redir = $file->getMeta('redirection');
        if ($redir) {
            return new gtwRedirection($redir, $path);
        } elseif ( isset($treeObject->nodes[$name]) && ! $treeObject->nodes[$name]->is_dir ) {
            if( $checkDupContent ) {
                //it's a file. But to avoid duplicate contents on this URL, look if there is an url that points to this file using multiview
                foreach($extList as $ext) {
                    if( $ext === substr( $name, -strlen($ext) ) ) {
                        //extension matches
                        $nameNoExt = substr( $name, 0, -strlen($ext) );
                        $fileResult = $this->checkMultiview($treeObject, $path, $nameNoExt, $commitId, false);
                        if( $fileResult instanceof gtwFile && $fileResult->getPath() == $path && $fileResult->getName() == $name ) {
                            return new gtwRedirection( $path . '/' . $nameNoExt );
                        }
                    }
                }
            }
            //no multiview found -> this URL is OK !
            return $file;
        }

        foreach($extList as $ext) {
            $n = $name.$ext;
            $file = new gtwFile($this, $commitId, $treeObject, $path, $n);
            if ($file->exists()) {
                return $file;
            }
            $file->setMetaDirObject($metaDirObject);
            $redir = $file->getMeta('redirection');
            if ($redir) {
                return new gtwRedirection($redir, $path);
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

    protected function loadBranchConfig($commit) {
        $hash = $commit->getName();
        if (isset($this->config['branches'][$hash])) {
            return $this->config['branches'][$hash];
        }
        $c = $this->config['branches'][$hash] = array('multiviews'=>array('.gtw'), 'redirection'=>array(), 'ignore'=>array(), 'protocol-aliases'=>array());

        $cfhash = $commit->find('.config.ini');
        if (!$cfhash){
            return $c;
        }
        $object = $this->repo->getObject($cfhash);
        if (!$object || !($object instanceof GitBlob)) {
            return $c;
        }

        $c = parse_ini_string($object->data, true);
        if ($c) {

           if (isset($c['multiviews'])) {
                $c['multiviews'] = preg_split("/\s*,\s*/", $c['multiviews']);
            }
            else
                $c['multiviews'] = array();

            if (isset($c['redirection']) && is_array($c['redirection'])) {
                $r = array();
                foreach( $c['redirection'] as $k=>$line) {
                    if (trim($line) == '')
                        continue;
                    list($old,$new) = explode("->", $line);
                    $old = trim($old);
                    if ($old)
                        $r[trim($old)] = trim($new);
                }
                $c['redirection'] = $r;
            }
            else
                $c['redirection'] = array();

            if (!isset($c['ignore']))
                $c['ignore'] = array();

            if (!isset($c['protocol-aliases']))
                $c['protocol-aliases'] = array();

            $this->config['branches'][$hash] = $c;
        }
        return $this->config['branches'][$hash];
    }
}
