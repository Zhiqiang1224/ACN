<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class radiusApi extends MY_Controller
{
    /**
     * user
     */
    public $username;   // user name
    public $password;   // String authorization token
    public $action;     // action

    /**
     * email server request build params
     */

    protected $requestUrl; // String request url

    /**
     * acsApi response params
     */
    protected $response; // String containing the content of the stream context
    protected $responseData = []; // Array storing the response data

    /**
     * __construct
     * Radius server constructor
     * Initiate the class attributes and the API config params
     */
    public function __construct() {
        parent::__construct();

        // Load the configuration url
        $this->config->load('radiusApi');
        $this->requestUrl = $this->config->item('radiusApi')['url'];

        // Load the library
        $this->load->library('hsi_validator');
    }

    /**
     * cUrl http post request
     * @param $data
     * @return bool|string
     */
    protected function curlHttpPost($data){
        $fields_string = http_build_query($data);

        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $this->requestUrl);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST, count($data));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'authorization: Bearer *'
            )
        );
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * @param $data
     * @return false|string
     */
    public function sendReq($data) {
        return $this->curlHttpPost($data);
    }

    /**
     *  Add the usr from the radiusApi server
     * @param $model
     * @return mixed
     */
    public function addToRadius($model){
        $this->action = 'add';

        $data_req = [
            'username'             => $model['SER_USER_NAME'],
            'password'             => $model['SER_PASSWORD'],
            'action'               => $this->action
        ];

        // Get the response status from calling radiusApi server API
        return $this->sendReq($data_req);
    }

    /**
     *  Update the usr from the radiusApi server
     * @param $model
     * @return mixed
     */
    public function changeFromRadius($model){
        $this->action = 'change';

        $data_req = [
            'username'             => $model['SER_USER_NAME'],
            'password'             => $model['SER_PASSWORD'],
            'action'               => $this->action
        ];

        // Get the response status from calling radiusApi server API
        return $this->sendReq($data_req);
    }

    /**
     * Delete the usr from the radiusApi server
     * @param $model
     * @return mixed
     */
    public function removeFromRadius($model){
        $this->action = 'delete';

        $data_req = [
            'username'             => $model['SER_USER_NAME'],
            'password'             => $model['SER_PASSWORD'],
            'action'               => $this->action
        ];

        // Get the response status from calling radiusApi server API
        return $this->sendReq($data_req);
    }

    public function restoreFromRadius($model){
        $this->action = 'restore';

        $data_req = [
            'username'             => $model['SER_USER_NAME'],
            'action'               => $this->action
        ];

        // Get the response status from calling radiusApi server API
        return $this->sendReq($data_req);
    }

    public function suspendFromRadius($model){
        $this->action = 'suspend';

        $data_req = [
            'username'             => $model['SER_USER_NAME'],
            'action'               => $this->action
        ];

        // Get the response status from calling radiusApi server API
        return $this->sendReq($data_req);
    }
}