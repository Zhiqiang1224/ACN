<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class t_acs_server_tracking extends CI_Model
{
    private $pwt_db;
    private $oss_db;

    /**
     * __construct
     * ACS constructor
     * Initiate the class attributes and the API config params
     * @param $email
     */
    public function __construct() {
        parent::__construct();
    }

    public function initialise($pwt_db, $oss_db)
    {
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
            'TRCK_CREATE_DATE'      => date('Y-m-d'),
        );

        $this->pwt_db->insert('T_ACS_SERVER_TRACKING', $formData);

        $insert_id = $this->pwt_db->insert_id();

        return $insert_id;

       // return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }

    /**
     * @param $data
     * @param $id
     * @return bool
     */
    public function update($data, $id){

        $formData = array(
            'DSL_ID'           => $data['dslId'],
            'USER_NAME'        => $data['username'],
            'SER_NUMBER'       => $data['password'],
            'MAC_ADDRESS'      => $data['password'],
            'SUB_SCRIBER_CODE' => $data['password'],
            'EMAIL_ADDRESS'    => $data['password'],
            'SERVICE_PROVIDER' => $data['password'],
            'FULL_NAME'        => $data['password'],
            'DOMAIN'           => $data['password'],
            'RADIUS_PASSWORD'  => $data['password'],
            'CABLE'            => $data['password'],
            'DATE'             => date('Y-m-d')
        );

        $this->pwt_db->where('SER_ID', $id);
        $this->pwt_db->update('T_ACS_SERVER_TRACKING', $formData);

        return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id){

        $this->db->where('EST_ID', $id);
        $this->db->delete('T_ACS_SERVER_TRACKING');

        return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }

}