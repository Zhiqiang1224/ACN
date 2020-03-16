<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class t_radius_server_tracking extends CI_Model
{
    private $pwt_db;
    private $oss_db;

    /**
     * __construct
     * Radius server constructor
     * Initiate the class attributes and the API config params
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
            'DSL_ID'      => $data['dslId'],
            'USER_NAME'   => $data['userName'],
            'PASSWORD'    => $data['passWord'],
            'ACTION'      => $data['action'],
            'DATE'        => date('Y-m-d')
        );

        $this->pwt_db->insert('T_RADIUS_SERVER_TRACKING', $formData);

        return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }

    /**
     * @param $data
     * @param $id
     * @return bool
     */
    public function update($data, $id){

        $formData = array(
            'DSL_ID'      => $data['dslId'],
            'USER_NAME'   => $data['userName'],
            'PASSWORD'    => $data['passWord'],
            'ACTION'      => $data['action'],
            'DATE'        => date('Y-m-d')
        );

        $this->pwt_db->where('EST_ID', $id);
        $this->pwt_db->update('T_RADIUS_SERVER_TRACKING', $formData);

        return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id){

        $this->pwt_db->where('EST_ID', $id);
        $this->pwt_db->delete('T_RADIUS_SERVER_TRACKING');

        return ($this->pwt_db->affected_rows() > 0) ? true : false;

    }


}