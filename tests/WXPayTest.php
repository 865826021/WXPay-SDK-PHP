<?php
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/Config.php';

use WXPay\WXPay;

/**
 * 测试统一下单
 */
function test_orderQuery() {
    $wxpay = new WXPay(
        Config::WXPAY_APPID,
        Config::WXPAY_MCHID,
        Config::WXPAY_KEY,
        Config::WXPAY_CERTPEMPATH,
        Config::WXPAY_KEYPEMPATH,
        6.0);

    var_dump( $wxpay->orderQuery(array('out_trade_no' => '201610265257070987061763')) );

}

// test_orderQuery();

/**
 * 测试退款
 */
function test_refund() {
    $reqData = array(
        'out_trade_no' => '201610265257070987061763',
        'out_refund_no' => '201610265257070987061763',
        'total_fee' => 1,
        'refund_fee' => 1,
        'op_user_id' => '100'
    );
    $wxpay = new WXPay(
        Config::WXPAY_APPID,
        Config::WXPAY_MCHID,
        Config::WXPAY_KEY,
        Config::WXPAY_CERTPEMPATH,
        Config::WXPAY_KEYPEMPATH,
        6.0);

    var_dump($wxpay->refund($reqData));
}

// test_refund();


/**
 * 测试下载对账单
 */
function test_downloadBill() {
    $reqData = array(
        'bill_date' => '20161102',
        'bill_type' => 'ALL'
    );

    $wxpay = new WXPay(
        Config::WXPAY_APPID,
        Config::WXPAY_MCHID,
        Config::WXPAY_KEY,
        Config::WXPAY_CERTPEMPATH,
        Config::WXPAY_KEYPEMPATH,
        6.0);

    var_dump( $wxpay->downloadBill($reqData) );
}

// test_downloadBill();