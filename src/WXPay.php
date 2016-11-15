<?php

namespace WXPay;

use GuzzleHttp\Client as HttpClient;

class WXPay
{
    /**
     * WXPayApi constructor.
     * @param $appId
     * @param $mchId
     * @param $key
     * @param $certPemPath
     * @param $keyPemPath
     * @param $timeout float 单位秒，默认5.0
     */
    function __construct($appId, $mchId, $key, $certPemPath, $keyPemPath, $timeout=5.0) {
        $this->appId = $appId;
        $this->mchId = $mchId;
        $this->key = $key;
        $this->certPemPath = $certPemPath;
        $this->keyPemPath = $keyPemPath;
        $this->timeout = $timeout;
        $this->client = new HttpClient(['timeout'  => $this->timeout]);
    }

    /**
     * 签名是否合法
     * @param $data array
     * @return bool
     */
    private function isSignatureValid($data) {
        return WXPayUtil::isSignatureValid($data, $this->key);
    }

    /**
     * 处理去wxpay请求后的返回数据
     * @param $xml string
     * @return mixed array
     * @throws \Exception
     */
    private function processResponseXml($xml) {
        $RETURN_CODE = "return_code";
        $FAIL = "FAIL";
        $SUCCESS = "SUCCESS";
        $data = WXPayUtil::xml2array($xml);

        // var_dump($data);
        // echo "\n------\n";

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
                // echo "Invalid signature in XML.";
                // echo "\n-----\n";
                throw new \Exception("Invalid signature in XML.");
            }
        }
        else {
            throw new \Exception("Invalid XML. `return_code` value ${return_code} is invalid");
        }
    }

    /**
     * 生成Https请求的XML数据
     * @param $data
     * @return mixed string
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
     * @param $url string
     * @param $reqData array 请求数据
     * @return mixed string 返回的xml数据
     * @throws \Exception
     */
    private function requestWithoutCert($url, $reqData) {
        $reqXml = $this->makeHttpRequestBody($reqData);
        $resp = $this->client->post($url, ['body' => $reqXml]);
        if ($resp->getStatusCode() == 200) {
            return $resp->getBody()->getContents();
        }
        else {
            throw new \Exception('HTTP Response status code is not 200');
        }
    }

    /**
     * Https请求，带证书
     * @param $url string
     * @param $reqData array 请求数据
     * @return mixed string 返回的xml数据
     * @throws \Exception
     */
    private function requestWithCert($url, $reqData) {
        $reqXml = $this->makeHttpRequestBody($reqData);
        $resp = $this->client->post($url, array(
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
     * @param $reqData array
     * @return mixed array
     */
    public function microPay($reqData) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::MICROPAY_URL, $reqData));
    }

    /**
     * 统一下单
     * @param $reqData array
     * @return mixed array
     */
    public function unifiedOrder($reqData) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::UNIFIEDORDER_URL, $reqData));
    }

    /**
     * 订单查询
     * @param $reqData array
     * @return mixed array
     */
    public function orderQuery($reqData) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::ORDERQUERY_URL, $reqData));
    }

    /**
     * 撤销订单（用于刷卡支付）
     * @param $reqData array
     * @return mixed array
     */
    public function reverse($reqData) {
        return $this->processResponseXml($this->requestWithCert(WXPayConstants::REVERSE_URL, $reqData));
    }

    /**
     * 关闭订单
     * @param $reqData array
     * @return mixed array
     */
    public function closeOrder($reqData) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::CLOSEORDER_URL, $reqData));
    }

    /**
     * 申请退款
     * @param $reqData array
     * @return mixed array
     */
    public function refund($reqData) {
        return $this->processResponseXml($this->requestWithCert(WXPayConstants::REFUND_URL, $reqData));
    }

    /**
     * 退款查询
     * @param $reqData array
     * @return mixed array
     */
    public function refundQuery($reqData) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::REFUNDQUERY_URL, $reqData));
    }

    /**
     * 下载对账单
     * @param $reqData array
     * @return mixed array
     * @throws \Exception
     */
    public function downloadBill($reqData) {
        $respContent = $this->requestWithoutCert(WXPayConstants::DOWNLOADBILL_URL, $reqData);
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
     * @param $reqData array
     * @return mixed array
     */
    public function report($reqData) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::REPORT_URL, $reqData));
    }

    /**
     * 转换短链接
     * @param $reqData array
     * @return mixed array
     */
    public function shortUrl($reqData) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::SHORTURL_URL, $reqData));
    }

    /**
     * 授权码查询OPENID接口
     * @param $reqData
     * @return mixed
     */
    public function authCodeToOpenid($reqData) {
        return $this->processResponseXml($this->requestWithoutCert(WXPayConstants::AUTHCODETOOPENID_URL, $reqData));
    }
}