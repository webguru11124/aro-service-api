<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workday\Helpers;

use SimpleXMLElement;
use Throwable;

class WorkdayXmlHelper
{
    private const FAULT_CONTENT_XML_XPATH = '/SOAP-ENV:Envelope/SOAP-ENV:Body/SOAP-ENV:Fault';
    private const WORKER_PHOTO_DATA_XPATH = '/env:Envelope/env:Body/wd:Get_Worker_Photos_Response/wd:Response_Data/wd:Worker_Photo/wd:Worker_Photo_Data';
    private const WORKER_PHOTO_CONTENT_XML_XPATH = self::WORKER_PHOTO_DATA_XPATH . '/wd:File';
    private const WORKER_ID_XML_XPATH = self::WORKER_PHOTO_DATA_XPATH . '/wd:ID';
    private const CONTENT_XML_NAMESPACES = [
        'wd' => 'urn:com.workday/bsvc',
    ];

    /**
     * Gets worker photo from Workday SOAP response
     *
     * @param string $response
     *
     * @return string
     */
    public static function getWorkerPhotoBase64FromXmlResponse(string $response): string
    {
        $xml = new SimpleXMLElement($response);

        foreach (self::CONTENT_XML_NAMESPACES as $namespacePrefix => $namespaceValue) {
            $xml->registerXPathNamespace($namespacePrefix, $namespaceValue);
        }

        return (string) $xml->xpath(self::WORKER_PHOTO_CONTENT_XML_XPATH)[0];
    }

    /**
     * Gets multiple worker photo from Workday SOAP response
     *
     * @param string $response
     *
     * @return array<string, mixed>
     */
    public static function getWorkerPhotosBase64FromXmlResponse(string $response): array
    {
        $xml = new SimpleXMLElement($response);
        foreach (self::CONTENT_XML_NAMESPACES as $namespacePrefix => $namespaceValue) {
            $xml->registerXPathNamespace($namespacePrefix, $namespaceValue);
        }
        $response = [];
        foreach ($xml->xpath(self::WORKER_ID_XML_XPATH) as $key => $idXmlElement) {
            $response[(string) $idXmlElement] = (string) $xml->xpath(self::WORKER_PHOTO_CONTENT_XML_XPATH)[$key];
        }

        return $response;
    }

    /**
     * Generates Workday worker photo SOAP request
     *
     * @param string $workdayId
     *
     * @return string
     */
    public static function getWorkerPhotoXMLRequest(string $workdayId): string
    {
        return self::buildSoapXMLRequest(
            '<bsvc:Get_Worker_Photos_Request xmlns:bsvc="urn:com.workday/bsvc" bsvc:version="v39.1">
                <bsvc:Request_References bsvc:Skip_Non_Existing_Instances="false" bsvc:Ignore_Invalid_References="true">
                    <bsvc:Worker_Reference bsvc:Descriptor="string">
                        <bsvc:ID bsvc:type="Employee_ID">' . $workdayId . '</bsvc:ID>
                    </bsvc:Worker_Reference>
                </bsvc:Request_References>
            </bsvc:Get_Worker_Photos_Request>'
        );
    }

    private static function buildSoapXMLRequest(string $request): string
    {
        return '<?xml version=\'1.0\' encoding=\'utf-8\'?>
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/">
<soap-env:Body>
' . $request . '
</soap-env:Body>
</soap-env:Envelope>';
    }

    /**
     * Generates Workday multiple worker photo SOAP request
     *
     * @param array<string> $workdayIds
     *
     * @return string
     */
    public static function getWorkerPhotosXMLRequest(array $workdayIds): string
    {
        $xmlRequest = '<?xml version=\'1.0\' encoding=\'utf-8\'?>
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/">
<soap-env:Body>
    <bsvc:Get_Worker_Photos_Request xmlns:bsvc="urn:com.workday/bsvc" bsvc:version="v39.1">
        <bsvc:Request_References bsvc:Skip_Non_Existing_Instances="false" bsvc:Ignore_Invalid_References="true">';
        foreach ($workdayIds as $workdayId) {
            $xmlRequest .= '<bsvc:Worker_Reference bsvc:Descriptor="string"><bsvc:ID bsvc:type="Employee_ID">';
            $xmlRequest .= $workdayId;
            $xmlRequest .= '</bsvc:ID></bsvc:Worker_Reference>';
        }
        $xmlRequest .= '
        </bsvc:Request_References>
    </bsvc:Get_Worker_Photos_Request>
</soap-env:Body>
</soap-env:Envelope>';

        return $xmlRequest;
    }

    /**
     * Extracts error from XML response
     *
     * @param string $response
     *
     * @return string
     */
    public static function getErrorFromXMLResponse(string $response): string
    {
        try {
            $xml = new SimpleXMLElement($response);
        } catch (Throwable) {
            return __('messages.workday.unavailable_workday_service');
        }

        foreach (self::CONTENT_XML_NAMESPACES as $namespacePrefix => $namespaceValue) {
            $xml->registerXPathNamespace($namespacePrefix, $namespaceValue);
        }

        $fault = $xml->xpath(self::FAULT_CONTENT_XML_XPATH)[0];

        return (string) $fault->faultstring;
    }
}
