<?php

declare(strict_types=1);

namespace MSP\AdminRestriction\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MSP\AdminRestriction\Api\RestrictInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

class AddRestrictIp extends Command
{
    private const ARG_IP = 'ip';

    public function __construct(
        private readonly ConfigInterface $scopeConfig,
        private readonly RestrictInterface $restrict,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('msp:security:admin_restriction:add_ip')
            ->setDescription('Add IP to Admin Restriction')
            ->addArgument(
                self::ARG_IP,
                InputArgument::REQUIRED,
                __('Authorized comma-separated IP list')->render()
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputIps = $input->getArgument(self::ARG_IP);
        $ips = explode(',', $inputIps);
        $allowedList = $this->restrict->getAllowedRanges();
        $changes = [];

        foreach ($ips as $ip) {
            if (!$this->isValidIP($ip)) {
                $output->writeln(sprintf('<error>Invalid IP address: %s</error>', $ip));
                return Command::FAILURE;
            }

            if (in_array($ip, $allowedList, true)) {
                $output->writeln(sprintf('<info>IP %s already exists</info>', $ip));
                continue;
            }

            $changes[] = $ip;
        }

        if (empty($changes)) {
            $output->writeln('<info>No changes have been made</info>');
            return Command::SUCCESS;
        }

        $this->scopeConfig->saveConfig(
            RestrictInterface::XML_PATH_AUTHORIZED_RANGES,
            implode(',', array_merge($allowedList, $changes))
        );

        $output->writeln(sprintf(
            '<info>IPs %s have been added to the list</info>',
            implode(', ', $changes)
        ));

        return Command::SUCCESS;
    }

    private function isValidIP(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}
