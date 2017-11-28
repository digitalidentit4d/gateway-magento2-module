<?php
/**
 * Copyright (c) 2017. On Tap Networks Limited.
 */
namespace OnTap\MasterCard\Model\Method\Masterpass;

use OnTap\MasterCard\Model\Method\WalletInterface;

class DirectWallet extends \OnTap\MasterCard\Model\Method\Wallet implements WalletInterface
{
    /**
     * @return array
     */
    public function getJsConfig()
    {
        return [
            'adapter_component' => $this->getMethodConfig()->getValue('adapter_component'),
            'callbackUrl' => $this->getUrlBuilder()->getUrl('mpgs/session/updateMasterpass')
        ];
    }
}