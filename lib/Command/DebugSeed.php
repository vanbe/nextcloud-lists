<?php

declare(strict_types=1);

namespace OCA\Lists\Command;

use OCA\Lists\Db\ListEntity;
use OCA\Lists\Db\ListMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DebugSeed extends Command {
    public function __construct(private readonly ListMapper $mapper) {
        parent::__construct();
    }

    protected function configure(): void {
        $this->setName('lists:debug:seed')
            ->setDescription('Seeds the database with sample lists (dev only)')
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Owner user ID', 'admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $uid = (string) $input->getOption('user');

        $samples = [
            ['name' => 'Courses semaine', 'icon' => 'cart', 'description' => 'Liste de courses hebdomadaire'],
            ['name' => 'TODO projet', 'icon' => 'check-circle', 'description' => null],
        ];

        foreach ($samples as $data) {
            $entity = new ListEntity();
            $entity->setUid($uid);
            $entity->setName($data['name']);
            $entity->setIcon($data['icon']);
            $entity->setDescription($data['description']);

            $this->mapper->insert($entity);
            $output->writeln(sprintf('  Created list #%d "%s"', $entity->getId(), $entity->getName()));
        }

        $output->writeln('<info>Seeding complete.</info>');
        return Command::SUCCESS;
    }
}
