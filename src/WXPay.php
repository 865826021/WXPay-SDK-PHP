<?php

namespace WXPay;

use GuzzleHttp\Client as HttpClient;

class WXPay
{
    /**
     * WXPayApi constructor.
     * @param string $appId 公众帐号ID
     * @param string $mchId 商户号
     * @param string $key API密钥
     * @param string $certPemPath 商户pem格式证书文件路径
     * @param string $keyPemPath 商户pem格式证书密钥文件路径
     * @param float $timeout 单位秒，默认6.0
     */
    function __construct($appId, $mchId, $key, $certPemPath, $keyPemPath, $timeout=WXPayConstants::DEFAULT_TIMEOUT) {
        $this->appId = $appId;
        $this->mchId = $mchId;
        $this->key = $key;
        $this->certPemPath = $certPemPath;
        $this->keyPemPath = $keyPemPath;
        $this->timeout = $timeout;
    }

    /**
     * 签名是否合法
     * @param array $data
     * @return bool
     */
    private function isSignatureValid($data) {
        return WXPayUtil::isSignatureValid($data, $this->key);
    }

    /**
     * 处理去wxpay请求后的返回数据
     * @param string $xml
     * @return array
     * @throws \Exception
     */
    private function processResponseXml($xml) {
        $RETURN_CODE = "return_code";
        $FAIL = "FAIL";
        $SUCCESS = "SUCCESS";
        $data = WXPayUtil::xml2array($xml);

        if (array_key_exists($RETURN_CODE, $data)) {
            $return_code = $data[$RETURN_CODE];
        }
        else {
            throw new \Exception("Invalid XML. There is no `return_code`");
        }

        if ($return_code === $FAIL) {
            return $data;
        }
        elseif ($return_code === $SUCCESS) {
            if ($this->isSignatureValid($data)) {
                return $data;
            }
            else {
                throw new \Exception("Invalid signature in XML.");
            }
        }
        else {
            throw new \Exception("Invalid XML. `return_code` value ${return_code} is invalid");
        }
    }

    /**
     * 生成Https请求的XML数据
     * @param array $data
     * @return string
     */
    private function makeHttpRequestBody($data) {
        $newData = array();
        foreach ($data as $k => $v) {
            $newData[$k] = $v;
        }
        $newData['appid'] = $this->appId;
        $newData['mch_id'] = $this->mchId;
        $newData['nonce_str'] = WXPayUtil::generateNonceStr();
        return WXPayUtil::generateSignedXml($newData, $this->key);
    }

    /**
     * Https请求，不带证书
     * @param string $url URL
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，秒
     * @return string 返回的xml数据
     * @throws \Exception
     */
    private function requestWithoutCert($url, $reqData, $timeout=null) {
        if ($timeout == null) {
            $timeout = $this->timeout;
        }
        $client = new HttpClient(['timeout'  => $timeout]);
        $reqXml = $this->makeHttpRequestBody($reqData);
        $resp = $client->post($url, ['body' => $reqXml]);
        if ($resp->getStatusCode() == 200) {
            return $resp->getBody()->getContents();
        }
        else {
            throw new \Exception('HTTP Response status code is not 200');
        }
    }

    /**
     * Https请求，带证书
     * @param string $url URL
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，秒
     * @return string 返回的xml数据
     * @throws \Exception
     */
    private function requestWithCert($url, $reqData, $timeout=null) {
        if ($timeout == null) {
            $timeout = $this->timeout;
        }
        $client = new HttpClient(['timeout'  => $timeout]);
        $reqXml = $this->makeHttpRequestBody($reqData);
        $resp = $client->post($url, array(
            'body' => $reqXml,
            'cert' => $this->certPemPath,
            'ssl_key' => $this->keyPemPath,
        ));
        if ($resp->getStatusCode() == 200) {
            return $resp->getBody()->getContents();
        }
        else {
            throw new \Exception('HTTP Response status code is not 200');
        }
    }

    /**
     * 提交刷卡支付
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function microPay($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::MICROPAY_URL, $reqData, $timeout));
    }

    /**
     * 统一下单
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function unifiedOrder($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::UNIFIEDORDER_URL, $reqData, $timeout));
    }

    /**
     * 订单查询
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function orderQuery($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::ORDERQUERY_URL, $reqData, $timeout));
    }

    /**
     * 撤销订单（用于刷卡支付）
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function reverse($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithCert(WXPayConstants::REVERSE_URL, $reqData, $timeout));
    }

    /**
     * 关闭订单
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function closeOrder($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::CLOSEORDER_URL, $reqData, $timeout));
    }

    /**
     * 申请退款
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function refund($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithCert(WXPayConstants::REFUND_URL, $reqData, $timeout));
    }

    /**
     * 退款查询
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function refundQuery($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::REFUNDQUERY_URL, $reqData, $timeout));
    }

    /**
     * 下载对账单
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据  注意，若下载成功，wxpay只会返回对账单数据，非XML。该函数对此做了封装，加上了return_code和return_msg
     * @throws \Exception
     */
    public function downloadBill($reqData, $timeout=null) {
        $respContent = $this->requestWithoutCert(WXPayConstants::DOWNLOADBILL_URL, $reqData, $timeout);
        $respContent = trim($respContent);
        if (strlen($respContent) === 0) {
            throw new \Exception('HTTP response is empty!');
        }
        if (strlen($respContent) > 0 && substr( $respContent, 0, 1 ) === "<") {  // xml
            return WXPayUtil::xml2array($respContent);
        }
        else {  // 对账单数据
            return array(
                'return_code' => 'SUCCESS',
                'return_msg' => 'OK',
                'data' => $respContent
                );
        }
    }

    /**
     * 交易保障
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function report($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::REPORT_URL, $reqData, $timeout));
    }

    /**
     * 转换短链接
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function shortUrl($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::SHORTURL_URL, $reqData, $timeout));
    }

    /**
     * 授权码查询OPENID接口
     * @param array $reqData 请求数据
     * @param null|float $timeout 超时时间，单位是秒
     * @return array wxpay返回数据
     */
    public function authCodeToOpenid($reqData, $timeout=null) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::AUTHCODETOOPENID_URL, $reqData, $timeout));
    }
}