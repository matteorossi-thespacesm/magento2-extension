<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector;

class Protocol implements \Ess\M2ePro\Model\Connector\ProtocolInterface
{
    public const COMPONENT_VERSION = 3;

    /**
     * @return string
     */
    public function getComponent(): string
    {
        return 'Walmart';
    }

    /**
     * @return int
     */
    public function getComponentVersion(): int
    {
        return self::COMPONENT_VERSION;
    }
}
