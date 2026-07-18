<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Console;

use App\Identity\Application\CreateUser;
use App\Identity\Application\Exception\InvalidPassword;
use App\Identity\Domain\Exception\InvalidLogin;
use App\Identity\Domain\Exception\UserAlreadyExists;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:create',
    description: 'Tworzy użytkownika.',
)]
final class CreateUserCommand extends Command
{
    public function __construct(private readonly CreateUser $createUser)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('login', InputArgument::REQUIRED, 'Login nowego użytkownika');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $login = (string) $input->getArgument('login');
        $password = $io->askHidden(sprintf(
            'Hasło (minimum %d znaków)',
            CreateUser::PASSWORD_MIN_LENGTH,
        ));
        $confirmation = $io->askHidden('Powtórz hasło');

        if (!is_string($password) || !is_string($confirmation) || !hash_equals($password, $confirmation)) {
            $io->error('Podane hasła nie są identyczne.');

            return Command::FAILURE;
        }

        try {
            $user = $this->createUser->execute($login, $password);
        } catch (InvalidLogin|InvalidPassword|UserAlreadyExists $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Utworzono użytkownika "%s".', $user->login()));

        return Command::SUCCESS;
    }
}
