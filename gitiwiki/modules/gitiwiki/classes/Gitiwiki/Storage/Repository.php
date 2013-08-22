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

class Repository {
    /**
     * @var array
     * configuration parameters of the repository
     */
    protected $config;

    /**
     * @var Glip\Git
     * the git object representing the repository
     */
    protected $repo;

    protected $repoName;

    static protected $DEFAULT_CONFIG = array('multiviews'=>array('.gtw'),
                                'redirection'=>array(),
                                'ignore'=>array(),
                                'protocol-aliases'=>array());
    
    /**
     * @param string $repoName the name of the repository as registered in the configuration
     */
    function __construct($repoName) {

        $conf = \jApp::config();

        $this->config = \jProfiles::get('gtwrepo', $repoName, true);

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

        if (!isset($this->config['basepath']) || $this->config['basepath'] == '/') {
            $this->config['basepath'] = '';
        }
        else {
            $this->config['basepath'] = trim($this->config['basepath'],'/').'/';
        }

        $this->config['path'] = str_replace(array('app:'), array(\jApp::appPath()), $this->config['path']);
        $this->repo = new \Glip\Git($this->config['path']);
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

    function getBranchConfig($branchName = null) {
        if (!$branchName) {
            $branchName = $this->config['branch'];
        }
        $branch = $this->repo[$branchName];
        if (!$branch)
            return self::$DEFAULT_CONFIG;
        return $this->loadBranchConfig($branch->getTip());
    }

    function getName() {
        return $this->repoName;
    }

    /**
     * searches a file at the given path. It has a "multiview" support, and
     * has a "redirection" support by checking the meta file corresponding to the given path
     * @param string $path  the path of the file into the repository. It should start with a "/".
     *                      this path will be prefixed by the basepath config parameter of the repository
     *                      if needed.
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
            $commit = $this->repo[$this->config['branch']]->getTip();
            $commitHash = $commit->getSha();
            $commitId = $commitHash->hex();
        }
        else {
            $commit = $this->repo[$commitId];
            if (! ($commit instanceof \Glip\GitCommit))
                throw new \Exception("Bad Commit Id");
            $commitHash = new \Glip\SHA($commitId);
        }

        // retrieve the object corresponding to the dir
        $config = $this->loadBranchConfig($commit);

        $path = ltrim($path, '/');

        foreach($config['redirection'] as $regexp=>$target) {
            if (preg_match('!'.$regexp.'!', $path, $m)) {
                if ($target == '')
                    return null;
                if (count($m) > 1) {
                    array_shift($m);
                    array_unshift($m, $target);
                    return new Redirection(call_user_func_array('sprintf',$m));
                }
                else {
                    return new Redirection($target);
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

        // we have here
        //  $path = the path of the directory where the file is
        //  $name = the name of the file to retrieve
        //  $implicitName = true if the name was given, false if gtw guessed it

        // let's retrieve the git object corresponding to the path.
        $originalPath = $path;
        $originalName = $name;
        $originalTreeObject = $treeObject = $commit[$this->config['basepath'].$path.'/'];
        if (!$treeObject) {
            //jLog::log("get $path : don't find the tree object");
            return null;
        }

        if (! ($treeObject instanceof \Glip\GitTree)) {
            //jLog::log("get $path: treeobject is not a tree");
            // the path does not correspond to a directory, but to a file
            // in case when a leading / was provided, so we could ignore this / and
            // display the content of the file
            if ($implicitName) {
                $name = basename($path);
                $path = dirname($path);
                if ($path == '.')
                    $path = '';

                $treeObject = $commit[$this->config['basepath'].$path.'/'];
                if (!$treeObject) {
                    return null;
                }
                return new File($this, $commitHash, $treeObject, $path, $name);
            }
            else {
                // the directory of the file is not a directory. Error
                throw new \Exception ('Unexpected content at this path');
            }
        }

        // ok, the dir path is really a directory
        $node = null;

        // does the given filename correspond to a file/dir ?
        if (isset($treeObject[$name])) {
            $node = $treeObject[$name];
            if (! ($node instanceof \Glip\GitTree)) {
                // this is a file, good !
                //jLog::log("get $path/$name : it is a file, good");
                return new File($this, $commitHash, $treeObject, $path, $name);
            }

            // the given "path/name" is a directory

            if ($implicitName)
                // error we don't expect to find a directory 'index' under the given path
                throw new \Exception ('Unexpected content at this path');

            //jLog::log("get $path/$name : it is a directory. Try multiview for $path/$name to get index (dokuwiki compatibility)");

            // so the path indicates a directory
            // is there a file with the same name + a known extension?
            // => compatibility with dokuwiki storage
            $fileResult = $this->checkMultiview($treeObject, $path, $name, $commitHash);
            if ($fileResult) {
                //jLog::log("get $path/$name  : it is a directory. found $path/".$fileResult->getName());
                // ok, we found a file
                return $fileResult;
            }

            //jLog::log("get $path/$name : it is a directory. Try multiview for $path/$name/index");

            // this sub directory becomes the base directory
            // XXX: isn't better to do a redirection to the path with a trailing slash ?
            $treeObject = $node;
            $path = ltrim($path.'/'.$name, '/');
            $name = 'index';
            $node = null;
            $implicitName = true;
        }
        else if ($implicitName) {
            //jLog::log("get $path/$name : not found. Try multiview.");
            $fileResult = $this->checkMultiview($treeObject, $path, $name, $commitHash);
            if ($fileResult)
                return $fileResult;
            //jLog::log("get $path/$name with multiview not found. Try multiview on $path");
            $name = basename($path);
            $path = dirname($path);
            if ($path == '.')
                $path = '';

            $treeObject = $commit[$this->config['basepath'].$path.'/'];
            if (!$treeObject) {
                return null;
            }
        }
        else {
            //jLog::log("get $path/$name : not found. Try multiview");
        }

        // multiview : try to find a file with the given name + a known extension
        $fileResult = $this->checkMultiview($treeObject, $path, $name, $commitHash);
        if ($fileResult || ! $implicitName)
            return $fileResult;
        //jLog::log("get $originalPath: directory view");
        return new Directory($this, $commitHash, $originalTreeObject, $originalPath);
    }

    protected function checkMultiview($treeObject, $path, $name, $commitHash) {
        $metaDirObject = $this->getMetaDirObject($treeObject);
        $file = new File($this, $commitHash, $treeObject, $path, $name);
        if ($metaDirObject)
            $file->setMetaDirObject($metaDirObject);

        $redir = $file->getMeta('redirection');
        if ($redir) {
            return new Redirection($redir, $path);
        }

        $extList = $this->config['branches'][$commitHash->hex()]['multiviews'];
        foreach($extList as $ext) {
            $n = $name.$ext;
            $file = new File($this, $commitHash, $treeObject, $path, $n);
            if ($file->exists()) {
                return $file;
            }
            if ($metaDirObject)
                $file->setMetaDirObject($metaDirObject);
            $redir = $file->getMeta('redirection');
            if ($redir) {
                return new Redirection($redir, $path);
            }
        }
        return null;
    }

    protected function getMetaDirObject($treeObject) {
        if (!isset($treeObject['.meta'])) {
            return null;
        }
        $metaDirObject = $treeObject['.meta'];
        if (!$metaDirObject || !($metaDirObject instanceof \Glip\GitTree)) {
            return null;
        }
        return $metaDirObject;
    }

    /**
     * return configuration content for a specific branch
     *
     * @param \Glip\GitCommit $commit
     * @return array
     */
    protected function loadBranchConfig(\Glip\GitCommit $commit) {
        $hash = $commit->getSha()->hex();
        if (isset($this->config['branches'][$hash])) {
            return $this->config['branches'][$hash];
        }
        $c = $this->config['branches'][$hash] = self::$DEFAULT_CONFIG;

        $object = $commit[$this->config['basepath'].'.config.ini'];
        if (!$object || !($object instanceof \Glip\GitBlob)) {
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