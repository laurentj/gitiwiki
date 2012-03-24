<?php

require_once(dirname(__FILE__).'/../classes/gtwRepo.class.php');

class getFileTest extends PHPUnit_Framework_TestCase {

    public function testGetImplicitHome() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('
== first page ==

Hello world !


', $page->getContent());
    }

    public function testGetHome() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/index.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('
== first page ==

Hello world !


', $page->getContent());
    }

    public function testGetMultiviewHome() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/index');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('
== first page ==

Hello world !


', $page->getContent());
    }

    public function testGetArticle() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/article.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('This is an article.
', $page->getContent());
    }

    public function testGetMultiviewArticle() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/article');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('This is an article.
', $page->getContent());
    }

    public function testGetUnknowFile() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/foo.txt');
        $this->assertNull($page);
    }

    public function testGetImplicitDirIndex() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/manual/');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('This is the index page of manual
', $page->getContent());
    }

    public function testGetDirIndex() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/manual/index.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('This is the index page of manual
', $page->getContent());
    }

    public function testGetMultiviewDirIndex() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/manual/index');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('This is the index page of manual
', $page->getContent());
    }

    public function testGetImplicitDirDkIndex() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/manual2/');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('manual2.wiki', $page->getName());
        $this->assertEquals('This is the index page of manual2
', $page->getContent());
    }

    public function testGetImplicitDirDkIndex2() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/manual2');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('manual2.wiki', $page->getName());
        $this->assertEquals('This is the index page of manual2
', $page->getContent());
    }

    public function testGetDirArticle() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/manual2/article2.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwFile', $page);
        $this->assertEquals('this is an article2 of manual2
', $page->getContent());
    }

    public function testGetDirWithoutIndex() {
        $repo = new gtwRepo('default');
        $page = $repo->getFile('/manual_no_index/');
        $this->assertNotNull($page);
        $this->assertInstanceOf('gtwDirectory', $page);
    }
}