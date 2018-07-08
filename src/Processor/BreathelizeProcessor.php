<?php
declare(strict_types=1);

namespace App\Processor;

use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;
use Symfony\Component\Finder\Finder;

class BreathelizeProcessor
{
    private const VOCABULARY_DELIMITER = "\n";
    private const INPUT_FILE_DELIMITER = " ";
    private const DEFAULT_BATCH_SIZE = 30;

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var string
     */
    private $vocabulary;

    /**
     * @var string
     */
    private $directory;


    public function __construct(Finder $finder, string $directory)
    {
        $this->finder = $finder;
        $this->directory = $directory;
    }

    public function setVocabulary(string $filename, string $delimiter = self::VOCABULARY_DELIMITER)
    {
        $vocabulary = $this->finder->files()->name($filename)->in($this->directory);
        if (!$vocabulary->hasResults()) {
            throw new \InvalidArgumentException('Vocabulary not found');
        }
        $contents = current(\iterator_to_array($vocabulary))->getContents();
        $this->vocabulary = explode($delimiter, mb_strtolower(trim($contents)));
    }

    public function getMinimumChangesCount(string $inputFilename, string $delimiter = self::INPUT_FILE_DELIMITER): int
    {
        if (null === $this->vocabulary) {
            throw new \InvalidArgumentException('Vocabulary is not set');
        }

        $files = Finder::create()->files()->name($inputFilename)->in($this->directory);
        if (!$files->hasResults()) {
            throw new \InvalidArgumentException('Input file not found');
        }

        $content = current(\iterator_to_array($files))->getContents();
        $tokens = explode($delimiter, $content);

        $batches = array_chunk($tokens, self::DEFAULT_BATCH_SIZE);
        $values = wait(parallelMap($batches, function ($batch) {
            $batchChanges = [];
            foreach ($batch as $token) {
                $batchChanges[] = $this->getTokenMinChangesCount($token);
            }
            return array_sum($batchChanges);
        }));


        return array_sum($values);
    }

    private function getTokenMinChangesCount(string $token): int
    {
        $shortest = -1;
        foreach ($this->vocabulary as $word) {
            $lev = levenshtein($token, $word);
            if ($lev === 0) {
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $shortest = $lev;
            }
        }

        return $shortest;
    }
}