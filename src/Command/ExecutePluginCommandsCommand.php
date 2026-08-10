<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Payever\Bundle\PaymentBundle\Service\JobHandler\PaymentCommandJobHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecutePluginCommandsCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    /** @var string */
    protected static $defaultName = 'payever:execute-plugin-commands';

    private ConfigManager $configManager;
    private PaymentCommandJobHandler $task;

    public function __construct(
        ConfigManager $configManager,
        PaymentCommandJobHandler $task
    ) {
        $this->configManager = $configManager;
        $this->task = $task;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Execute plugin commands.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> execute plugin commands.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        $businessUuid = $this->configManager->get('payever_payment.business_uuid');

        return !empty($businessUuid);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->task->run();

        return self::SUCCESS;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }
}
