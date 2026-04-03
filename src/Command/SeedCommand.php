<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed',
    description: 'Seed the database with sample data',
)]
class SeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = [
            ['name' => 'Алексей Иванов', 'email' => 'alexey@example.com'],
            ['name' => 'Мария Петрова', 'email' => 'maria@example.com'],
            ['name' => 'Дмитрий Сидоров', 'email' => 'dmitry@example.com'],
            ['name' => 'Елена Козлова', 'email' => 'elena@example.com'],
            ['name' => 'Сергей Морозов', 'email' => 'sergey@example.com'],
        ];

        $count = 0;
        foreach ($users as $data) {
            $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existing) {
                continue;
            }

            $user = new User();
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $this->em->persist($user);
            $count++;
        }

        $this->em->flush();
        $io->success(sprintf('Seeded %d users.', $count));

        return Command::SUCCESS;
    }
}
