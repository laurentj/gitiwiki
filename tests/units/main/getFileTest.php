<?php

//require_once(dirname(__FILE__).'/../classes/gtw\Repository.class.php');

use \Gitiwiki\Storage as gtw;

class getFileTest extends \PHPUnit\Framework\TestCase {

    protected $repoName = 'default';


    public function testGetImplicitHome() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('
== first page ==

Hello world !


', $page->getContent());
    }

    public function testGetHome() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/index.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('
== first page ==

Hello world !


', $page->getContent());
    }

    public function testGetMultiviewHome() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/index');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('
== first page ==

Hello world !


', $page->getContent());
    }

    public function testGetArticle() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/article.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('This is an article.
', $page->getContent());
    }

    public function testGetMultiviewArticle() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/article');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('This is an article.
', $page->getContent());
    }

    public function testGetUnknowFile() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/foo.txt');
        $this->assertNull($page);
    }

    public function testGetImplicitDirIndex() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual/');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('This is the index page of manual
', $page->getContent());
        $this->assertEquals('manual/index.wiki', $page->getPathFileName());
        $this->assertEquals('index.wiki', $page->getName());
        $this->assertEquals('manual', $page->getPath());
    }

    public function testGetImplicitDirIndexNoTrailingSlash() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('This is the index page of manual
', $page->getContent());
        $this->assertEquals('manual/index.wiki', $page->getPathFileName());
        $this->assertEquals('index.wiki', $page->getName());
        $this->assertEquals('manual', $page->getPath());
    }

    public function testGetDirIndex() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual/index.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('This is the index page of manual
', $page->getContent());
    }

    public function testGetMultiviewDirIndex() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual/index');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('This is the index page of manual
', $page->getContent());
    }

    public function testGetImplicitDirDkIndex() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual2/');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('manual2.wiki', $page->getName());
        $this->assertEquals('This is the index page of manual2
', $page->getContent());
    }

    public function testGetImplicitDirDkIndex2() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual2');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('manual2.wiki', $page->getName());
        $this->assertEquals('This is the index page of manual2
', $page->getContent());
    }

    public function testGetDirArticle() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual2/article2.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('this is an article2 of manual2
', $page->getContent());
    }

    public function testGetDirWithoutIndex() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual_no_index/');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Directory", $page);
        $this->assertEquals('manual_no_index', $page->getPath());
        $this->assertEquals(array("article"), $page->getContent());
    }

    public function testMetaRedirectionAtRoot() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/truc.html');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertTrue($page->isWikiUrl());
        $this->assertEquals('article.wiki', $page->url);
    }

    public function testMetaRedirectionInDirectory() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual/movedpage.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertTrue($page->isWikiUrl());
        $this->assertEquals('manual2/article2', $page->url);

        $page = $repo->findFile('/manual/relative-renamed-page.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertTrue($page->isWikiUrl());
        $this->assertEquals('manual/index.wiki', $page->url);
    }

    public function testMetaRedirectionOutsideWiki() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual/movedpage-outside.wiki');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertFalse($page->isWikiUrl());
        $this->assertEquals('/foo.html', $page->url);
    }

    
    public function testGlobalRedirection() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual2.old/foo.txt');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertTrue($page->isWikiUrl());
        $this->assertEquals('manual2/foo.txt', $page->url);

        $page = $repo->findFile('/manual2.old/bla/foo.txt');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertTrue($page->isWikiUrl());
        $this->assertEquals('manual2/bla/foo.txt', $page->url);
    }

    public function testGlobalRedirection2() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual2/unexistant');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertTrue($page->isWikiUrl());
        $this->assertEquals('manual2/article2', $page->url);
    }

    public function testGlobalRedirectionOutsideWiki() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/manual/moved-page-outside.txt');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertFalse($page->isWikiUrl());
        $this->assertEquals('/new-page.txt', $page->url);
    }

    public function testGlobalRedirectionOutsideSite() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('/something/elsewhere.txt');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\Redirection", $page);
        $this->assertFalse($page->isWikiUrl());
        $this->assertEquals('http://jelix.org/new-page.txt', $page->url);
    }

    public function testGetTestAlias() {
        $repo = new gtw\Repository($this->repoName);
        $page = $repo->findFile('testalias');
        $this->assertNotNull($page);
        $this->assertInstanceOf("Gitiwiki\\Storage\\File", $page);
        $this->assertEquals('
== a page ==

Hello [[apiref:myclass]] world !


', $page->getContent());
        $content= $page->getHtmlContent('/');
        $this->assertEquals('
<h5 id="a-page">a page<a class="anchor" href="#a-page" title="Link to this section"></a></h5><div class="level5">

<p>Hello <a href="http://example.com/api/myclass.html">myclass</a> world !</p>


</div>',str_replace(' ¶', '',$content)); // phpunit doesn't like this character !
    }
}
