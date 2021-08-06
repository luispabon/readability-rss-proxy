<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\RssUserRepository;
use App\Services\PasswordStrengthValidator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserCreateCommand extends Command
{
    protected static $defaultName = 'user:create';

    public function __construct(
        private RssUserRepository $userRepository,
        private PasswordStrengthValidator $passwdValidator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Creates a RSS user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): string|int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Create a RSS user');

        $email = $io->ask('Please enter the user\'s email', null, static function (string $answer) {
            if (filter_var($answer, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException('Not a valid email address');
            }

            return $answer;
        });

        $password = $io->ask('Please enter the user\'s password', null, function (string $answer) {
            if ($this->passwdValidator->validate($answer) === false) {
                throw new RuntimeException('Weak password, please re-enter');
            }

            return $answer;
        });

        $makeAdmin = false;
        if ($io->ask('Make this user admin? (y/n)', 'n', static function (string $answer) {
                if ($answer !== 'y' && $answer !== 'n') {
                    throw new RuntimeException('Answer `y` or `n`');
                }

                return $answer;
            }) === 'y') {
            $makeAdmin = true;
        }

        $this->userRepository->makeUser($email, $password, $makeAdmin);
        $io->success('User created correctly');

        return 0;
    }
}
