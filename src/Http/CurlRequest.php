<?php

namespace DoctrineElastic\Http;

/**
 * Class ElasticRequest
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class CurlRequest
{
    protected $baseUrl = '';

    public function request($url, $data, $method = 'POST', array $headers = null)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            if (!boolval($headers)) {
                $headers = ['Content-Type: application/json; charset=utf-8'];
            }

            switch ($method) {
                case 'GET':
                    $url .= '?';

                    if (is_array($data)) {
                        foreach ($data as $key => $value) {
                            $url .= $key . '=' . $value . '&';
                        }
                    } elseif (is_string($data)) {
                        $url .= $data;
                    }

                    rtrim($url, '&');
                    break;
                case 'POST':
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_POST, true);
                    break;
                case 'HEAD':
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    break;
                default:
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            if (in_array($method, ['POST', 'PUT'])) {
                if (boolval($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
                }
            }

            $url = rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
            curl_setopt($ch, CURLOPT_URL, $url);

            $resultJson = curl_exec($ch);

            $content = json_decode($resultJson, true);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if (is_null($content) && in_array($httpcode, [400, 500])) {
                return array('status' => $httpcode, 'content' => array('error' => ['reason' => $resultJson]));
            }

            return array('status' => $httpcode, 'content' => $content);
        } catch (\Exception $ex) {
            return array('status' => 500, 'content' => array('error' => ['reason' => $ex->getMessage()], 'detail' => $ex->getTraceAsString(), 'line' => $ex->getLine()));
        }
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

}