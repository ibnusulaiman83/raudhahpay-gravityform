<?php

abstract class RaudhahPayGravityConnect
{
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const DEFAULT_SUCCESS_CODE = 200;
    const DEFAULT_ERROR_CODE = 400;

    const ALLOWED_KEY = [
        'ref_id',
        'bill_id',
        'bill_no',
        'ref1',
        'ref2',
        'payment_method',
        'status',
        'paid',
        'currency',
        'amount',
        'signature',
    ];

    protected $webServiceUrl;
    protected $accessToken;
    protected $collectionId;

    protected function getHeader()
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' =>'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken
        ];
    }

    protected function post($route, $params, $include = null)
    {
        $url = $this->webServiceUrl . '/' . $route;

        if (!is_null($include)) {
            $url = $url . '?include=' . $include;
        }

        $data['sslverify'] = false;
        $data['headers'] = $this->getHeader();
        $data['method'] = self::METHOD_POST;
        $data['body'] = json_encode($params);

        $response = \wp_remote_post($url, $data);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $responseCode = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return [$responseCode, $body];
    }

    protected function validateIpnResponse($requestData, $signatureKey)
    {
        $hashedData = $this->hashData($requestData, $signatureKey);

        if ($requestData['signature'] !== $hashedData) {
            throw new Exception('Signature does not matched.');
        }

        return true;
    }

    public function getIpnResponseData()
    {
        if ($this->validGetIpnRequest()) {
            $data = array_filter($_GET, function($key) {
                return in_array($key, self::ALLOWED_KEY);
            }, ARRAY_FILTER_USE_KEY);

            ksort($data);

            return $data;
        } else if ($this->validPostIpnRequest()) {
            $data = [
                'ref_id' => isset($_POST['ref_id']) ? $_POST['ref_id'] : '',
                'bill_id' => isset($_POST['bill_id']) ? $_POST['bill_id'] : '',
                'bill_no' => isset($_POST['bill_no']) ? $_POST['bill_no'] : '',
                'ref1' => isset($_POST['ref1']) ? $_POST['ref1'] : '',
                'ref2' => isset($_POST['ref2']) ? $_POST['ref2'] : '',
                'payment_method' => isset($_POST['payment_method']) ? $_POST['payment_method'] : '',
                'status' => isset($_POST['status']) ? $_POST['status'] : '',
                'paid' => isset($_POST['paid']) ? $_POST['paid'] : '',
                'currency' => isset($_POST['currency']) ? $_POST['currency'] : '',
                'amount' => isset($_POST['amount']) ? $_POST['amount'] : '',
                'signature' => isset($_POST['signature']) ? $_POST['signature'] : '',
            ];

            return $this->sanitizeInputData($data);
        } else {
            return null;
        }
    }

    private function validGetIpnRequest()
    {
        return $_SERVER['REQUEST_METHOD'] == self::METHOD_GET
                && isset($_GET['ref2'])
                && isset($_GET['paid'])
                && isset($_GET['status'])
                && isset($_GET['signature']);
    }

    private function validPostIpnRequest()
    {
        return $_SERVER['REQUEST_METHOD'] == self::METHOD_POST
                && isset($_POST['ref2'])
                && isset($_POST['paid'])
                && isset($_POST['status'])
                && isset($_POST['signature']);
    }

    private function sanitizeInputData(array $inputs)
    {
        foreach ($inputs as $key => $item) {
            $inputs[$key] = sanitize_text_field($item);
        }

        return $inputs;
    }

    private function hashData($data, $signatureKey)
    {
        $formattedData = '';
        ksort($data);

        foreach ($data as $key => $value) {
            if ($key == 'signature')
                continue;

            if (strlen($formattedData) > 0) {
                $formattedData .= '|';
            }

            $formattedData .= $key . ':' . ((is_null($value)) ? '' : $value);
        }

        return hash_hmac('sha256', $formattedData, $signatureKey);
    }
}