<?php

namespace MSP\AdminRestriction\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MSP\AdminRestriction\Api\RestrictInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use MSP\AdminRestriction\Model\Restrict;

class AddRestrictIp extends Command
{
    public function __construct(
        private readonly ScopeConfigInterface $config,
        private readonly ConfigInterface      $scopeConfig,
        private readonly RestrictInterface    $restrict,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('msp:security:admin_restriction:add_ip');
        $this->setDescription('Add IP to Admin Restriction');
        $this->addArgument('ip', InputArgument::REQUIRED, __('Authorized comma separated IP list'));
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputIps = $input->getArgument('ip');
        $ips = explode(',', $inputIps);
        $changes = [];
        $allowedList = $this->restrict->getAllowedRanges();

        // Loop over ips
        foreach ($ips as $ip) {
            try {
                $this->validateIP($ip);
            } catch (\InvalidArgumentException $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                return 1;
            }

            if (in_array($ip, $allowedList)) {
                $output->writeln(sprintf('<info>IP %s already exists</info>', $ip));
                continue;
            }

            $changes[] = $ip;
        }

        if (!$changes) {
            $output->writeln('<info>No changes have been made</info>');
            return 0;
        }

        $this->scopeConfig->saveConfig(
            RestrictInterface::XML_PATH_AUTHORIZED_RANGES,
            implode(',', array_merge($allowedList, $changes))
        );

        $output->writeln(sprintf('<info>IPs %s will be added to the list</info>', implode(', ', $changes)));

        return 0;
    }

    private function validateIP(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException(sprintf('Invalid IP address: %s', $ip));
        }
    }
}
