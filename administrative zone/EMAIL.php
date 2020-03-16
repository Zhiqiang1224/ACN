<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH.'third_party/hsi_api/EmailServer.php';


class EMAIL extends MY_Controller
{
    private $status_init = 21;
    private $status_suc  = 22;
    private $status_fail = 23;

    public function __construct() {
        parent::__construct();

        $this->load->model('hsi_network/m_hsi_network','m_hsi_network');
        $this->m_hsi_network->initialise($this->get_mysql_db(), $this->get_oss_db());

        // Load the email server tracking model
        $this->load->model('hsi_network/t_email_server_tracking');
        $this->t_email_server_tracking->initialise($this->get_mysql_db(),$this->get_oss_db());

        // Load the library
        $this->load->library('hsi_validator');
    }

    /**
     * Add to Email server
     */
    private function add(){
        $this->_doAction('add');
    }

    /**
     * Change  to Email server
     */
    private function change(){
        $this->_doAction('change');
    }

    /**
     * Delete to Email server
     */
    private function delete(){
        $this->_doAction('delete');
    }

    /**
     * Process the email order with different action
     * @param $output_action
     */
    public function _doAction($output_action){
        $list_action = array('add', 'change', 'delete');
        if (!in_array($output_action, $list_action)) {
            die('Invalid action');
        }

        $orders =[];
        $emailServer = new emailServer();

        if($output_action == 'add'){
            $orders = $this->m_hsi_network->getAddEmailParameters();   // Get all email orders
        }

        if(!$orders){
            die("There is not email order");
        }

        foreach ($orders as  &$order){
            $isLocked = $this->m_hsi_network->lock_service($order['SER_ID'], 'SYS');  // Lock service

            if($isLocked){
                $this->m_hsi_network->update_dsl_status($order['DSL_ID'], $this->status_init); // Initial the status

                $order['action'] = $output_action;  // Add action name for each order
                $order['USERNAME'] = $this->hsi_validator->getNameBeforeDomain($order['USERNAME']);

                $responseEmail = $emailServer->toEmailServer($order);    // Send request to the server
                $this->saveEmailResponse($order, $responseEmail);        // save to the database

                $this->m_hsi_network->unlock_service($order['SER_ID']);  // Unlock service
            }
        }
    }

    /**
     * Save the request and response to db
     * @param $emailOrder
     * @param $responseEmail
     */
    private function saveEmailResponse($emailOrder, $responseEmail){
        if($responseEmail){
            if(!strpos($responseEmail, 'OK')){
                echo "Email  error  <br> ";
                $this->m_hsi_network->update_dsl_status($emailOrder['DSL_ID'], $this->status_fail);
            } else {
                echo "Email  OK<br>";
                $this->m_hsi_network->update_dsl_status($emailOrder['DSL_ID'], $this->status_suc);
            }

            $data_toInsert = [
                'dslId'          => $emailOrder['DSL_ID'],
                'sentXml'        => json_encode($emailOrder),
                'receivedXml'    => $responseEmail,
            ];

            // Save into the database
            $result = $this->t_email_server_tracking->store($data_toInsert);
        }
    }

}