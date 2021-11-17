<?php
/**
 * @package   sphinxsearch
 * @subpackage sphinxsearch
 * @author    Brice Tencé
 * @contributor Laurent Jouanneau
 * @copyright 2012 Brice Tencé, 2021 Laurent Jouanneau
 * @link      http://jelix.org
 * @license    GNU PUBLIC LICENCE
 */

namespace GtwSphinx\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SphinxSearchExport extends \Jelix\Scripts\ModuleCommandAbstract
{
    protected function configure()
    {
        $this
            ->setName('gtwsphinx:search:export')
            ->setDescription('')
            ->setHelp('')
            ->addArgument(
                'repository',
                InputArgument::REQUIRED,
                'the repository name'
            )
            ->addArgument(
                'bookindex',
                InputArgument::REQUIRED,
                'the book id'
            )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repoName = $input->getArgument('repository');
        $bookIndex = $input->getArgument('bookindex');

        $sphinxSrv = \jClasses::getService('sphinxsearch~sphinx');
        $output->writeln(
            $sphinxSrv->xmlpipe2source( array(
                'type' => 'gitiwiki',
                'repo' => $repoName,
                'bookindex' => $bookIndex ) )
        );
    }
}
