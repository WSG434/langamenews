<?php

namespace App\Command;

use App\News\NewsImporter;
use App\Repository\NewsSourceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:news:import', description: 'Import news from enabled sources')]
class NewsImportCommand extends Command
{
    public function __construct(
        private readonly NewsImporter $importer,
        private readonly NewsSourceRepository $sourceRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('source', null, InputOption::VALUE_OPTIONAL, 'Source code to import from');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceCode = $input->getOption('source');

        if ($sourceCode !== null) {
            $sources = [];
            $source = $this->sourceRepository->findOneBy(['code' => $sourceCode, 'enabled' => true]);
            if ($source === null) {
                $output->writeln("<error>Source '{$sourceCode}' not found or disabled.</error>");
                return Command::FAILURE;
            }
            $sources[] = $source;
        } else {
            $sources = $this->sourceRepository->findAllEnabled();
        }

        $total = 0;
        foreach ($sources as $source) {
            $count = $this->importer->import($source);
            $output->writeln(sprintf('[%s] imported: %d', $source->getCode(), $count));
            $total += $count;
        }

        $output->writeln("Total new: {$total}");

        return Command::SUCCESS;
    }
}
