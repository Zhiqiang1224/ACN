<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');



class acsApi extends MY_Controller
{
    /**
     * acsApi credential
     */
    protected $userName;
    protected $password;
    protected $userPass;

    /**
     * acsApi url action
     */

    protected $baseUrl;                   // String main url
    protected $schemaUrl;                 // show the schema url
    protected $changeCredentialUrl;       // change the subscriber credential information
    protected $addSubscriberUrl;          // Add subscriber sub url
    protected $addDeviceUrl;              // Add device sub url
    protected $replaceDeviceUrl;          // Replace device url
    protected $removeSubscriberUrl;       // Remove subscribe sub url
    protected $removeDeviceUrl;           // Remove device sub url
    protected $searchSerialNumberUrl;     // Search sub url
    protected $searchSubscriberCodeUrl;   // Search sub url
    protected $requestUrl;                // The request url

    /**
     * acsApi server parameters
     */
    protected $serialNumber;
    protected $macAddress;
    protected $subscriberCode;            // Subscriber code
    protected $emailAddress;
    protected $serviceProvider;
    protected $FullName;
    protected $domain;
    protected $radiusPassword;
    protected $cable;

    /**
     * acsApi response params
     */
    protected $response; // String containing the content of the stream context
    protected $responseData = []; // Array storing the response data

    /**
     * __construct
     * ACS constructor
     * Initiate the class attributes and the API config params
     */
    public function __construct() {
        parent::__construct();

        // Load the ACS service
        $this->load->model('hsi_network/service_acs');
        $this->service_acs->initialise($this->get_mysql_db(),$this->get_oss_db());

        // Load the configuration file
        $this->config->load('acsApi');

        // Assign the config values to parameters
        $this->userName                  = $this->config->item('acsApi')['usr'];
        $this->password                  = $this->config->item('acsApi')['pwd'];
        $this->userPass                  = $this->userName . ':' . $this->password;

        // Url depends on the action
        $this->baseUrl                   = $this->config->item('acsApi')['baseUrl'];
        $this->schemaUrl                 = $this->config->item('acsApi')['schemaUrl'];
        $this->changeCredentialUrl       = $this->config->item('acsApi')['changeCredentialUrl'];
        $this->addSubscriberUrl          = $this->config->item('acsApi')['addSubscriberUrl'];
        $this->addDeviceUrl              = $this->config->item('acsApi')['addDeviceUrl'];
        $this->replaceDeviceUrl          = $this->config->item('acsApi')['replaceDeviceUrl'];
        $this->removeSubscriberUrl       = $this->config->item('acsApi')['removeSubscriberUrl'];
        $this->removeDeviceUrl           = $this->config->item('acsApi')['removeDeviceUrl'];
        $this->searchSerialNumberUrl     = $this->config->item('acsApi')['searchSerialNumberUrl'];
        $this->searchSubscriberCodeUrl   = $this->config->item('acsApi')['searchSubscriberCodeUrl'];

        // Load the library
        $this->load->library('hsi_validator');
    }

    /**
     * cUrl http get request
     * @param $url
     *  @param $data
     * @return array
     */
    protected function curlHttpGet($url, $data){

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . base64_encode($this->userPass),
                "content-type: application/json; charset=utf-8",
                "cache-control: no-cache"
            ),
        ));

        return $this->responseInfo($ch, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return array
     */
    protected function curlHttpPost($url, $data){
        $fields_string = $data;

        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Basic " . base64_encode($this->userPass),
                'Content-Type: application/json',
                'Accept: *.*',
                'Accept-Language: en-us',
                "cache-control: no-cache"
            )
        );

        return $this->responseInfo($ch, $data);
    }

    /**
     * cUrl http delete request
     * @param $data
     * @return mixed
     */
    protected function curlHttpDelete($data){
        // Encode the url
        $query = urlencode($data);
        // Combination of the sub request query
        $url = $this->requestUrl . $query;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Basic " . base64_encode($this->userPass),
            'Content-Type: application/json',
            'Accept: *.*',
            'Accept-Language: en-us',
        ));

        return $this->responseInfo($ch, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return array
     */
    protected function curlHttpUpdate($url, $data){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Basic " . base64_encode($this->userPass),
                'Content-Type: application/json',
                'Accept: *.*',
                'Accept-Language: en-us',
            )
        );

        return $this->responseInfo($ch, $data);
    }

    /**
     * Parcel the response information
     * @param $ch
     * @param $data
     * @return array
     */
    protected function responseInfo($ch, $data = NULL){
        $this->responseData['request'] = $data;  // request body
        $this->responseData['message'] = json_decode(curl_exec($ch),true);
        $this->responseData['http_Code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $this->responseData;
    }

    /**
     * @param $url
     * @return array
     */
    public function sendGetReq($url, $data) {
        return $this->curlHttpGet($url, $data);
    }

    /**
     * @param $data
     * @return array
     */
    public function sendPostReq($data) {
        return $this->curlHttpPost($this->requestUrl, $data);
    }

    /**
     * @param $data
     * @return array
     */
    public function sendUpdateReq($data) {
        return $this->curlHttpUpdate($this->requestUrl, $data);
    }

    /**
     * @param $data
     * @return array
     */
    public function sendDeleteReq($data) {
        return $this->curlHttpDelete($data);
    }

    /**
     * Get the schema of acsApi server
     * @return array
     */
    public function getAcsSchema(){
        $url = $this->schemaUrl;
        return $this->sendGetReq($url);
    }

    /**
     *  Find the user by serial number
     * @param $sn
     * @return mixed
     */
    public function searchDeviceBySerialNumber($sn){
        $url = $this->searchSerialNumberUrl . $sn;
        return $this->sendGetReq($url, $sn);
    }

    /**
     * Find the user by subscriber code
     * @param $code
     * @return mixed
     */
    public function searchUsrBySubscriberCode($code){
        $url = $this->searchSubscriberCodeUrl . $code;
        return $this->sendGetReq($url, $code);
    }

    /**
     * Delete the device by serial number
     * @param $sn
     * @return array
     */
    public function deleteDeviceCable($sn){
        $this->requestUrl = $this->removeDeviceUrl;
        return $this->sendDeleteReq($sn);
    }

    /**
     * Delete subscriber by the subscriber code
     * @param $code
     * @return array
     */
    public function deleteSubscriberByCode($code){
        $this->requestUrl = $this->removeSubscriberUrl;
        return $this->sendDeleteReq($code);
    }

    /**
     * Get device info by device id
     * @param $deviceId
     * @return array
     */
    public function getDeviceInfoById($deviceId){
        $url = $this->replaceDeviceUrl."/{$deviceId}/data";
        return $this->sendGetReq($url, $deviceId);
    }

    /**
     * Get the credential by serial number
     * @param $sn
     * @return array
     */
    public function getCredentialBySerialNumber($sn){
        $url = $this->changeCredentialUrl;
        return $this->curlHttpPost($url, $sn);
    }

    public function getServicesBySerialNumber($deviceId){
        $url = $this->baseUrl ."/prime-home/api/v1/devices/{$deviceId}/actions";
        return $this->sendGetReq($url, $deviceId);
    }

    /**
     * Subscriber the user info with model 360 to the server (default)
     * @param $data
     * @return array
     */
    public function addSubscriber360($data){

        // Prepare the parameters for input
        $data_req = [
            'subscriberCode'    => $data['subscriberCode'],
            'userName'          => $data['userName'],   // Should be unique
            'password'          => $data['password'],   // At least 6 characters
            'fullName'          => $data['fullName'],
            'emailAddress'      => $data['emailAddress'],
            'serviceProvider'   => $data['serviceProvider']
        ];

        // Get the link of request
        $this->requestUrl = $this->addSubscriberUrl;

        // Get the request body
        $requestParams = $this->service_acs->requestForAddSubscriber360($data_req);

        // Call API and get response
        return $this->sendPostReq($requestParams);
    }


    /**
     * Subscriber the user info with model 505 to the server
     * @param $data
     * @return array
     */
    public function addSubscriber505($data){

        // Prepare the parameters for input
        $data_req = [
            'subscriberCode'    => $data['subscriberCode'],
            'fullName'          => $data['fullName'],
            'serviceProvider'   => $data['serviceProvider']
        ];


        // Get the link of request
        $this->requestUrl = $this->addSubscriberUrl;

        // Get the request body
        $requestParams = $this->service_acs->requestForAddSubscriber505($data_req);

        //Call API and get response
        return $this->sendPostReq($requestParams);

    }

    /**
     * Add DSL device to the server
     * @param $data
     * @return array
     */
    public function addDeviceDSL($data){

        // Prepare the parameters for input
        $data_req = [
            'subscriberCode'   => $data['subscriberCode'],
            'macAddress'       => $data['macAddress'],      // 6 unique digit hex number in server
            'serialNumber'     => $data['serialNumber'],    // $data['serialNumber'],
            'userName'         => $data['userName'],        // zhiquiang.yang@acninc.com
            'password'         => $data['password'],        // radiusApi password
        ];

        // Get the link of request
        $this->requestUrl = $this->addDeviceUrl;

        // Get the request body
        $requestParams = $this->service_acs->requestForAddDeviceDSL($data_req);

        // Call API and get response
        return $this->sendPostReq($requestParams);

    }

    /**
     * Add cable device to the server
     * @param $data
     * @return array
     */
    public function addDeviceCable($data){

        // Prepare the parameters for input
        $data_req = [
            'subscriberCode'   => $data['subscriberCode'],
            'serialNumber'     => $data['serialNumber'],
            'macAddress'       => $data['macAddress'],    // 6 unique digit hex number in server
        ];

        // Get the link of request
        $this->requestUrl = $this->addDeviceUrl;

        // Get the request body
        $requestParams = $this->service_acs->requestForAddDeviceCable($data_req);

        // Call API and get response
        return $this->sendPostReq($requestParams);

    }

    /**
     * Replace cable device to the server
     * @param $data
     * @return array
     */
    public function replaceDeviceCable($data){

        // Prepare the parameters for input
        $data_req = [
            'subscriberCode'   => $data['subscriberCode'],
            'macAddress'       => $data['macAddress'],    // 6 unique digit hex number in server
            'serialNumber'     => $data['serialNumber']
        ];

        // Get the link of request
        $this->requestUrl = $this->replaceDeviceUrl;

        // Get the request body
        $requestParams = $this->service_acs->requestForReplaceDeviceCable($data_req);

        // Call API and get response
        return $this->sendPostReq($requestParams);
    }

    /**
     * Replace cable device to the server
     * @param $data
     * @return array
     */
    public function replaceDeviceDSL($data){

        // Prepare the parameters for input
        $data_req = [
            //'subscriberCode'  => $data['subscriberCode'],
            'macAddress'       => $data['macAddress'],     // 6 unique digit hex number in server
            'serialNumber'     => $data['serialNumber'],
            'userName'         => $data['userName'],       //'zhiquiang.yang@acninc.com',
            'password'         => $data['password'],       //radiusApi password
        ];

        // Get the link of request
        $this->requestUrl = $this->replaceDeviceUrl;

        // Get the request body
        $requestParams = $this->service_acs->requestForReplaceDeviceDSL($data_req);

        // Call API and get response
        return $this->sendPostReq($requestParams);

    }

    /**
     * Update the device with same device Id
     * @param $data
     * @return array
     */
    public function updateDevice($data){

        // Prepare the parameters for replacing
        $data_req = [
            'revision'          => $data['newRevision'],
            'deviceId'          => $data['newDeviceId'],
            'newSerialNumber'   => $data['newSerialNumber'],
            'newMacAddress'     => $data['newMacAddress'],
            'oldSerialNumber'   => $data['oldSerialNumber'],
            'oldMacAddress'     => $data['oldMacAddress'],
            'userName'          => $data['userName'],
            'passWord'          => $data['passWord'],
        ];

        // Get the link of request
        $this->requestUrl = $this->replaceDeviceUrl."/{$data['newDeviceId']}/data";

        // Get the update request body
        $requestParams = $this->service_acs->requestUpdateDevice($data_req);

        // Call update API and get response
        return $this->sendUpdateReq($requestParams);
    }


    /**
     * Update the credential information
     * @param $deviceId
     * @param $data
     * @return array
     */
    public function updateCredentialInfo($data){

        $data_req = [
            'revision'          => $data['revision'],
            'deviceId'          => $data['deviceId'],
            'subscriberCode'    => $data['subscriberCode'],
            'macAddress'        => $data['macAddress'],
            'serialNumber'      => $data['serialNumber'],
            'userName'          => $data['userName'],
            'passWord'          => $data['password'],
        ];

        // Get the link of request
        $this->requestUrl = $this->replaceDeviceUrl."/{$data['deviceId']}/data";

        // Get the update request body
        $requestParams = $this->service_acs->requestUpdateCredential($data_req);

        return $this->sendUpdateReq($requestParams);
    }
}