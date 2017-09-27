<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingRepository;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingService;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharing;
use Iapps\RemittanceService\RemittanceProfitSharing\RemittanceCorpServProfitSharingStatus;
use Iapps\RemittanceService\Common\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;

class Remittance_profit_sharing extends Admin_Base_Controller{

    protected $_service;

    function __construct()
    {
        parent::__construct();

        $this->load->model('remittanceprofitsharing/Remittance_profit_sharing_model');
        $repo = new RemittanceCorpServProfitSharingRepository($this->Remittance_profit_sharing_model);
        $this->_service = new RemittanceCorpServProfitSharingService($repo);
    }

    public function searchProfitSharing()
    {
        if( !$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PROFIT_SHARING, AccessType::READ) )
            return false;

        $limit  = $this->input->get("limit");
        $page   = $this->input->get("page");
        $status = $this->input->get("status") ? $this->input->get("status") : NULL;
        $remittance_config_id = $this->input->get("remittance_config_id") ? $this->input->get("remittance_config_id") : NULL;
        $from_country_currency_code = $this->input->get("from_country_currency_code") ? $this->input->get("from_country_currency_code") : NULL;
        $to_country_currency_code = $this->input->get("to_country_currency_code") ? $this->input->get("to_country_currency_code") : NULL;
        $from_country_partner_id = $this->input->get("from_country_partner_id") ? $this->input->get("from_country_partner_id") : NULL;
        $to_country_partner_id = $this->input->get("to_country_partner_id") ? $this->input->get("to_country_partner_id") : NULL;


        if( $object = $this->_service->searchProfitSharing($limit, $page, $status, $remittance_config_id, $from_country_currency_code, $to_country_currency_code, $from_country_partner_id, $to_country_partner_id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object->result, 'total' => $object->total));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;

    }


    public function getAllProfitSharing()
    {
        if( !$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PROFIT_SHARING, AccessType::READ) )
            return false;

        $limit  = $this->input->get("limit");
        $page   = $this->input->get("page");
        $status = $this->input->get("status") ? $this->input->get("status") : NULL;
        $is_active = $this->input->get("is_active") ? $this->input->get("is_active") : NULL;
        $corporate_service_id = $this->input->get("corporate_service_id") ? $this->input->get("corporate_service_id") : NULL;

        if( $object = $this->_service->getProfitSharingList($limit, $page, $is_active, $status, $corporate_service_id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object->result, 'total' => $object->total));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getProfitSharingInfo()
    {
        // if( !$this->is_required($this->input->get(), array('corporate_service_profit_sharing_id')))
        // {
        //     return false;
        // }

        if( !$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_PROFIT_SHARING, AccessType::READ) )
            return false;

        $corporate_service_profit_sharing_id = $this->input->get("corporate_service_profit_sharing_id") ? $this->input->get("corporate_service_profit_sharing_id") : NULL;

        $corporate_service_id = $this->input->get("corporate_service_id") ? $this->input->get("corporate_service_id") : NULL;


        if( $info = $this->_service->getProfitSharingInfo($corporate_service_profit_sharing_id,$corporate_service_id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $info));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function addProfitSharing()
    {
        /*sample param

         parties = [
                    {"percentage":"30","service_provider_id":"999"},
                    {"percentage":"70","service_provider_id":"888"}
                   ]
        */

        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_ADD_PROFIT_SHARING, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('corporate_service_id','parties')))
        {
            return false;
        }

        $corporate_service_profit_sharing_id = $this->input->post('corporate_service_profit_sharing_id') ? $this->input->post('corporate_service_profit_sharing_id') : NULL;
        $corporate_service_id = $this->input->post('corporate_service_id');
        $parties = $this->input->post('parties');
        $parties = json_decode($parties, true);

        $corp_serv_profit_sharing = new RemittanceCorpServProfitSharing();
        $corp_serv_profit_sharing->setCreatedBy($admin_id);
        $corp_serv_profit_sharing->setCorporateServiceId($corporate_service_id);

        if( $object = $this->_service->addProfitSharing($corp_serv_profit_sharing, $parties, $corporate_service_profit_sharing_id) )
        {   
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function updateProfitSharing()
    {

        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_UPDATE_PROFIT_SHARING, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('corporate_service_profit_sharing_id','status')))
        {
            return false;
        }


        $corporate_service_profit_sharing_id = $this->input->post('corporate_service_profit_sharing_id');
        $status = $this->input->post('status');

        $remark = $this->input->post('remark') ? $this->input->post('remark') : NULL;

        $corp_serv_profit_sharing = new RemittanceCorpServProfitSharing();
        $corp_serv_profit_sharing->setStatus($status);
        $corp_serv_profit_sharing->setApproveRejectRemark($remark);
        $corp_serv_profit_sharing->setApproveRejectBy($admin_id);
        $corp_serv_profit_sharing->setUpdatedBy($admin_id);
        $corp_serv_profit_sharing->setId($corporate_service_profit_sharing_id);
        $corp_serv_profit_sharing->setApproveRejectAt(IappsDateTime::now());

        if( $object = $this->_service->updateProfitSharing($corp_serv_profit_sharing) )
        {   
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function cancelProfitSharing()
    {

        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_CANCEL_PROFIT_SHARING, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('corporate_service_profit_sharing_id','status')))
        {
            return false;
        }


        $corporate_service_profit_sharing_id = $this->input->post('corporate_service_profit_sharing_id');
        $status = $this->input->post('status');

        $remark = $this->input->post('remark') ? $this->input->post('remark') : NULL;

        $corp_serv_profit_sharing = new RemittanceCorpServProfitSharing();
        $corp_serv_profit_sharing->setStatus($status);
        $corp_serv_profit_sharing->setApproveRejectRemark($remark);
        $corp_serv_profit_sharing->setApproveRejectBy($admin_id);
        $corp_serv_profit_sharing->setUpdatedBy($admin_id);
        $corp_serv_profit_sharing->setId($corporate_service_profit_sharing_id);
        $corp_serv_profit_sharing->setApproveRejectAt(IappsDateTime::now());

        if( $object = $this->_service->updateProfitSharing($corp_serv_profit_sharing) )
        {   
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}