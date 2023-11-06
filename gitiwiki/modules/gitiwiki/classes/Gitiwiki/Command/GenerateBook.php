<?php
/**
 * @package   gitiwiki
 * @subpackage gitiwiki
 * @author    Laurent Jouanneau
 * @copyright 2012-2013 laurent Jouannea21
 * @link      http://jelix.org
 * @license    GNU PUBLIC LICENCE
 */


namespace Gitiwiki\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBook extends \Jelix\Scripts\ModuleCommandAbstract
{
    protected function configure()
    {
        $this
            ->setName('gitiwiki:book')
            ->setDescription('Generate a book')
            ->setHelp('')
            ->addArgument(
                'repository',
                InputArgument::REQUIRED,
                'the repository name'
            )
            ->addArgument(
                'firstpage',
                InputArgument::REQUIRED,
                'the first page of the book (book index)'
            )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repoName = $input->getArgument('repository');
        $bookIndex = $input->getArgument('firstpage');
        $repo = new \Gitiwiki\Storage\Repository($repoName);
        $page = $repo->findFile($bookIndex);
        if ($page === null) {
            throw new \Exception('Book index is not found');
        }
        elseif($page instanceof \Gitiwiki\Storage\File) {
            if ($page->isStaticContent()) {
                throw new \Exception('The given path is not a book index');
            }

            $basePath = \jUrl::get('gitiwiki~wiki:page@classic', array('repository'=>$repoName, 'page'=>''));
            // FIXME: do rules for wikirenderer that just extract book info contents.
            $html = $page->getHtmlContent($basePath);

            $extraData = $page->getExtraData();

            // for book index
            if (isset($extraData['bookContent']) && isset($extraData['bookInfos'])) {
                $books = new \Gitiwiki\Storage\Books();
                $books->saveBook($page->getCommitId(), $repo->getName(), $page->getPathFileName(), $extraData, true);
            }
            else {
                throw new \Exception('The given path is not a book index');
            }
            $output->writeln("Book is generated");
        }
        else {
            throw new \Exception('The given path is not a page');
        }
        return 0;
    }
}
