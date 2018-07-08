<?php
declare(strict_types=1);


namespace App\Command;

use App\Processor\BreathelizeProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class BreathalizeCommand extends Command
{
    private const INPUT_DIR = 'var/files';
    private const VOCABULARY_FILENAME = 'vocabulary.txt';

    /**
     * Configures the argument and options
     */
    protected function configure()
    {
        $this
            ->setName('breathalizer:analyze')
            ->setDescription('Count minimum number of changes in file according to vocabulary')
            ->addArgument('file', InputArgument::REQUIRED, 'Set file to analyze')
            ->addArgument('vocabulary', InputArgument::OPTIONAL, 'Set custom vocabulary file')
        ;
    }

    /**
     * Executes the logic and creates the output.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time_start = microtime(true);
        $filename = $input->getArgument('file');
        $vocabulary = $input->getArgument('vocabulary') ?? self::VOCABULARY_FILENAME;

        $finder = new Finder();
        $processor = new BreathelizeProcessor($finder, self::INPUT_DIR, $vocabulary);
        $processor->setVocabulary($vocabulary);

        try {
            $count = $processor->getMinimumChangesCount($filename);
            $msg = '<fg=green>'. $count .'</fg=green>';
        } catch (\Throwable $e) {
            $msg = '<fg=yellow>'. $e->getMessage() .'</fg=yellow>';
        }

        $output->writeln('Execution time: '.(microtime(true) - $time_start).' sec');
        $output->writeln('Memory: ' . round (memory_get_peak_usage() / 1024 / 1024) . ' MB');
        $output->writeln('Min changes: ' .$msg);
    }
}