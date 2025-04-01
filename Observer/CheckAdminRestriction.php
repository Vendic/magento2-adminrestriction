<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
declare(strict_types=1);

namespace MSP\AdminRestriction\Observer;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MSP\AdminRestriction\Api\RestrictInterface;
use MSP\SecuritySuiteCommon\Api\AlertInterface;
use MSP\SecuritySuiteCommon\Api\LockDownInterface;

class CheckAdminRestriction implements ObserverInterface
{
    public function __construct(
        private RestrictInterface $restrict,
        private AlertInterface $securitySuite,
        private LockDownInterface $lockDown
    ) {
    }

    public function execute(Observer $observer)
    {
        if (!$this->restrict->isAllowed()) {
            $this->securitySuite->event(
                'MSP_AdminRestriction',
                'Unauthorized access attempt',
                AlertInterface::LEVEL_WARNING
            );

            $action = $observer->getData('controller_action');

            return $this->lockDown->doActionLockdown($action, __('Unauthorized access attempt'));
        }
    }
}
