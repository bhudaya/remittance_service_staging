
<?php
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfig;
use Iapps\RemittanceService\RemittanceConfig\RemittanceConfigRepository;
use Iapps\RemittanceService\RemittanceConfig\CorpServRemittanceConfigService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\RemittanceService\RemittanceConfig\CorpServRemittanceConfigCollection;
class Corp_serv_remittance_config extends Base_Controller{
    protected $_corp_serv_remittance_config_service;
    function __construct()
    {
        parent::__construct();
        $this->load->model('remittanceconfig/Remittance_config_model');
        $repo = new RemittanceConfigRepository($this->Remittance_config_model);
        $this->_corp_serv_remittance_config_service = new CorpServRemittanceConfigService($repo);

    }
    public function addCorpServiceRemittanceConfig()
    {
        if( !$this->is_required($this->input->post(), array('service_provider_id',
            'remittance_service_id',
            'min_limit',
            'max_limit',
            'step_amount',
            'is_default',
            'cashin_corp_serv_name',
            'cashin_corp_serv_desc',
            'cashin_daily_limit',
            'cashout_corp_serv_name',
            'cashout_corp_serv_desc',
            'cashout_daily_limit')))
        {
            return false;
        }
        $service_provider_id = $this->input->post("service_provider_id");
        $remittance_service_id = $this->input->post("remittance_service_id");
        $min_limit = $this->input->post("min_limit");
        $max_limit = $this->input->post("max_limit");
        $step_amount = $this->input->post("step_amount");
        $is_default = $this->input->post("is_default");
        $cashin_corp_serv_name = $this->input->post("cashin_corp_serv_name");
        $cashin_corp_serv_desc = $this->input->post("cashin_corp_serv_desc");
        $cashin_daily_limit = $this->input->post("cashin_daily_limit");
        $cashout_corp_serv_name = $this->input->post("cashout_corp_serv_name");
        $cashout_corp_serv_desc = $this->input->post("cashout_corp_serv_desc");
        $cashout_daily_limit = $this->input->post("cashout_daily_limit");
        $admin_id = $this->_get_admin_id();
        $config = new \Iapps\RemittanceService\RemittanceConfig\CorpServRemittanceConfig();
        $config->setServiceProviderId($service_provider_id);
        $config->setRemittanceServiceId($remittance_service_id);
        $config->setMinLimit($min_limit);
        $config->setMaxLimit($max_limit);
        $config->setStepAmount($step_amount);
        $config->setIsDefault($this->convertStringToBooleanInt($is_default));
        $config->setCashInCorpServName($cashin_corp_serv_name);
        $config->setCashInCorpServDesc($cashin_corp_serv_desc);
        $config->setCashInDailyLimit($cashin_daily_limit);
        $config->setCashOutCorpServName($cashout_corp_serv_name);
        $config->setCashOutCorpServDesc($cashout_corp_serv_desc);
        $config->setCashOutDailyLimit($cashout_daily_limit);
        $this->_corp_serv_remittance_config_service->setUpdatedBy($admin_id);
        if( $config = $this->_corp_serv_remittance_config_service->addCorpServiceRemittanceConfig($config) )
        {
            $this->_respondWithSuccessCode($this->_corp_serv_remittance_config_service->getResponseCode(), array('result' => $config));
            return true;
        }
        $this->_respondWithCode($this->_corp_serv_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    public function editCorpServiceRemittanceConfig()
    {
        if( !$this->is_required($this->input->post(), array('id',
            'service_provider_id',
            'min_limit',
            'max_limit',
            'step_amount',
            'is_default',
            'cashin_corp_serv_name',
            'cashin_corp_serv_desc',
            'cashin_daily_limit',
            'cashout_corp_serv_name',
            'cashout_corp_serv_desc',
            'cashout_daily_limit')))
        {
            return false;
        }
        $id = $this->input->post("id");
        $service_provider_id = $this->input->post("service_provider_id");
        $remittance_service_id = $this->input->post("remittance_service_id");
        $min_limit = $this->input->post("min_limit");
        $max_limit = $this->input->post("max_limit");
        $step_amount = $this->input->post("step_amount");
        $is_default = $this->input->post("is_default");
        $cashin_corp_serv_name = $this->input->post("cashin_corp_serv_name");
        $cashin_corp_serv_desc = $this->input->post("cashin_corp_serv_desc");
        $cashin_daily_limit = $this->input->post("cashin_daily_limit");
        $cashout_corp_serv_name = $this->input->post("cashout_corp_serv_name");
        $cashout_corp_serv_desc = $this->input->post("cashout_corp_serv_desc");
        $cashout_daily_limit = $this->input->post("cashout_daily_limit");
        $admin_id = $this->_get_admin_id();
        $config = new \Iapps\RemittanceService\RemittanceConfig\CorpServRemittanceConfig();
        $config->setId($id);
        $config->setServiceProviderId($service_provider_id);
        $config->setRemittanceServiceId($remittance_service_id);
        $config->setMinLimit($min_limit);
        $config->setMaxLimit($max_limit);
        $config->setStepAmount($step_amount);
        $config->setIsDefault($this->convertStringToBooleanInt($is_default));
        $config->setCashInCorpServName($cashin_corp_serv_name);
        $config->setCashInCorpServDesc($cashin_corp_serv_desc);
        $config->setCashInDailyLimit($cashin_daily_limit);
        $config->setCashOutCorpServName($cashout_corp_serv_name);
        $config->setCashOutCorpServDesc($cashout_corp_serv_desc);
        $config->setCashOutDailyLimit($cashout_daily_limit);
        $this->_corp_serv_remittance_config_service->setUpdatedBy($admin_id);
        if( $config = $this->_corp_serv_remittance_config_service->editCorpServiceRemittanceConfig($config) )
        {
            $this->_respondWithSuccessCode($this->_corp_serv_remittance_config_service->getResponseCode(), array('result' => $config));
            return true;
        }
        $this->_respondWithCode($this->_corp_serv_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    public function getDefaultRemittanceConfigByRemittanceServiceId(){
        $limit                 = $this->input->post("limit");
        $page                  = $this->input->post("page");
        $remittance_service_id = $this->input->post('remittance_service_id');
        if($result = $this->_corp_serv_remittance_config_service->getDefaultCorpServiceRemittanceConfigInfo($remittance_service_id,$limit, $page))
        {
            $this->_respondWithSuccessCode($this->_corp_serv_remittance_config_service->getResponseCode(),array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_corp_serv_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    public function getCorpServiceFeeByRemittanceConfigId(){
        $limit                 = $this->input->get("limit");
        $page                  = $this->input->get("page");
        $remittance_config_id = $this->input->get('remittance_config_id');
        if($result = $this->_corp_serv_remittance_config_service->getCorpServiceFeeByRemittanceConfigId($remittance_config_id,$limit, $page))
        {
            $this->_respondWithSuccessCode($this->_corp_serv_remittance_config_service->getResponseCode(),array('result' => $result, 'total' => count($result)));
            return true;
        }
        $this->_respondWithCode($this->_corp_serv_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    public function getCorpServiceRemittanceConfigInfo(){
        $remittance_config_id = $this->input->get('remittance_config_id');
        if($result = $this->_corp_serv_remittance_config_service->getCorpServiceRemittanceConfigInfo($remittance_config_id))
        {
            $this->_respondWithSuccessCode($this->_corp_serv_remittance_config_service->getResponseCode(),array('result' => $result));
            return true;
        }
        $this->_respondWithCode($this->_corp_serv_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    public function getAllCorpServiceRemittanceConfig(){
        $limit               = $this->input->get("limit");
        $page                = $this->input->get("page");
        if($object = $this->_corp_serv_remittance_config_service->getAllCorpServiceRemittanceConfig($limit, $page))
        {
            $this->_respondWithSuccessCode($this->_corp_serv_remittance_config_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }
        $this->_respondWithCode($this->_corp_serv_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    /*
        public function editRemittanceConfig(){
            if( !$this->is_required($this->input->post(), array('remittance_config_id',
                                                                'country_code',
                                                                'service_provider_id',
                                                                'name',
                                                                'country_currency_code',
                                                                'remittance_service_id',
                                                                'min_limit',
                                                                'max_limit',
                                                                'is_default',
                                                                'step_amount',
                                                                'cashin_corporate_service_id',
                                                                'cashout_corporate_service_id')) )
            {
                return false;
            }
            $admin_id = $this->_get_admin_id();
            $corporateService = new CorporateService();
            $corporateService->setCountryCode($this->input->post('country_code'));
            $corporateService->setServiceProviderId($this->input->post('service_provider_id'));
            $corporateService->setName($this->input->post('name'));
            $corporateService->setCountryCurrencyCode($this->input->post('country_currency_code'));
            $corporateService->setUpdatedBy($admin_id);
            $dailyLimit = $this->input->post('daily_limit');
            if(!empty($dailyLimit)){
                 $corporateService->setDailyLimit($this->input->post('daily_limit'));
            }

            $remittanceConfig = new RemittanceConfig();
            $remittanceConfig->setRemittanceServiceId($this->input->post('remittance_service_id'));
            $remittanceConfig->setMinLimit($this->input->post('min_limit'));
            $remittanceConfig->setMaxLimit($this->input->post('max_limit'));
            $remittanceConfig->setIsDefault($this->input->post('is_default'));
            $remittanceConfig->setStepAmount($this->input->post('step_amount'));
            $remittanceConfig->setId($this->input->post('remittance_config_id'));
            $remittanceConfig->setCashinCorporateServiceId($this->input->post('cashin_corporate_service_id'));
            $remittanceConfig->setCashoutCorporateServiceId($this->input->post('cashout_corporate_service_id'));
            $remittanceConfig->setUpdatedBy($admin_id);
            if($result = $this->_remittance_config_service->editRemittanceConfig($corporateService,$remittanceConfig)){
                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode());
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        public function getAllRemittanceConfig(){
            $limit               = $this->input->post("limit");
            $page                = $this->input->post("page");
            $serviceProviderName = $this->input->post('service_provider');
            if($object = $this->_remittance_config_service->getAllRemittanceConfig($serviceProviderName,$limit, $page)){
                $result_array = $object->result != null ? $object->result->toArray() : null;

                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(),array('result' => $result_array, 'total' => $object->total));
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        public function getRemittanceConfigInfo(){

            if( !$this->is_required($this->input->post(), array('id'
                                                                )) )
            {
                return false;
            }
            if($info = $this->_remittance_config_service->getRemittanceConfigById($this->input->post("id"))){
                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(),array('result' => $info));
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        public function getRemittanceConfigInfoByRemittanceServiceId(){
            if( !$this->is_required($this->input->get(), array('remittance_service_id'
            )) )
            {
                return false;
            }
            $remittance_service_id = $this->input->get("remittance_service_id");
            if($info = $this->_remittance_config_service->getRemittanceConfigByRemittanceServiceId($remittance_service_id)){
                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(),array('result' => $info));
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        public function addCorporateServRemittanceFee(){
            if( !$this->is_required($this->input->post(), array('corporate_service_id',
                                                                'service_provider_id',
                                                                'is_percentage',
                                                                'transaction_fee',
                                                                'fee_type_id',
                                                                'name'
                                                                )) )
            {
                return false;
            }
            $admin_id = $this->_get_admin_id();

            $corporateServiceFee = new CorporateServiceFee();
            $corporateServiceFee->setCorporateServiceId($this->input->post('corporate_service_id'));
            $corporateServiceFee->setServiceProviderId($this->input->post('service_provider_id'));
            $corporateServiceFee->setIsPercentage($this->input->post('is_percentage'));
            $corporateServiceFee->setTransactionFee($this->input->post('transaction_fee'));
            $corporateServiceFee->setFeeTypeId($this->input->post('fee_type_id'));
            $corporateServiceFee->setName($this->input->post('name'));
            $corporateServiceFee->getCreatedBy($admin_id);
            if($result = $this->_remittance_config_service->addCorporateServRemittanceFee($corporateServiceFee)){
                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode());
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        public function editCorporateServRemittanceFee(){
            if( !$this->is_required($this->input->post(), array('id',
                                                                'corporate_service_id',
                                                                'service_provider_id',
                                                                'is_percentage',
                                                                'transaction_fee',
                                                                'fee_type_id',
                                                                'name'
                                                                )) )
            {
                return false;
            }
            $admin_id = $this->_get_admin_id();

            $corporateServiceFee = new CorporateServiceFee();
            $corporateServiceFee->setId($this->input->post('id'));
            $corporateServiceFee->setCorporateServiceId($this->input->post('corporate_service_id'));
            $corporateServiceFee->setServiceProviderId($this->input->post('service_provider_id'));
            $corporateServiceFee->setIsPercentage($this->input->post('is_percentage'));
            $corporateServiceFee->setTransactionFee($this->input->post('transaction_fee'));
            $corporateServiceFee->setFeeTypeId($this->input->post('fee_type_id'));
            $corporateServiceFee->setName($this->input->post('name'));
            $corporateServiceFee->setUpdatedBy($admin_id);
            if($result = $this->_remittance_config_service->editCorporateServRemittanceFee($corporateServiceFee)){
                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode());
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        public function deleteCorporateServRemittanceFee(){
            if( !$this->is_required($this->input->post(), array('id'
                                                                )) )
            {
                return false;
            }
            $admin_id = $this->_get_admin_id();
            $id                = $this->input->post("id");
            $corporateServiceFee = new CorporateServiceFee();
            $corporateServiceFee->setId($this->input->post('id'));
            $corporateServiceFee->setDeletedBy($admin_id);
            if($result = $this->_remittance_config_service->deleteCorporateServRemittanceFee($corporateServiceFee)){
                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode());
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        public function getAllCorporateServRemittanceFee(){
            if( !$this->is_required($this->input->post(), array('corporate_service_id'
                                                                )) )
            {
                return false;
            }
            $limit                = $this->input->post("limit");
            $page                 = $this->input->post("page");
            $corporate_service_id = $this->input->post('corporate_service_id');
            if($object = $this->_remittance_config_service->getAllCorporateServRemittanceFee($corporate_service_id,$limit, $page)){
                $result_array = $object->result != null ? $object->result->toArray() : null;

                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(),array('result' => $result_array, 'total' => $object->total));
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }
        public function getCorporateServRemittanceFeeInfo(){

            if( !$this->is_required($this->input->post(), array('id'
                                                                )) )
            {
                return false;
            }
            if($info = $this->_remittance_config_service->getCorporateServRemittanceFeeInfo($this->input->post("id"))){
                $this->_respondWithSuccessCode($this->_remittance_config_service->getResponseCode(),array('result' => $info));
                return true;
            }
            $this->_respondWithCode($this->_remittance_config_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }*/
}