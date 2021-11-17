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

class SphinxSearchClear extends \Jelix\Scripts\ModuleCommandAbstract
{
    protected function configure()
    {
        $this
            ->setName('gtwsphinx:search:clear')
            ->setDescription('')
            ->setHelp('')
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sphinxSrv = \jClasses::getService('sphinxsearch~sphinx');
        $sphinxSrv->resetSources();
    }
}
