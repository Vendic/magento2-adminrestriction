<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */
declare(strict_types=1);

namespace MSP\AdminRestriction\Plugin;

use Magento\TwoFactorAuth\Model\TfaSession;
use MSP\AdminRestriction\Api\RestrictInterface;
use MSP\AdminRestriction\Model\Restrict\RestrictStatus;
use MSP\SecuritySuiteCommon\Api\AlertInterface;

class BypassTwoFactorAuth
{
    public function __construct(
        private AlertInterface $securitySuite,
        private RestrictInterface $restrict,
    )
    {
    }

    public function afterIsGranted(TfaSession $subject, $result): bool
    {
        if ($this->restrict->isAllowed()) {
            $this->securitySuite->event(
                'MSP_AdminRestriction',
                'Bypassed two factor authentication, IP is on whitelist',
                AlertInterface::LEVEL_INFO
            );

            return true;
        }

        $this->securitySuite->event(
            'MSP_AdminRestriction',
            '2FA needed, IP is not on the whitelist',
            AlertInterface::LEVEL_WARNING
        );

        return $result;
    }
}
