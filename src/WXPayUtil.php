<?php

namespace WXPay;

class WXPayUtil
{
    /**
     * 将array转换为XML格式的字符串
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public static function array2xml($data) {
        $xml = new \SimpleXMLElement('<xml/>');
        foreach($data as $k => $v ) {
            if (is_string($k) && (is_numeric($v) || is_string($v))) {
                $xml->addChild("$k",htmlspecialchars("$v"));
            }
            else {
                throw new \Exception('Invalid array, will not be converted to xml');
            }
        }
        return $xml->asXML();
    }

    /**
     * 将XML格式字符串转换为array
     * 参考： http://php.net/manual/zh/book.simplexml.php
     * @param string $str XML格式字符串
     * @return array
     * @throws \Exception
     */
    public static function xml2array($str) {
        $xml = simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $result = array();
        $bad_result = json_decode($json,TRUE);  // value，一个字段多次出现，结果中的value是数组
        // return $bad_result;
        foreach ($bad_result as $k => $v) {
            if (is_array($v)) {
                if (count($v) == 0) {
                    $result[$k] = '';
                }
                else if (count($v) == 1) {
                    $result[$k] = $v[0];
                }
                else {
                    throw new \Exception('Duplicate elements in XML. ' . $str);
                }
            }
            else {
                $result[$k] = $v;
            }
        }
        return $result;
    }


    /**
     * 生成签名
     * @param $data array
     * @param $wxpayKey
     * @return string
     * @throws \Exception
     */
    public static function generateSignature($data, $wxpayKey) {
        $combineStr = '';
        $keys = array_keys($data);
        asort($keys);  // 排序
        foreach($keys as $k) {
            $v = $data[$k];
            if ($k == WXPayConstants::SIGN) {
                continue;
            }
            elseif ((is_string($v) && strlen($v) > 0) || is_numeric($v) ) {
                $combineStr = "${combineStr}${k}=${v}&";
            }
            elseif (is_string($v)  && strlen($v) == 0) {
                continue;
            }
            else {
                throw new \Exception('Invalid data, cannot generate signature');
            }
        }
        $combineStr = "${combineStr}key=${wxpayKey}";
        return strtoupper(md5($combineStr));
    }

    /**
     * 验证签名是否合法
     * @param array $data
     * @param string $wxpayKey API密钥
     * @return bool
     */
    public static function isSignatureValid($data, $wxpayKey) {
        if ( !array_key_exists(WXPayConstants::SIGN, $data) ) {
            return false;
        }
        $sign = $data[WXPayConstants::SIGN];
        try {
            $generatedSign = WXPayUtil::generateSignature($data, $wxpayKey);
            // echo "签名: ${generatedSign} \n";
            if ($sign === $generatedSign) {
                return true;
            }
            else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 生成含有签名数据的XML格式字符串
     * @param array $data
     * @param string $wxpayKey
     * @return string
     */
    public static function generateSignedXml($data, $wxpayKey) {
        $newData = array();
        foreach ($data as $k => $v) {
            $newData[$k] = $v;
        }
        $sign = WXPayUtil::generateSignature($data, $wxpayKey);
        $newData[WXPayConstants::SIGN] = $sign;
        return WXPayUtil::array2xml($newData);
    }

    /**
     * 生成 nonce str
     * 参考: http://php.net/manual/zh/function.uniqid.php
     * @return string
     */
    public static function generateNonceStr() {
        return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

}