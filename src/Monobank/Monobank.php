<?php

declare(strict_types = 1);

namespace Monobank;

/**
 * Class Monobank
 *
 * @package Monobank
 */
final class Monobank
{
    /**
     * @var string
     */
    private string $apiKey;

    /**
     * @var string
     */
    private string $baseApiUrl;

    /**
     * @var string
     */
    private string $storeId;

    /**
     * @var mixed
     */
    private $serverResponseCode;

    /**
     * Monobank constructor.
     *
     * @param string $apiKey
     * @param string $baseApiUrl
     * @param string $storeId
     */
    public function __construct(
        string $apiKey = "secret_98765432--123-123",
        string $baseApiUrl = 'https://u2-demo.ftband.com/',
        string $storeId = 'test_store_with_confirm'
    ) {
        $this->apiKey = $apiKey;
        $this->baseApiUrl = $baseApiUrl;
        $this->storeId = $storeId;
    }

    /**
     * @param string $phone
     * @return array
     */
    public function validateClientByPhone(string $phone) : array
    {
        $apiUri = '/api/v2/client/validate';
        $requestParameter = ['phone' => $phone];
        $this->validateRequestData($apiUri, $requestParameter);
        $responseRaw = $this->execApiRequest($apiUri, $requestParameter);

        $response['success'] = isset($responseRaw['found']);
        if ($response['success'] === true) {
            $response['clientExist'] = $responseRaw['found'];
        } else {
            $response['error'] = 'System error';
        }
        return $response;
    }

    /**
     * @param array $orderData
     * @return array
     */
    public function initOrderPayment(array $orderData) : array
    {
        $apiUri = 'api/order/create';
        $this->validateRequestData($apiUri, $orderData);
        $responseRaw = $this->execApiRequest($apiUri, $orderData);

        $response['success'] = isset($responseRaw['order_id']);
        if ($response['success'] === true) {
            $response['orderId'] = $responseRaw['order_id'];
        } else {
            $response['error'] = $responseRaw['message'];
        }

        return $response;
    }

    /**
     * @param string $orderId
     * @return array
     */
    public function checkPaymentByOrder(string $orderId) : array
    {
        $apiUri = 'api/order/check/paid';
        try {
            $responseRaw = $this->makeApiRequestWithOrderId($apiUri, $orderId);
        } catch (\Exception $exception) {
            $responseRaw['message'] = $exception->getMessage();
        }

        $response['success'] = isset($responseRaw['fully_paid']);
        if ($response['success'] === true) {
            $response['possibleReturnToCard'] = $responseRaw['bank_can_return_money_to_card'];
            $response['fullSumPayed'] = $responseRaw['fully_paid'];
        } else {
            $response['error'] = $responseRaw['message'];
        }
        return $response;
    }

    /**
     * @param string $orderId
     * @return array
     */
    public function confirmOrderProcessing(string $orderId) : array
    {
        $apiUri = 'api/order/confirm';

        try {
            $responseRaw = $this->makeApiRequestWithOrderId($apiUri, $orderId);
        } catch (\Exception $exception) {
            $responseRaw['message'] = $exception->getMessage();
        }

        $response['success'] = isset($responseRaw['state']);
        if ($response['success'] === true) {
            $response['orderId'] = $responseRaw['order_id'];
            $response['state'] = $responseRaw['state'];
            $response['subState'] = $responseRaw['order_sub_state'];
        } else {
            $response['error'] = $responseRaw['message'];
        }
        return $response;
    }

    /**
     * @param string $orderId
     * @return array
     */
    public function rejectOrderProcessing(string $orderId) : array
    {
        $apiUri = 'api/order/reject';

        try {
            $responseRaw = $this->makeApiRequestWithOrderId($apiUri, $orderId);
        } catch (\Exception $exception) {
            $responseRaw['message'] = $exception->getMessage();
        }

        $response['success'] = isset($responseRaw['state']);
        if ($response['success'] === true) {
            $response['orderId'] = $responseRaw['order_id'];
            $response['state'] = $responseRaw['state'];
            $response['subState'] = $responseRaw['order_sub_state'];
        } else {
            $response['error'] = $responseRaw['message'];
        }
        return $response;
    }

    /**
     * @param array $requestData
     * @return array
     */
    public function rejectOrder(array $requestData) : array
    {
        $apiUri = 'api/order/return';
        $this->validateRequestData($apiUri, $requestData);
        $responseRaw = $this->getResponseFromAPI($apiUri, $requestData);
        $response['success'] = isset($responseRaw['status']) && $responseRaw['status'] === 'OK';
        if ($response['success'] !== true) $response['error'] = $responseRaw['message'];

        return $response;
    }

    /**
     * @param string $orderId
     * @return array
     */
    public function checkOrderStatus(string $orderId) : array
    {
        $apiUri = 'api/order/state';
        try {
            $responseRaw = $this->makeApiRequestWithOrderId($apiUri, $orderId);
        } catch (\Exception $exception) {
            $responseRaw['message'] = $exception->getMessage();
        }

        $response['success'] = isset($responseRaw['state']);
        if ($response['success'] === true) {
            $response['orderId'] = $responseRaw['order_id'];
            $response['state'] = $responseRaw['state'];
            $response['subState'] = $responseRaw['order_sub_state'];
        } else {
            $response['error'] = $responseRaw['message'];
        }
        return $response;
    }

    /**
     * @param string $apiUri
     * @param array  $requestParameter
     * @return array
     */
    private function getResponseFromAPI(string $apiUri, array $requestParameter) : array
    {
        $this->validateRequestData($apiUri, $requestParameter);
        return $this->execApiRequest($apiUri, $requestParameter);
    }

    /**
     * @param string $apiUri
     * @param array  $requestParams
     */
    private function validateRequestData(string $apiUri, array $requestParams) : void
    {
        switch ($apiUri) {
            case 'api/client/validate':
                if (isset($requestParams['phone']) === false || strlen($requestParams['phone']) !== 13) {
                    throw new \InvalidArgumentException(
                        "Invalid data passed to request. Phone must have 12 digits with '+' and look like +380931231212"
                    );
                }
                break;
            case 'api/order/create':
                if (
                    isset($requestParams['store_order_id']) === false &&
                    isset($requestParams['client_phone']) === false &&
                    isset($requestParams['invoice']) === false &&
                    isset($requestParams['available_programs']) === false &&
                    isset($requestParams['products']) === false &&
                    isset($requestParams['total_sum']) === false
                ) {
                    throw new \InvalidArgumentException(
                        'Invalid data passed to request. Some of required fields - not exists'
                    );
                }
                break;
            case 'api/order/return':
                if (
                    isset($requestParams['order_id']) === false ||
                    isset($requestParams['return_money_to_card']) === false ||
                    isset($requestParams['store_return_id']) === false ||
                    isset($requestParams['sum']) === false
                ) {
                    throw new \InvalidArgumentException(
                        'Invalid data passed to request. Some of required fields - not exists'
                    );
                }
                break;
            case 'api/order/check/paid':
            case 'api/order/confirm':
            case 'api/order/reject':
            case 'api/order/state':
            if (isset($requestParams['order_id']) === false) {
                throw new \InvalidArgumentException(
                    'Invalid data passed to request. Field order_id not exist in request'
                );
            }
            break;
        }
    }

    /**
     * @param string $apiUri
     * @param string $orderId
     * @return array
     */
    private function makeApiRequestWithOrderId(string $apiUri, string $orderId) : array
    {
        $requestParameter = ['order_id' => $orderId];
        $this->validateRequestData($apiUri, $requestParameter);
        return $this->getResponseFromAPI($apiUri, $requestParameter);
    }

    /**
     * @param string $apiUri
     * @param array  $requestData
     * @param string $method
     * @return array
     */
    private function execApiRequest(string $apiUri, array $requestData, $method = 'POST') : array
    {
        $url = $this->baseApiUrl . $apiUri;
        $result = [];
        $requestString = json_encode($requestData);
        $signature = base64_encode(hash_hmac('sha256', $requestString, $this->apiKey, true));
        $headers = [
            'store-id: ' . $this->storeId,
            'signature: ' . $signature,
            'Content-Type: application/json',
            'my-header: a66b0275-9872-4fa2-9489-d91b085495a4',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $server_output = curl_exec($ch);
        $this->serverResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($this->serverResponseCode !== 504) {
            $result = json_decode($server_output, true) ?? [];
        }
        return $result;
    }
}
