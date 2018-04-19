<?php

//require_once(dirname(__FILE__).'/../classes/gtw\Repository.class.php');

use \Gitiwiki\Storage as gtw;

class getFileRepoAliasTest extends getFileTest {

    protected $repoName = 'current-manual';

    public function testGetNameForUrl() {
        $repo = new gtw\Repository('current-manual');
        $this->assertEquals('mymanual', $repo->getName());
        $this->assertEquals('current-manual', $repo->getNameForUrl());

        $repo = new gtw\Repository('mymanual');
        $this->assertEquals('mymanual', $repo->getName());
        $this->assertEquals('current-manual', $repo->getNameForUrl());
    }
}
