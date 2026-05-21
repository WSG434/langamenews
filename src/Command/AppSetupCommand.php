<?php

namespace App\Command;

use App\Repository\NewsSourceRepository;
use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:setup', description: 'Initial application setup: migrations, fixtures, env checks')]
class AppSetupCommand extends Command
{
    private const REQUIRED_ENV = [
        'APP_SECRET',
        'DATABASE_URL',
        'MESSENGER_TRANSPORT_DSN',
        'TELEGRAM_BOT_TOKEN',
        'TELEGRAM_CHAT_ID',
    ];

    private const OPTIONAL_ENV = [
        'NEWS_API_KEY'     => 'NewsAPI source will be skipped',
        'GUARDIAN_API_KEY' => 'Guardian source will be skipped',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NewsSourceRepository $newsSourceRepository,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('News Aggregator — Application Setup');
        $ok = true;

        // 1. Env vars
        $io->section('Environment variables');
        foreach (self::REQUIRED_ENV as $var) {
            $val = $_ENV[$var] ?? $_SERVER[$var] ?? getenv($var);
            if (!$val) {
                $io->error("Missing required env var: {$var}");
                $ok = false;
            } else {
                $io->writeln(" <info>✓</info> {$var}");
            }
        }
        foreach (self::OPTIONAL_ENV as $var => $hint) {
            $val = $_ENV[$var] ?? $_SERVER[$var] ?? getenv($var);
            if (!$val) {
                $io->warning("{$var} not set — {$hint}");
            } else {
                $io->writeln(" <info>✓</info> {$var}");
            }
        }

        if (!$ok) {
            $io->error('Fix missing env vars before continuing.');
            return Command::FAILURE;
        }

        // 2. var/ writable
        $io->section('File permissions');
        $varDir = $this->projectDir . '/var';
        if (!is_writable($varDir)) {
            $io->error("var/ directory is not writable: {$varDir}");
            return Command::FAILURE;
        }
        $io->writeln(' <info>✓</info> var/ is writable');

        // 3. Migrations
        $io->section('Database migrations');
        $exitCode = $this->getApplication()->find('doctrine:migrations:migrate')
            ->run(new ArrayInput(['--no-interaction' => true]), $output);
        if ($exitCode !== Command::SUCCESS) {
            $io->error('Migrations failed.');
            return Command::FAILURE;
        }

        // 4. News sources fixtures
        $io->section('News sources');
        if ($this->newsSourceRepository->count([]) === 0) {
            $io->writeln(' Loading fixtures…');
            $this->getApplication()->find('doctrine:fixtures:load')
                ->run(new ArrayInput(['--append' => true, '--no-interaction' => true]), $output);
            $io->writeln(' <info>✓</info> Sources loaded');
        } else {
            $io->writeln(' <info>✓</info> Sources already present (' . $this->newsSourceRepository->count([]) . ')');
        }

        // 5. SiteSettings init
        $io->section('Site settings');
        $this->siteSettingsRepository->getCurrent();
        $io->writeln(' <info>✓</info> SiteSettings initialized');

        // 6. Messenger transports
        $io->section('Messenger transports');
        $this->getApplication()->find('messenger:setup-transports')
            ->run(new ArrayInput(['--no-interaction' => true]), $output);

        $io->success('Setup complete. Next steps:');
        $io->listing([
            'Create an admin: php bin/console app:user:promote your@email.com',
            'Import news:     php bin/console app:news:import',
            'Start consumer:  systemctl start news-consumer',
        ]);

        return Command::SUCCESS;
    }
}
