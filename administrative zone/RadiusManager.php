<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH.'third_party/hsi_api/radiusApi.php';


class RadiusManager extends MY_Controller
{
    public function __construct() {
        parent::__construct();

        $this->load->model('hsi_network/m_hsi_network','m_hsi_network');
        $this->m_hsi_network->initialise($this->get_mysql_db(), $this->get_oss_db());

        // Load the email server tracking model
        $this->load->model('hsi_network/t_radius_server_tracking');
        $this->t_radius_server_tracking->initialise($this->get_mysql_db(),$this->get_oss_db());

        // Load the library
        $this->load->library('hsi_validator');
    }

    /**
     * Get the email server request for different action
     * @param $request
     */
    public function getRadiusRequests($request){
        $radiusOrders = $this->m_hsi_network->getRadiusOrdersByRequest($request['PROVREQUESTID']);
        if ($radiusOrders) {
            foreach ($radiusOrders as $radiusOrder) {
                $this->processRadiusAction($radiusOrder);
            }
        }
    }

    /**
     * Process the order for different action
     * @param $radiusRequest
     */
    private function processRadiusAction($radiusOrder){
        if($radiusOrder['ACTIONCODE'] == 'add') {
            $responseRadius = $this->addRadius($radiusOrder);
        }

        if($radiusOrder['ACTIONCODE'] == 'change') {
            $responseRadius = $this->changeRadius($radiusOrder);
        }

        if($radiusOrder['ACTIONCODE'] == 'delete') {
            $responseRadius = $this->deleteRadiusServer($radiusOrder);
        }

        if($radiusOrder['ACTIONCODE'] == 'restore') {
            $responseRadius = $this->restoreRadius($radiusOrder);
        }

        if($radiusOrder['ACTIONCODE'] == 'suspend') {
            $responseRadius = $this->suspectRadius($radiusOrder);
        }

        // Save the message to cpeApi message table
        $this->processRadiusResponse($radiusOrder, $responseRadius);
    }

    /**
     * Hinder the response to different action
     * @param $order
     * @param $response
     */
    private function processRadiusResponse($order, $response){
        if($response){
            if(!strpos($response, 'OK')){
                die("Failure for {$order['USERNAME']} ");
                //$this->m_hsi_network->updateRequestStatus($order['PROVREQUESTID'], 4);
            } else {
                //$this->m_hsi_network->updateRequestStatus($order['PROVREQUESTID'], 3);
            }
            //$this->m_hsi_network->saveRadiusMessage($order ,$response);
        }
    }

    /**
     * Add to radiusApi server for the user
     * @param $order
     * @return mixed
     */
    private function addRadius($order){
        // Prepare the data for the email server
        $params = [
            'userName'    => $order['USERNAME'],
            'passWord'    => $order['PASSWORD'],
        ];

        // Call email server API to activate the server
        $radius = new radiusApi();
        return $radius->addToRadius($params);
    }

    /**
     *  Delete from radiusApi server
     * @param $order
     * @return mixed
     */
    private function deleteRadiusServer($order){
        // Prepare the data for the email server
        $params = [
            'userName'    => $order['USERNAME'],
            'passWord'    => $order['PASSWORD'],
        ];

        $radius = new radiusApi();
        return $radius->removeFromRadius($params);
    }

    /**
     * Change from radiusApi server
     * @param $order
     * @return mixed
     */
    private function changeRadius($order){
        // Prepare the data for the email server
        $params = [
            'userName'    => $order['USERNAME'],
            'passWord'    => $order['PASSWORD'],
        ];

        $radius = new radiusApi();
        return $radius->changeFromRadius($params);
    }

    /**
     * Restore from radiusApi server
     * @param $order
     * @return mixed
     */
    private function restoreRadius($order){
        // Prepare the data for the email server
        $params = [
            'userName'    => $order['USERNAME'],
        ];

        $radius = new radiusApi();
        return $radius->restoreFromRadius($params);
    }

    /**
     * Suspect from radiusApi server
     * @param $order
     * @return mixed
     */
    private function suspectRadius($order){
        // Prepare the data for the email server
        $params = [
            'userName'    => $order['USERNAME'],
        ];

        $radius = new radiusApi();
        return $radius->restoreFromRadius($params);
    }

}