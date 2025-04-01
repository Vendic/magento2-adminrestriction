<?php

declare(strict_types=1);

namespace MSP\AdminRestriction\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use MSP\AdminRestriction\Api\RestrictInterface;
use MSP\SecuritySuiteCommon\Api\AlertInterface;
use MSP\SecuritySuiteCommon\Api\LockDownInterface;

class CheckAdminRestriction implements ObserverInterface
{
    private const XML_PATH_CONFIG_ENABLE = 'twofactorauth/general/enable';

    public function __construct(
        private RestrictInterface $restrict,
        private AlertInterface $securitySuite,
        private LockDownInterface $lockDown,
        private Manager $moduleManager,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    public function execute(Observer $observer)
    {
        // Account for the 2FA extension not being enabled.
        if ($this->moduleManager->isEnabled('Magento_TwoFactorAuth')) {
            return $this;
        }

        // When 2FA is enabled we don't need this controller. We'll bypass 2FA in
        // \MSP\AdminRestriction\Plugin\BypassTwoFactorAuth
        if ($this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_ENABLE)) {
            return $this;
        }

        if ($this->restrict->isEnabled() === false) {
            return $this;
        }

        // Do IP whitelist check in case 2FA extension is turned off or is disabled via
        // markshust/magento2-module-disabletwofactorauth
        if ($this->restrict->isAllowed()) {
            return $this;
        }

        $this->restrictAdmin($observer);

        return $this;
    }

    private function restrictAdmin(Observer $observer): void
    {
        $this->securitySuite->event(
            'MSP_AdminRestriction',
            'Unauthorized access attempt',
            AlertInterface::LEVEL_WARNING
        );

        $this->restrictStatus->setAdminWasRestricted(false);

        $action = $observer->getData('controller_action');

        $this->lockDown->doActionLockdown($action, __('Unauthorized access attempt'));
    }
}
