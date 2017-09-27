<?php

namespace Iapps\RemittanceService\RemittanceCompany;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\PaginatedResult;
use Iapps\Common\Helper\CreatorNameExtractor;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\RemittanceService\Common\MessageCode;
use Iapps\Common\Microservice\AccountService\PartnerAccountServiceFactory;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;

class RemittanceCompanyService extends IappsBaseService{

    public function getById($id)
    {
        return $this->getRepository()->findById($id);
    }

    public function getServiceProviderId()
    {
        // get user profile
        $acc_serv = PartnerAccountServiceFactory::build();
        if( $structure = $acc_serv->getAgentUplineStructure() )
        {
            if( $upline = $structure->first_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser()->getId();
                }
            }

            if( $upline = $structure->second_upline )
            {
                if( $upline->getRoles()->hasRole(array('main_agent')) )
                {
                    return $upline->getUser()->getId();
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_INVALID_SERVICE_PROVIDER);
        return false;
    }

    public function getByServiceProviderIds(array $service_provider_ids)
    {
        $remcos = new RemittanceCompanyCollection();
        foreach($service_provider_ids AS $service_provider_id)
        {//this makes using cache possible
            if( $info = $this->getRepository()->findByServiceProviderId($service_provider_id) )
            {
                $info->result->rewind();
                $remco = $info->result->current();
                if( $remco instanceof RemittanceCompany )
                    $remcos->addData($remco);
            }
        }

        //extract user + company
        RemittanceCompanyNameExtractor::extract($remcos);

        return $remcos;
    }

    public function getByServiceProviderId($service_provider_id, $array = false)
    {
        if( $info = $this->getRepository()->findByServiceProviderId($service_provider_id) )
        {
            $this->setResponseCode(MessageCode::CODE_COMPANY_GET_SUCCESS);
            $info->result->rewind();
            $remco = $info->result->current();
            if( $remco instanceof RemittanceCompany )
            {
                //extract user
                $collection = new RemittanceCompanyCollection();
                $collection->addData($remco);
                CreatorNameExtractor::extract($collection);

                //grab user information
                $accountService = AccountServiceFactory::build();
                //if( $users = $accountService->getUsers(array($remco->getServiceProviderId())) )
                if( $user = $accountService->getUser(NULL, $remco->getServiceProviderId()) )
                {
                    $users = new IappsBaseEntityCollection();
                    $users->addData($user);
                    $collection->joinCompanyInfo($users);
                }

                $collection->rewind();
                if( !$array )
                    return $collection->current();
                else
                {
                    $remco = $collection->current();
                    $array = $remco->jsonSerialize();
                    $array['name'] = $remco->getCompanyInfo()->getName();
                    $array['accountID'] = $remco->getCompanyInfo()->getAccountID();
                    $array['logo'] = $remco->getCompanyInfo()->getProfileImageUrl();
                    return $array;
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_COMPANY_GET_FAILED);
        return false;
    }

    public function getByCompanyCode($company_code)
    {
        if( $info = $this->getRepository()->findByCompanyCode($company_code) )
        {
            $this->setResponseCode(MessageCode::CODE_COMPANY_GET_SUCCESS);
            $info->result->rewind();
            return $info->result->current(); //only 1
        }

        $this->setResponseCode(MessageCode::CODE_COMPANY_GET_FAILED);
        return false;
    }

    public function getList($page = 1, $limit = MAX_VALUE, $isArray = true)
    {
        if( $info = $this->getRepository()->findAll() )
        {
            $list = $info->result;

            if( $list instanceof RemittanceCompanyCollection )
            {
                $this->setResponseCode(MessageCode::CODE_COMPANY_LIST_FOUND);
                $result = $list->pagination($limit, $page);

                $paginatedList = $result->getResult();

                //grab user information
                if( $spIds = $paginatedList->getServiceProviderIds() )
                {
                    $accountService = AccountServiceFactory::build();
                    if( $users = $accountService->getUsers($spIds) )
                    {
                        $paginatedList->joinCompanyInfo($users);
                    }
                }

                if( $isArray )
                {
                    $result = array();

                    foreach( $paginatedList AS $remittanceCompany)
                    {
                        $temp = $remittanceCompany->jsonSerialize();
                        $temp['name'] = $remittanceCompany->getCompanyInfo()->getName();
                        $temp['accountID'] = $remittanceCompany->getCompanyInfo()->getAccountID();
                        $temp['logo'] = $remittanceCompany->getCompanyInfo()->getProfileImageUrl();

                        $result[] = $temp;
                    }

                    return $result;
                }
                else
                    return $paginatedList;
            }
        }

        $this->setResponseCode(MessageCode::CODE_COMPANY_LIST_NOT_FOUND);
        return false;
    }

    public function editByServiceProviderId(RemittanceCompany $entity, $ignore_null = true)
    {
        if ($companyInfo = $this->getByServiceProviderId($entity->getServiceProviderId())) {

            if( $companyInfo instanceof RemittanceCompany )
            {
                //this always ignore null to not break the existing API
                if( $entity->getReceiptFormat() )
                    $companyInfo->setReceiptFormat($entity->getReceiptFormat());
                if( $entity->getUen() OR !$ignore_null)
                    $companyInfo->setUen($entity->getUen());
                if( $entity->getMasLicenseNo() OR !$ignore_null)
                    $companyInfo->setMasLicenseNo($entity->getMasLicenseNo());
                if( $entity->getReceiptFooter() OR !$ignore_null)
                    $companyInfo->setReceiptFooter($entity->getReceiptFooter());
                if( $entity->getRequiredAcuityCheck() !== NULL OR !$ignore_null)
                    $companyInfo->setRequiredAcuityCheck($entity->getRequiredAcuityCheck());
                if( $entity->getRequiredFaceToFaceVerification() !== NULL OR !$ignore_null)
                    $companyInfo->setRequiredFaceToFaceVerification($entity->getRequiredFaceToFaceVerification());
				if( $entity->getRequiredFaceToFaceTrans() !== NULL OR !$ignore_null)
                    $companyInfo->setRequiredFaceToFaceTrans($entity->getRequiredFaceToFaceTrans());
				if( $entity->getRequiredFaceToFaceRecipient() !== NULL OR !$ignore_null)
                    $companyInfo->setRequiredFaceToFaceRecipient($entity->getRequiredFaceToFaceRecipient());
				if( $entity->getRequiredManualApprovalNFF() !== NULL OR !$ignore_null)
                    $companyInfo->setRequiredManualApprovalNFF($entity->getRequiredManualApprovalNFF());
                if( $entity->getRequiredDowJonesCheck() !== NULL OR !$ignore_null)
                    $companyInfo->setRequiredDowJonesCheck($entity->getRequiredDowJonesCheck());
                if( $entity->getRequiredOFACUNMASCheck() !== NULL OR !$ignore_null)
                    $companyInfo->setRequiredOFACUNMASCheck($entity->getRequiredOFACUNMASCheck());
                if( $entity->getMatchCriteria() !== NULL OR !$ignore_null)
                    $companyInfo->setMatchCriteria($entity->getMatchCriteria());
                if( $entity->getIntervalOfDowJones() !== NULL OR !$ignore_null)
                    $companyInfo->setIntervalOfDowJones($entity->getIntervalOfDowJones());

                $companyInfo->setUpdatedBy($this->getUpdatedBy());

                if ($this->getRepository()->updateByServiceProviderId($companyInfo)) {

                    $this->setResponseCode(MessageCode::CODE_COMPANY_EDIT_SUCCESS);
                    return true;
                }

                $this->setResponseCode(MessageCode::CODE_COMPANY_EDIT_FAILED);
                return false;
            }
        }

        $this->setResponseCode(MessageCode::CODE_COMPANY_EDIT_FAILED);
        return false;
    }
}