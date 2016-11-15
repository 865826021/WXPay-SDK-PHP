<?php

namespace WXPay;

class WXPayConstants
{
    const SIGN = "sign";
    const DEFAULT_TIMEOUT  = 6.0; // ms
    const MICROPAY_URL     = 'https://api.mch.weixin.qq.com/pay/micropay';
    const UNIFIEDORDER_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    const ORDERQUERY_URL   = 'https://api.mch.weixin.qq.com/pay/orderquery';
    const REVERSE_URL      = 'https://api.mch.weixin.qq.com/secapi/pay/reverse';
    const CLOSEORDER_URL   = 'https://api.mch.weixin.qq.com/pay/closeorder';
    const REFUND_URL       = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    const REFUNDQUERY_URL  = 'https://api.mch.weixin.qq.com/pay/refundquery';
    const DOWNLOADBILL_URL = 'https://api.mch.weixin.qq.com/pay/downloadbill';
    const REPORT_URL       = 'https://api.mch.weixin.qq.com/pay/report';
    const SHORTURL_URL     = 'https://api.mch.weixin.qq.com/tools/shorturl';
    const AUTHCODETOOPENID_URL = 'https://api.mch.weixin.qq.com/tools/authcodetoopenid';
}