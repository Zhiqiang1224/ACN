<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class emailServer extends MY_Controller
{
    private static $instance = null;
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
     * Email server constructor
     * Initiate the class attributes and the API config params
     */
    public function __construct() {
        parent::__construct();

        // Load the configuration url
        $this->config->load('emailServer');
        $this->requestUrl = $this->config->item('email')['url'];

        // Load the library
        $this->load->library('hsi_validator');
    }

    /**
     * Singleton instance
     * Create object once
     * @return emailServer|null
     */
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    /**
     * @param $url
     * @param $data
     * @return mixed
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
     * Send the request to server with action name
     * @param $params
     * @return false|string
     */
    public function toEmailServer($params){
        // Prepare the request parameters
        $data_req = [
            'username'             => $params['USERNAME'],
            'password'             => $params['PASSWORD'],
            'action'               => $params['action']
        ];

        return $this->sendReq($data_req);
    }
}