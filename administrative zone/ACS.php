<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require APPPATH.'third_party/hsi_api/acsApi.php';


class ACS extends MY_Controller
{

    private $trackingId;
    private $status_init = 21;
    private $status_suc  = 22;
    private $status_fail = 23;

    public function __construct() {
        parent::__construct();

        $this->load->model('hsi_network/m_hsi_network','m_hsi_network');
        $this->m_hsi_network->initialise($this->get_mysql_db(), $this->get_oss_db());

        // Load the ACS server tracking model
        $this->load->model('hsi_network/t_acs_server_tracking');
        $this->t_acs_server_tracking->initialise($this->get_mysql_db(), $this->get_oss_db());

        // Load the ACS response tracking model
        $this->load->model('hsi_network/t_acs_response_tracking');
        $this->t_acs_response_tracking->initialise($this->get_mysql_db(), $this->get_oss_db());

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
            $orders = $this->m_hsi_network->getAcsOrdersByRequest(12503435); // Get all acs orders
        }

        if(!$orders){
            die("There is not acs order");
        }

        foreach ($orders as $order){
            $isLocked = $this->m_hsi_network->lock_service(172, 'SYS');  // Lock service

            if($isLocked){
                $this->m_hsi_network->update_dsl_status(97, $this->status_init); // Initial the status

                $this->processOrder($order, $output_action);

                $this->m_hsi_network->unlock_service(172);    // Unlock service
            }
        }
    }

    /**
     * Add action of ACS
     */
    private function add(){
        $this->_doAction('add');
    }

    /**
     * Change action of ACS
     */
    private function change(){
        $this->_doAction('change');
    }

    /**
     * Delete action of ACS
     */
    private function delete(){
        $this->_doAction('delete');
    }

    /**
     * Process the order for different action
     * @param $acsOrder
     * @param $output_action
     */
    private function processOrder($acsOrder, $output_action){

        $data_server = [  // insert into acsApi server tracking table with dsl id
            'dslId'          => 97,  // $acsOrder['DSL_ID']
        ];
        $this->trackingId = $this->t_acs_server_tracking->store($data_server);


        if($output_action == 'add') { // Get the acsApi order to add device
            $data_toCheck = [
                'subScriberCode'  => $acsOrder['SUBSCRIBERCODE'],
                'fullName'        => $acsOrder['FULLNAME'],
                'serviceProvider' => $acsOrder['SERVICEPROVIDER'],
                'macAddress'      => $acsOrder['MACADDRESS'],
                'serialNumber'    => $acsOrder['SERIALNUMBER']
            ];

            // Check the empty value
            $emptyMessage = $this->hsi_validator->checkValueEmpty($data_toCheck);
           if($emptyMessage !== null){
               $responseAcs = $this->errorEmpty($emptyMessage);  //mock the empty value
           } else {
               $responseAcs = $this->activateAcsOrder($acsOrder);
           }
        }

        if($output_action == 'delete') { // Get the acsApi order to remove device
            $data_toCheck = [
                'subScriberCode'  => $acsOrder['SUBSCRIBERCODE'],
                'serialNumber'    => $acsOrder['SERIALNUMBER']
            ];

            // Check the empty value
            $emptyMessage = $this->hsi_validator->checkValueEmpty($data_toCheck);
            if($emptyMessage !== null){
                $responseAcs = $this->errorEmpty($emptyMessage);
            } else {
                $responseAcs = $this->deactivateAcsOrder($acsOrder);
            }
        }

        if($output_action == 'change') { // Get the acsApi order to replace the device
            $data_toCheck = [
                'subScriberCode'  => $acsOrder['SUBSCRIBERCODE'],
                'macAddress'      => $acsOrder['MACADDRESS'],
                'serialNumber'    => $acsOrder['SERIALNUMBER']
            ];

            // Check the empty value
            $emptyMessage = $this->hsi_validator->checkValueEmpty($data_toCheck);
            if($emptyMessage !== null){
                $responseAcs = $this->errorEmpty($emptyMessage);
            } else {
                $responseAcs = $this->updateAcsDevice($acsOrder);
            }
        }

        $this->storeAcsResponse($responseAcs, $acsOrder);    // save the response to the database
    }


    /**
     * Mock error message of empty value
     * @param $emptyMessage
     * @return mixed
     */
    private function errorEmpty($emptyMessage){
        $responseAcs = [];
        http_response_code(404); // Setting the http status code to 404

        $responseAcs['request']   = json_encode(['message' => $emptyMessage]);
        $responseAcs['message']   = ['message' => $emptyMessage];
        $responseAcs['http_Code'] =  http_response_code(); // 404

        return $responseAcs;
    }

    /**
     * Process the response for different action
     * @param $order
     * @param $responseAcs
     */
    private storeAcsResponse($responseAcs, $order =null){

        if($responseAcs){
            if($responseAcs['http_Code'] != 200){
                echo "ACS   fail : {$responseAcs['message']['0']['message']} <br>";
                $this->m_hsi_network->update_dsl_status(97, $this->status_fail);
            } else {
                echo "ACS  ok<br>";
                $this->m_hsi_network->update_dsl_status(97, $this->status_suc);
            }

            $data_response = [
                'trackingId'     => $this->trackingId,
                'sentXml'        => $responseAcs['request'],
                'receivedXml'    => json_encode($responseAcs['message']),
                'code'           => $responseAcs['http_Code'],
            ];

            $result = $this->t_acs_response_tracking->store($data_response);
        }
    }

    /**
     * Activate the Acs order into server
     * @param $order
     * @return array
     */
    private function activateAcsOrder($order){
        $acs = new acsApi();
        /**********************************************************************************************
         *  Adding  subscriber information
         **********************************************************************************************/

        // Check the model number
        $is360 = $this->hsi_validator->getDeviceModelNumber($order['DEVICEMODELNUMBER']);

        //Prepare the request data for adding subscriber
        $data_subscriber = [
            'subscriberCode'    => $order['SUBSCRIBERCODE'],
            'fullName'          => $order['FULLNAME'],
            'serviceProvider'   => $order['SERVICEPROVIDER'],
            'userName'          =>($is360 == true)?  $order['USERNAME'] : '',   // Should be unique
            'password'          =>($is360 == true)?  $order['PASSWORD'] : '',   // At least 6 characters
            'emailAddress'      =>($is360 == true)?  $order['EMAILADDRESS'] : ''
        ];

        if(!$is360){
            $responseSub = $acs->addSubscriber505($data_subscriber);  // Add subscribe of model 505/800
        } else {
            $responseSub = $acs->addSubscriber360($data_subscriber);  // Add subscribe of model 360
        }

        // Return the response if it has failure
        if($responseSub['http_Code'] !== 200){
            return $responseSub;
        }

        // store into acsApi response tracking table without error
        $this->storeAcsResponse($responseSub);

        /************************************************************************************************
         *  Adding the device information with subscriber
         ************************************************************************************************/

        // Check the device type
        $isCable = $this->hsi_validator->getDeviceType($order['SERVICEPROVIDER']);

        // Prepare the request data for adding device
        $data_device = [
            'subscriberCode'   => $order['SUBSCRIBERCODE'],
            'macAddress'       => $order['MACADDRESS'],     // 6 unique digit hex number in server
            'serialNumber'     => $order['SERIALNUMBER'],
            'userName'         =>($isCable == false)?  $order['USERNAME'] : '', //'zhiquiang.yang@acninc.com',
            'password'         =>($isCable == false)?  $order['PASSWORD'] : '', //radiusApi password
        ];

        $acs = new acsApi();

        if($isCable){
            $responseAddDevice = $acs->addDeviceCable($data_device);  // Adding the Device with cable
        } else {
            $responseAddDevice = $acs->addDeviceDSL($data_device);    // Adding the Device with DSL
        }

        return $responseAddDevice;
    }

    /**
     * Deactivate an order
     * @param $order
     * @return array
     */
    private function deactivateAcsOrder($order){
        // Prepare the data for deactivate request
        $params = [
            'serialNumber'      => $order['SERIALNUMBER'],
            'subscriberCode'    => $order['SUBSCRIBERCODE']
        ];

        $acs = new acsApi();
        // Remove the device
        $responseDevice = $acs->deleteDeviceCable($params['serialNumber']);

        $responseDevice['request'] = json_encode(['serialNuber' => $params['serialNumber']]);
        if($responseDevice['http_Code'] !== 200){
            return $responseDevice;
        }

        $this->storeAcsResponse($responseDevice);  // store into acsApi response tracking table

        // Remove  the subscriber
        $responseSubscriber = $acs->deleteSubscriberByCode($params['subscriberCode']);
        $responseSubscriber['request'] = json_encode(['serialNuber' => $params['subscriberCode']]);

        return $responseSubscriber;
    }

    /**
     * Add the device for replacement
     * @param $order
     * @return array
     */
    private function addReplaceDevice($order){
        // Check the device type
        $isCable = $this->hsi_validator->getDeviceType($order['SERVICEPROVIDER']);

        // Prepare the data for request
        $data_device = [
            'serialNumber'      => $order['SERIALNUMBER'],
            'macAddress'        => $order['MACADDRESS'],
            'subscriberCode'    => $order['SUBSCRIBERCODE'],
            'userName'          =>($isCable ==  false)?  $order['USERNAME'] : '', //zhiquiang.yang@acninc.com,
            'password'          =>($isCable ==  false)?  $order['PASSWORD'] : ''
        ];

        // Check the device type
        $acs = new acsApi();
        if($isCable){ // Cable
            $responseAcs = $acs->replaceDeviceCable($data_device); // Add new device for replacement
        } else { // DSL
            $responseAcs = $acs->replaceDeviceDSL($data_device);
        }

        return $responseAcs;
    }

    /**
     * Update the credential information
     * @param $order
     * @return array|false|string
     */
    private function updateAcsCredential($order){
        $acs = new acsApi();
        // Get the PPP credentials in ACS by serial number
        $responseCredential = $acs->getCredentialBySerialNumber($order['SERIALNUMBER']);
        if($responseCredential['http_Code'] !== 200){
            return $responseCredential;
        }

        // Get device id from credential
        $deviceId = $responseCredential['message'][0]['fields']['deviceId'];

        // Get device info from api
        $responseGetDeviceInfo = $acs->getDeviceInfoById($deviceId);

        if($responseGetDeviceInfo['http_Code'] !== 200){
            return $responseGetDeviceInfo;
        }

        $device = $responseGetDeviceInfo['message'];  // Get device message body

        $data_update = [  // Prepare the data
            'revision'          => $device['revision'],
            'deviceId'          => $deviceId,
            'subscriberCode'    => $device['subscriberCode'],
            'macAddress'        => $device['oui'],
            'serialNumber'      => $device['sn'],
            'userName'          => $order['USERNAME'], // user info get from order
            'password'          => $order['PASSWORD'],
        ];

        return $acs->updateCredentialInfo($data_update);
    }

    /**
     * Replace a device of the subscriber
     * @param $order
     * @return array
     */
    private function updateAcsDevice($order){
        $acs = new acsApi();
        // Get the user information
        $responseSearchCode = $acs->searchUsrBySubscriberCode($order['SUBSCRIBERCODE']);

        if($responseSearchCode['http_Code'] !== 200){
            $responseSearchCode['request'] = json_encode(['subscriber' => $order['SUBSCRIBERCODE']]);  // Convert value to json format
            return $responseSearchCode;
        }

        $this->storeAcsResponse($responseSearchCode);

        // Get the old device information
        $oldDevice = $responseSearchCode['message']['subscriptions'][0];

        // Adding the new device for replacing
        $responseAdd = $this->addReplaceDevice($order);
        if($responseAdd['http_Code'] !== 200){
            return $responseAdd;
        }

        $this->storeAcsResponse($responseAdd);  // store into acsApi response tracking table

        $deviceId =  $responseAdd['message']['deviceId']; // Get the new device Id

        // Get the new device information by device Id
        $responseGetNewDeviceInfo  =  $acs->getDeviceInfoById($deviceId);
        if($responseGetNewDeviceInfo['http_Code'] !== 200){
            return $responseGetNewDeviceInfo;
        }

        // store into acsApi response tracking table
        $this->storeAcsResponse($responseGetNewDeviceInfo);

        // Get new device information
        $newDevice = $responseGetNewDeviceInfo['message'];

        $data_update = [ // Prepare the parameters to update
            'newDeviceId'        => $deviceId,
            'newRevision'        => $newDevice['revision'],
            'newSerialNumber'    => $newDevice['sn'],
            'newMacAddress'      => $newDevice['oui'],
            'oldSerialNumber'    => $oldDevice['sn'],
            'oldMacAddress'      => $oldDevice['oui'],
            'userName'           => $order['USERNAME'],
            'passWord'           => $order['PASSWORD'],
        ];

        $responseUpdateDevice = $acs->updateDevice($data_update);  // Call the Api and get response

        return  $responseUpdateDevice;
    }
}