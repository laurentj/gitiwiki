<?php
/**
 * @author    Laurent Jouanneau
 * @copyright 2012-2021 laurent Jouanneau
 * @link      http://jelix.org
 * @license    GNU PUBLIC LICENCE
 */

namespace GtwDocbook\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDocbook extends \Jelix\Scripts\ModuleCommandAbstract
{
    protected function configure()
    {
        $this
            ->setName('gitiwiki:docbook')
            ->setDescription('Generate a book as docbook format')
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
            ->addOption(
                'lang',
                'l',
                InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'draft',
                'd',
                InputOption::VALUE_NONE
            )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repoName = $input->getArgument('repository');
        $bookIndex = $input->getArgument('bookindex');

        $lang = $input->getOption('lang');
        if ($lang) {
            \jApp::config()->locale = $lang;
        }

        $output->writeln("start docbook generation : ".$bookIndex."\n");

        \jClasses::inc('gtwDocbookGenerator');
        $gen = new \gtwDocbookGenerator($repoName, $bookIndex);
        $book = $gen->getBook();

        $date = new \jDateTime();
        $date->now();

        $tpl = new \jTpl();
        $tpl->assign('book', $book);
        $tpl->assign('pubdate', $date->toString(\jDateTime::LANG_DFORMAT));
        $tpl->assign('legalnotice', $gen->getLegalNotice());

        if($input->getOption('draft')) {
            $tpl->assign('edition', \jLocale::get('docbook.draft').' - '.date('d').' '.\jLocale::get('docbook.month_'.date('m')).' '.date('Y'));
            $tpl->assign('releaseInfo', \jLocale::get('docbook.release.info.draft'));
        }else{
            $tpl->assign('edition', $book['edition'].' - '.date('d').' '.\jLocale::get('docbook.month_'.date('m')).' '.date('Y'));
            $tpl->assign('releaseInfo', \jLocale::get('docbook.release.info.stable'));
        }

        $tpl->assign('content', $gen->generate());

        \jFile::write($gen->getBookPath().'docbook.xml', $tpl->fetch('docbook', 'xml'));

        $output->writeln("docbook built.\n");

    }
}
