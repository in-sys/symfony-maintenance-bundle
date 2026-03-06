<?php

namespace INSYS\Bundle\MaintenanceBundle\Command;

use INSYS\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use INSYS\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use INSYS\Bundle\MaintenanceBundle\Drivers\DriverTtlInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Create a lock action
 *
 * @package INSYSMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class DriverLockCommand extends Command
{
    protected $ttl;

    private $driverFactory;

    public function __construct(DriverFactory $driverFactory)
    {
        parent::__construct();

        $this->driverFactory = $driverFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('insys:maintenance:lock')
            ->setDescription('Lock access to the site while maintenance...')
            ->addArgument('ttl', InputArgument::OPTIONAL, 'Overwrite time to life from your configuration, doesn\'t work with file or shm driver. Time in seconds.', null)
            ->setHelp(<<<EOT

    You can optionally set a time to life of the maintenance

   <info>%command.full_name% 3600</info>

    You can execute the lock without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

    Or

    <info>%command.full_name% 3600 -n</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = $this->getDriver();

        if ($input->isInteractive()) {
            if (!$this->askConfirmation('WARNING! Are you sure you wish to continue? (y/n)', $input, $output)) {
                $output->writeln('<error>Maintenance cancelled!</error>');

                return 1;
            }
        } elseif (null !== $input->getArgument('ttl')) {
            $this->ttl = $input->getArgument('ttl');
        } elseif ($driver instanceof DriverTtlInterface) {
            $this->ttl = $driver->getTtl();
        }

        // set ttl from command line if given and driver supports it
        if ($driver instanceof DriverTtlInterface) {
            $driver->setTtl($this->ttl);
        }

        $output->writeln('<info>'.$driver->getMessageLock($driver->lock()).'</info>');

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $driver = $this->getDriver();
        $default = $driver->getOptions();

        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');

        if (null !== $input->getArgument('ttl') && !is_numeric($input->getArgument('ttl'))) {
            throw new \InvalidArgumentException('Time must be an integer');
        }

        $output->writeln(array(
            '',
            $formatter->formatBlock('You are about to launch maintenance', 'bg=red;fg=white', true),
            '',
        ));

        $ttl = null;
        if ($driver instanceof DriverTtlInterface) {
            if (null === $input->getArgument('ttl')) {
                $output->writeln(array(
                    '',
                    'Do you want to redefine maintenance life time ?',
                    'If yes enter the number of seconds. Press enter to continue',
                    '',
                ));

                $question = new Question(
                    sprintf('<info>%s</info> [<comment>Default value in your configuration: %s</comment>]%s ', 'Set time', $driver->hasTtl() ? $driver->getTtl() : 'unlimited', ':'),
                    isset($default['ttl']) ? $default['ttl'] : 0
                );
                $question->setValidator(function($value) {
                    if (!is_numeric($value)) {
                        throw new \InvalidArgumentException('Time must be an integer');
                    }
                    return $value;
                });
                $question->setMaxAttempts(1);

                /** @var QuestionHelper $questionHelper */
                $questionHelper = $this->getHelper('question');
                $ttl = $questionHelper->ask($input, $output, $question);
            }

            $ttl = (int) $ttl;
            $this->ttl = $ttl ? $ttl : $input->getArgument('ttl');
        } else {
            $output->writeln(array(
                '',
                sprintf('<fg=red>Ttl doesn\'t work with %s driver</>', get_class($driver)),
                '',
            ));
        }
    }

    /**
     * @return AbstractDriver
     */
    private function getDriver()
    {
        return $this->driverFactory->getDriver();
    }

    protected function askConfirmation(string $question, InputInterface $input, OutputInterface $output): bool
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return (bool) $helper->ask($input, $output, new ConfirmationQuestion($question));
    }
}
