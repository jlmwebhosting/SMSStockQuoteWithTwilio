<?php
namespace PHPMaster;

class StockService
{
    public static function GetQuote($symbol) {
        $client = new \SoapClient('http://www.webservicex.net/stockquote.asmx?WSDL');

        $params = array(
            'symbol' => $symbol,

        );
        $response = $client->__soapCall('GetQuote', array($params));
        $quotes = simplexml_load_string($response->GetQuoteResult);
        return $quotes->Stock[0];
    }
}