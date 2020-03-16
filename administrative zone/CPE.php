<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH.'third_party/hsi_api/cpeApi.php';


class CPE extends MY_Controller
{
    private $status_init = 21;
    private $status_suc  = 22;
    private $status_fail = 23;

    public function __construct() {
        parent::__construct();

        // Load the hsi network model
        $this->load->model('hsi_network/m_hsi_network','m_hsi_network');
        $this->m_hsi_network->initialise($this->get_mysql_db(), $this->get_oss_db());

        // Load the cpeApi server tracking model
        $this->load->model('hsi_network/t_cpe_server_tracking');
        $this->t_cpe_server_tracking->initialise($this->get_mysql_db(), $this->get_oss_db());

        // Load the library
        $this->load->library('hsi_validator');
    }


    public function _doAction($output_action){
        $list_action = array('add', 'change', 'delete');
        if (!in_array($output_action, $list_action)) {
            die('Invalid action');
        }

        $orders =[];
        if($output_action == 'add'){
            $orders = $this->m_hsi_network->getCpeOrdersByRequest(12463045);  // Get all cpe orders
        }

        if(!$orders){
            die("There is not email order");
        }

        foreach ($orders as $order){
            $isLocked = $this->m_hsi_network->lock_service($order['SER_ID'], 'SYS');  // Lock service

            if($isLocked){
                $this->m_hsi_network->update_dsl_status($order['DSL_ID'], $this->status_init); // Initial the status

                $responseCpe = $this->activateCpe($order);               // Send request to the server
                $this->storeCpeResponse($order, $responseCpe);           // save to the database

                $this->m_hsi_network->unlock_service($order['SER_ID']);  // Unlock service
            }
        }
    }

    /**
     * Add action of CPE
     */
    private function add(){
        $this->_doAction('add');
    }

    /**
     * Change action of CPE
     */
    private function change(){
        $this->_doAction('change');
    }

    /**
     * Delete action of CPE
     */
    private function delete(){
        $this->_doAction('delete');
    }

    /**
     * Hinder the response to different action
     * @param $order
     * @param $response
     */
    private function storeCpeResponse($order, $responseCpe){
        if($responseCpe){
            $response = $responseCpe['response'];
            if($response->ErrorCode != 0){
                echo "CPE  fail: {$response->ErrorText}<br>";
                $this->m_hsi_network->update_dsl_status($order['DSL_ID'], $this->status_fail);
            } else {
                echo "CPE  OK <br>";
                $this->m_hsi_network->update_dsl_status($order['DSL_ID'], $this->status_suc);
            }

            $getResponseCode = $responseCpe['response'];
            $data_toInsert = [
                'dslId'          => $order['DSL_ID'],
                'sentXml'        => $responseCpe['requestXml'],
                'receivedXml'    => $responseCpe['receivedXml'],
                'code'           => $getResponseCode->ErrorCode,
                'message'        => $getResponseCode->ErrorText,
            ];

            // Save into the database
            $result = $this->t_cpe_server_tracking->store($data_toInsert);
        }
    }

    /**
     * Activate the server for the user
     * @param $order
     * @return mixed
     */
    private function activateCpe($order){
        $cpe = new cpeApi();

/**********************************  Remove the exist device  ***************************************
        if(!empty($order['UNIQUEDEVICEID'])){
            $params_toGet = [
                'transactionId'    => $this->hsi_validator->getTransactionId(),//$order['PON'] . '0', // Add the zero to PON for
                'deviceId'         => $order['UNIQUEDEVICEID']
            ];

            // Check the device exist in the server
            $responseGetCpe= $cpeApi->getDevice($params_toGet);
            if($responseGetCpe->ErrorCode === 0){
                $params_toDelete = [
                    'transactionId'    => $this->hsi_validator->getTransactionId(),//$order['PON'] . '00', // Add the zero to PON for
                    'deviceId'         => $order['UNIQUEDEVICEID']
                ];

                // Remove the exist device
                $responseRemoveCpe= $cpeApi->removeDevice($params_toDelete);
                if($responseRemoveCpe->ErrorCode !== 0){
                    return $responseRemoveCpe;
                }
            }
        }
****************************************************************************************************/

        $params = [
            'transactionId' => $this->hsi_validator->getTransactionId(),//$order['PON'],
            'profileName'   => $order['CPEPROFILENAME'],
            'deviceId'      => $order['UNIQUEDEVICEID'],
            'mac'           => $order['MACADDRESS'],
            'serial'        => $order['SERIALNUMBER'],
            'userId'        => array_key_exists('CPEORDID', $order)?                       $order['CPEORDID']:NULL,
            'userAuth'      => array_key_exists('SIPAUTHENTICATIONUSERNAME1', $order)?     $order['SIPAUTHENTICATIONUSERNAME1']:NULL,
            'pwd'           => array_key_exists('SIPAUTHENTICATIONPASSWORD1', $order)?     $order['SIPAUTHENTICATIONPASSWORD1']:NULL,
            'firstName'     => array_key_exists('FIRSTNAME',    $order)?                   $order['FIRSTNAME']:NULL,
            'lastName'      => array_key_exists('LASTNAME',     $order)?                   $order['LASTNAME']:NULL,
            'phoneNumber'   => array_key_exists('PHONENUMBER1', $order)?                   $order['PHONENUMBER1']:NULL,
            'line1'         => 1,
            'line2'         => 0
        ];


        return $cpe->addDevice($params);
    }

    /**
     * Activate the server for the user
     * @param $order
     * @return mixed
     */
    private function deactivateCpe($order){
        $params = [
            'transactionId' => $this->hsi_validator->getTransactionId(),//$order['PON'],
            'deviceId'      => $order['UNIQUEDEVICEID']
        ];

        $cpe = new cpeApi();
        return $cpe->removeDevice($params);
    }

    /**
     * Update the server for the user
     * @param $order
     * @return mixed
     */
    private function updateCpe($order){
        $params = [
            'transactionId' => $order['PON'],
            'profileName'   => $order['CPEPROFILENAME'],
            'deviceId'      => $order['UNIQUEDEVICEID'],
            'mac'           => $order['MACADDRESS'],
            'serial'        => $order['SERIALNUMBER'],
            'userId'        => array_key_exists('CPEORDID', $order)?                       $order['CPEORDID']:NULL,
            'userAuth'      => array_key_exists('SIPAUTHENTICATIONUSERNAME1', $order)?     $order['SIPAUTHENTICATIONUSERNAME1']:NULL,
            'pwd'           => array_key_exists('SIPAUTHENTICATIONPASSWORD1', $order)?     $order['SIPAUTHENTICATIONPASSWORD1']:NULL,
            'firstName'     => array_key_exists('FIRSTNAME',    $order)?                   $order['FIRSTNAME']:NULL,
            'lastName'      => array_key_exists('LASTNAME',     $order)?                   $order['LASTNAME']:NULL,
            'phoneNumber'   => array_key_exists('PHONENUMBER1', $order)?                   $order['PHONENUMBER1']:NULL,
            'line1'         => 1,
            'line2'         => 0
        ];

        $cpe = new cpeApi();
        return $cpe->changeDevice($params);
    }
}