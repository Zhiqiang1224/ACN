<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class t_cpe_server_tracking extends CI_Model
{
    private $pwt_db;
    private $oss_db;

    /**
     * __construct
     * CPE constructor
     * Initiate the class attributes and the API config params
     */
    public function __construct() {
        parent::__construct();
    }

    public function initialise($pwt_db, $oss_db) {
        $this->pwt_db = $pwt_db;
        $this->oss_db = $oss_db;
    }


    /**
     * Store the data into table
     * @param $data
     * @return mixed
     */
    public function store($data){

        $formData = array(
             'DSL_ID'                => $data['dslId'],
             'TRCK_SENT_XML'         => $data['sentXml'],
             'TRCK_RECEIVED_XML'     => $data['receivedXml'],
             'TRCK_CODE'             => $data['code'],
             'TRCK_MESSAGE'          => $data['message'],
             'TRCK_CREATE_DATE'      => date('Y-m-d'),
        );

        $this->pwt_db->insert('T_CPE_SERVER_TRACKING', $formData);
        return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }

    /**
     * Update the data in the table
     * @param $data
     * @param $id
     * @return bool
     */
    public function update($data, $id){
        $formData = array(
            'DSL_ID'             => $data['dslId'],
            'PROFILE_NAME'       => $data['profileName'],
            'TRANSACTION_ID'     => $data['transactionId'],
            'DEVICE_ID'          => $data['deviceId'],
            'DEVICE_MAC'         => $data['mac'],
            'DEVICE_SER'         => $data['serial'],
            'USR_ID'             => $data['userId'],
            'USR_AUTH_ID'        => $data['userAuth'],
            'USR_PWD'            => $data['pwd'],
            'LINE_ENABLE_1'      => $data['line1'],
            'LINE_ENABLE_2'      => $data['line2'],
            'USR_FIRST_NAME'     => $data['firstName'],
            'USR_LAST_NAME'      => $data['lastName'],
            'USR_PHONE'          => $data['phoneNumber'],
            'DATE'               => date('Y-m-d')
        );

        $this->pwt_db->where('SER_ID', $id);
        $this->pwt_db->update('T_CPE_SERVER_TRACKING', $formData);

        return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }

    /**
     * Remove the data from the table
     * @param $id
     * @return bool
     */
    public function destroy($deviceId){
        $this->db->where('DEVICE_ID', $deviceId);
        $this->db->delete('T_CPE_SERVER_TRACKING');

        return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }


}