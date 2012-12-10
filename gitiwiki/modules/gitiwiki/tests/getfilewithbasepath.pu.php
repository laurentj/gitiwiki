<?php

require_once(dirname(__FILE__).'/../classes/gtwRepo.class.php');

class getFileWithBasePathTest extends PHPUnit_Framework_TestCase {


    public function testGetUnknowFile() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/foo.txt');
        $this->assertNull($page);
    }

    public function testGetImplicitHome() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('This is the index page of rootmanual
', $page->getContent());
        $this->assertEquals('/index.wiki', $page->getPathFileName());
        $this->assertEquals('rootmanual/index.wiki', $page->getRealPathFileName());
        $this->assertEquals('index.wiki', $page->getName());
        $this->assertEquals('', $page->getPath());
    }

    public function testGetHome() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/index.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('This is the index page of rootmanual
', $page->getContent());
    }

    public function testGetMultiviewHome() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/index');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('This is the index page of rootmanual
', $page->getContent());
    }

    public function testMetaRedirectionInDirectory() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/movedpage.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwRedirection', $page);
        $this->assertTrue($page->isWikiUrl());
        $this->assertEquals('article2', $page->url);

        $page = $repo->findFile('/relative-renamed-page.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwRedirection', $page);
        $this->assertTrue($page->isWikiUrl());
        $this->assertEquals('index.wiki', $page->url);
    }

    public function testMetaRedirectionOutsideWiki() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/movedpage-outside.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwRedirection', $page);
        $this->assertFalse($page->isWikiUrl());
        $this->assertEquals('/foo.html', $page->url);
    }


    public function testGetArticleOutsideBasePath() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/manual2.wiki');
        $this->assertNull($page);
    }

    public function testGlobalRedirectionOutsideWiki() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/moved-page-outside.txt');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwRedirection', $page);
        $this->assertFalse($page->isWikiUrl());
        $this->assertEquals('/new-page.txt', $page->url);
    }

    public function testGlobalRedirectionOutsideSite() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/rootsomething/elsewhere.txt');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwRedirection', $page);
        $this->assertFalse($page->isWikiUrl());
        $this->assertEquals('http://jelix.org/new-page.txt', $page->url);
    }

    public function testGlobalRedirectionOutsideSite2() {
        $repo = new gtwRepo('defaultwithbasepath');
        $page = $repo->findFile('/something/elsewhere.txt'); // it is defined in a .config file outside rootmanual
        $this->assertNull($page);
    }
}
