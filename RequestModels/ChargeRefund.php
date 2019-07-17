<?php

namespace Payum\Checkoutcom\RequestModels;

use com\checkout\ApiServices\Charges\RequestModels\ChargeRefund as BaseChargeRefund;

/**
 * Class RequestModels\ChargeRefund
 */
class ChargeRefund extends BaseChargeRefund
{
    private $trackId;

    /**
     * @return mixed
     */
    public function getTrackId()
    {
        return $this->trackId;
    }

    /**
     * @param mixed $trackId
     */
    public function setTrackId($trackId)
    {
        $this->trackId = $trackId;
    }
}
