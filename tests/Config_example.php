<?php
/**
 * 拷贝到Config.php，WXPayTest.php 会用到
 * cp Config_example.php Config.php
 */


class Config
{
    const WXPAY_APPID = 'wx888888888';
    const WXPAY_MCHID = '22222222';
    const WXPAY_KEY = '123456781234567812345678';
    const WXPAY_CERTPEMPATH = '/path/to/apiclient_cert.pem';
    const WXPAY_KEYPEMPATH = '/path/to/apiclient_key.pem';
}