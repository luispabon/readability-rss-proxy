<?php

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
    /**
     * @var RssUserRepository
     */
    private $userRepository;
    /**
     * @var PasswordStrengthValidator
     */
    private $passwdValidator;

    public function __construct(RssUserRepository $userRepository, PasswordStrengthValidator $passwdValidator)
    {
        $this->userRepository  = $userRepository;
        $this->passwdValidator = $passwdValidator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates a RSS user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Create a RSS user');

        $email = $io->ask('Please enter the user\'s email', null, function ($answer) {
            if (filter_var($answer, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException('Not a valid email address');
            }

            return $answer;
        });

        $password = $io->ask('Please enter the user\'s password', null, function ($answer) {
            if ($this->passwdValidator->validate($answer) === false) {
                throw new RuntimeException('Weak password, please re-enter');
            }

            return $answer;
        });

        $makeAdmin = false;
        if ($io->ask('Make this user admin? (y/n)', 'n', function ($answer) {
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
