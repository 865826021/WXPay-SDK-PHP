<?php

require __DIR__.'/../../vendor/autoload.php';

use WXPay\WXPayConstants;
use PHPUnit\Framework\TestCase;



class StackTest extends TestCase
{
    public function testValue()
    {
        $this->assertEquals('sign', WXPayConstants::SIGN);
    }
}