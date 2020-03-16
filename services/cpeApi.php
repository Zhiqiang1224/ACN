<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class cpeApi extends MY_Controller
{
    /**
     * cpeApi request url
     */
    protected $url;

    /**
     * cpeApi request build params
     */
    protected $client;
    protected $transactionID;
    protected $profileName;
    protected $mac;
    protected $serial;
    protected $userAuth;
    protected $pwd;
    protected $line1;
    protected $line2;
    protected $firstName;
    protected $lastName;
    protected $phoneNumber;

    /**
     * cpeApi response params
     */
    protected $response; // String containing the content of the stream context
    protected $responseData = []; // Array storing the response data

    /**
     * Initiate the class attributes and the API config params
     * cpeApi constructor.
     */
    public function __construct() {
        parent::__construct();

        // Load the configuration file
        $this->config->load('cpeApi');
        $this->url = $this->config->item('cpeApi')['url'];

        // Load the hsi library
        $this->load->library('hsi_validator');

        // Get the soap client
        $this->client = $this->getSoapClient();
    }

    /**
     * Get the soap client
     * @return SoapClient
     */
    public function getSoapClient(){
        try {
            // Create soap request
            $client = new SoapClient(APPPATH. 'wsdl/hsi_network/CPE.wsdl', array('trace' => 1));

            // Config the url in the wsdl
            $client->__setLocation($this->url);

            //echo "<pre>";var_dump($client->__getFunctions());
            //echo "<pre>";var_dump($client->__getTypes());exit;

            return $client;
        } catch (SoapFault $fault) {
            echo $fault;
        }
    }

    public function getResponseInfo($response){

        $getResponse     = $response;
        $getLastRequest  = $this->client->__getLastRequest();
        $getLastResponse = $this->client->__getLastResponse();


        $response_toReturn = [
            'response'    => $getResponse,
            'requestXml'  => $getLastRequest,
            'receivedXml' => $getLastResponse
        ];

        return $response_toReturn;
    }

    /**
     * Add the device to the server and database
     * @param $data
     * @return mixed
     */
    public function addDevice($data) {

        $params = [
            'TransactionID'    => $data['transactionId'],
            'DeviceID'         => $data['deviceId'],
            'ParameterList'    => [
                ['Parameter' => '_ACN_ProfileName',     'Value'  => $data['profileName']],
                ['Parameter' => '_ACN_DeviceMAC',       'Value'  => $data['mac']],
                ['Parameter' => '_ACN_DeviceSerial',    'Value'  => $data['serial']],
                ['Parameter' => '_ACN_UserID',          'Value'  => $data['userId']],
                ['Parameter' => '_ACN_UserAuthID_1',    'Value'  => $data['userAuth']],
                ['Parameter' => '_ACN_UserPwd_1',       'Value'  => $data['pwd']],
                ['Parameter' => '_ACN_LineEnable_1',    'Value'  => $data['line1']],
                ['Parameter' => '_ACN_LineEnable_2',    'Value'  => $data['line2']],
                ['Parameter' => '_ACN_UserFirstName',   'Value'  => $data['firstName']],
                ['Parameter' => '_ACN_UserLastName',    'Value'  => $data['lastName']],
                ['Parameter' => '_ACN_UserPhoneNum_1',  'Value'  => $data['phoneNumber']]
            ]
        ];

        $response = $this->client->DeviceAdd($params);
        return $this->getResponseInfo($response);
    }

    /**
     * Update the device from the server and database
     * @param $data
     * @return mixed
     */
    public function changeDevice($data) {
        $params = [
            'TransactionID'    => $data['transactionId'],
            'DeviceID'         => $data['deviceId'],
            'ParameterList'    => [
                ['Parameter' => '_ACN_ProfileName',     'Value'  => $data['profileName']],
                ['Parameter' => '_ACN_DeviceMAC',       'Value'  => $data['mac']],
                ['Parameter' => '_ACN_DeviceSerial',    'Value'  => $data['serial']],
                ['Parameter' => '_ACN_UserID',          'Value'  => $data['userId']],
                ['Parameter' => '_ACN_UserAuthID_1',    'Value'  => $data['userAuth']],
                ['Parameter' => '_ACN_UserPwd_1',       'Value'  => $data['pwd']],
                ['Parameter' => '_ACN_LineEnable_1',    'Value'  => $data['line1']],
                ['Parameter' => '_ACN_LineEnable_2',    'Value'  => $data['line2']],
                ['Parameter' => '_ACN_UserFirstName',   'Value'  => $data['firstName']],
                ['Parameter' => '_ACN_UserLastName',    'Value'  => $data['lastName']],
                ['Parameter' => '_ACN_UserPhoneNum_1',  'Value'  => $data['phoneNumber']]
            ]
        ];


        $response = $this->client->DeviceModify($params);
        return $this->getResponseInfo($response);
    }

    /**
     * Get the device by transaction Id and device Id
     * @param $data
     * @return mixed
     */
    public function getDevice($data) {
        $params = [
            'TransactionID'    => $data['transactionId'],
            'DeviceID'         => $data['deviceId']
        ];

        // Request get info of device from server
        $response = $this->client->DeviceRead($params);
        return $this->getResponseInfo($response);
    }

    /**
     * remove the device from the CPE server and database
     * @param $data
     * @return mixed
     */
    public function removeDevice($data) {
        $params = [
            'TransactionID'    => $data['transactionId'],
            'DeviceID'         => $data['deviceId']
        ];

        // Request delete from CPE server
        $response = $this->client->DeviceDelete($params);
        return $this->getResponseInfo($response);
    }

    /**
     * Transaction status event
     * @return mixed
     */
    public function getTransactionStatusEvent(){
       return $this->client->TransactionStatusEvent();
    }

}