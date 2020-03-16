<?php
/**
 * Created by PhpStorm.
 * Author: zhiqiang yang
 * Date: 2019-07-26
 * Time: 8:46 AM
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class m_hsi_network extends CI_Model {

    private $pwt_db;
    private $oss_db;

    public function __construct() {
        parent::__construct();
    }

    public function initialise($pwt_db, $oss_db)
    {
        $this->pwt_db = $pwt_db;
        $this->oss_db = $oss_db;
    }


    /**
     * Get all the information of the hsi provrequest
     * @return bool
     */
    public function getAllRequestForActivation(){

//        $sql = "SELECT *
//                FROM PROVISION.PROVREQUEST
//                WHERE  PROVORDTYPEID IN (1,4,33)
//                AND  PROVREQUESTSTATUSID = 3
//                AND  PROVREQUESTID > 12341508
//                AND  rownum <= 10
//                ORDER BY PROVREQUESTID DESC";

        $sql = "SELECT *
        FROM PROVISION.PROVREQUEST
        WHERE  PROVORDTYPEID IN (1)
        AND  PROVREQUESTSTATUSID = 3
        AND  PROVREQUESTID > 12441508
        AND  rownum <= 5
 
union
SELECT *
        FROM PROVISION.PROVREQUEST
        WHERE  PROVORDTYPEID IN (4)
        AND  PROVREQUESTSTATUSID = 3
        AND  PROVREQUESTID > 12441508
        AND  rownum <= 5
union
SELECT *
        FROM PROVISION.PROVREQUEST
        WHERE  PROVORDTYPEID IN (33)
        AND  PROVREQUESTSTATUSID = 3
        AND  PROVREQUESTID > 12441508
        AND  rownum <= 5";


        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    /**
     * @param $proRequestId
     * @return bool
     */
    public function getEmailServerOrdersByRequest($proRequestId){

        $sql = "SELECT * 
                FROM  PROVISION.EMAILSERVERORD 
                WHERE PROVREQUESTID = {$proRequestId}";

        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    /**
     * @param $proRequestId
     * @return bool
     */
    public function getRadiusOrdersByRequest($proRequestId){

        $sql = "SELECT * 
                FROM  PROVISION.RADIUSSERVERORD 
                WHERE PROVREQUESTID = {$proRequestId}";

        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    /**
     * @param $proRequestId
     * @return bool
     */
    public function getAcsOrdersByRequest($proRequestId){

        $sql = "SELECT * 
                FROM   PROVISION.ACSORD 
                WHERE  PROVREQUESTID = {$proRequestId}";

        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    /**
     * @param $proRequestId
     * @return bool
     */
    public function getCpeOrdersByRequest($proRequestId){

        $sql = "SELECT * 
                FROM   PROVISION.CPEORD 
                WHERE  PROVREQUESTID = {$proRequestId}";

        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    public function updateRequestStatus($proRequestId, $status){
        $sql = "UPDATE PROVISION.PROVREQUEST 
                SET    PROVREQUESTSTATUSID = {$status}
                WHERE  PROVREQUESTID = {$proRequestId}";

        $result = $this->oss_db->query($sql);
        return $result;
    }

    public function saveAcsMessage($orderId, $response){
        $sql = "INSERT INTO  PROVISION.ACSMESSAGE('') 
                VALUES    
               ";

        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    public function saveCpeMessage($order, $response){
        $sql = "INSERT INTO CPEMESSAGE
                ('CPEORDID','ERRORTEXT','CREATEDBY','CREATEDDATE','MODIFIEDBY','MODIFIEDDATE','PON','ERRORCODE')
                 VALUES
                 ({$order['CPEORDID']},{$response->ERRORTEXT},'CREATEDBY', sysdate,'MODIFIEDBY',sysdate ,{$order['PON']}, {$response->ERRORCODE});
               ";

        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    public function saveEmailMessage($orderId, $response){
        $sql = "INSERT INTO  PROVISION.EMAILSERVERMESSAGE 
                VALUES    
               ";

        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    public function saveRadiusMessage($orderId, $response){
        $sql = "INSERT INTO  PROVISION.RADIUSSERVERMESSAGE 
                VALUES ({$orderId},{$response})   
               ";

        $result = $this->oss_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }




    public function getAddEmailParameters(){
        $sql = "SELECT
                T_A.ACC_ORGSYS_ID   AS CCP_CUSTID ,
                T_S.SER_ID,
                T_S.SER_ORGSYS_ID,
                T_S.SER_USER_NAME   AS USERNAME,
                T_S.SER_PASSWORD    AS PASSWORD,
                T_D.DSL_ID,
                T_D.DSL_TASK_DATE,
                T_D.DSL_STATUS_ID
                FROM
                T_SERVICES T_S,
                T_DSL T_D,
                T_ORDER T_O,
                T_ACCOUNT T_A
                WHERE    T_S.SER_ID=T_D.SER_ID
                AND      T_O.ORD_ID=T_S.ORD_ID
                AND      T_A.ACC_ID=T_O.ACC_ID
                AND      T_D.DSL_STATUS_ID = 21
                AND      T_D.DSL_TASK_DATE <= now()
                ";

        $result = $this->pwt_db->query($sql);

        if ($result->num_rows != 0) {
            return $result->result_array();
        } else {
            return FALSE;
        }
    }

    public function lock_service($id, $by)
    {
        $data = array(
            'SER_LOCKED_BY' => $by,
            'SER_LOCKED_TIME' => date('Y-m-d h:i:s')
        );

        if ($id !== NULL)
        {
            $this->pwt_db->where('SER_ID', $id);
            $this->pwt_db->where('SER_LOCKED_BY IS NULL', null, false);
            $this->pwt_db->update('T_SERVICES', $data);
        }

        if ($this->pwt_db->affected_rows() > 0)
        {
            return TRUE;
        } else
        {
            return FALSE;
        }
    }



    public function unlock_service($id)
    {
        $data = array(
            'SER_LOCKED_BY' => NULL,
            'SER_LOCKED_TIME' => NULL
        );

        if ($id !== NULL)
        {
            $this->pwt_db->where('SER_ID', $id);
            $this->pwt_db->update('T_SERVICES', $data);
        }
    }

    public function update_dsl_status($id, $status, $trxId = null)
    {
        if ($trxId == null)
        {
            $data = array(
                'DSL_STATUS_ID' => $status,
                'DSL_TASK_DATE' => date('Y-m-d'));
        } else
        {
            $data = array(
                'DSL_STATUS_ID' => $status,
                'DSL_TASK_DATE' => date('Y-m-d'),
                'DSL_TRANSACTION_ID' => $trxId,
                'DSL_VENDOR_STATUS_ID' => 1
            );
        }


        if ($id !== NULL)
        {
            $this->pwt_db->where('DSL_ID', $id);
            $this->pwt_db->update('T_DSL', $data);
        }
    }

}

