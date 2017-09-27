<?php

use Iapps\RemittanceService\RemittanceCompany\IRemittanceCompanyDataMapper;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompany;
use Iapps\RemittanceService\RemittanceCompany\RemittanceCompanyCollection;
use Iapps\Common\Core\IappsDateTime;

class Remittance_company_model extends Base_Model
                               implements IRemittanceCompanyDataMapper{

    public function map(\stdClass $data)
    {
        $entity = new RemittanceCompany();

        if( isset($data->remittance_company_id) )
            $entity->setId($data->remittance_company_id);

        if( isset($data->service_provider_id) )
            $entity->setServiceProviderId($data->service_provider_id);

        if( isset($data->company_code) )
            $entity->setCompanyCode($data->company_code);

        if( isset($data->receipt_footer) )
            $entity->setReceiptFooter($data->receipt_footer);

        if( isset($data->receipt_format) )
            $entity->setReceiptFormat($data->receipt_format);

        if( isset($data->uen) )
            $entity->setUen($data->uen);

        if( isset($data->mas_license_no) )
            $entity->setMasLicenseNo($data->mas_license_no);

        if( isset($data->required_face_to_face_verification) )
            $entity->setRequiredFaceToFaceVerification($data->required_face_to_face_verification);

        if( isset($data->required_acuity_check) )
            $entity->setRequiredAcuityCheck($data->required_acuity_check);
		
		if( isset($data->required_face_to_face_trans) )
			$entity->setRequiredFaceToFaceTrans($data->required_face_to_face_trans);
		
		if( isset($data->required_face_to_face_recipient) )
			$entity->setRequiredFaceToFaceRecipient($data->required_face_to_face_recipient);
		
		if( isset($data->required_manual_approval_nff) )
			$entity->setRequiredManualApprovalNFF($data->required_manual_approval_nff);

        if( isset($data->required_dow_jones_check) )
            $entity->setRequiredDowJonesCheck($data->required_dow_jones_check);

        if( isset($data->required_ofac_un_mas_check) )
            $entity->setRequiredOFACUNMASCheck($data->required_ofac_un_mas_check);

        if( isset($data->match_criteria) )
            $entity->setMatchCriteria($data->match_criteria);

        if( isset($data->interval_of_dow_jones) )
            $entity->setIntervalOfDowJones($data->interval_of_dow_jones);

        if( isset($data->created_at) )
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));

        if( isset($data->updated_by) )
            $entity->setUpdatedBy($data->updated_by);

        if( isset($data->deleted_at) )
            $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));

        if( isset($data->deleted_by) )
            $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select('id as remittance_company_id,
                           service_provider_id,
                           company_code,
                           receipt_footer,
                           receipt_format,
                           uen,
                           mas_license_no,
                           required_face_to_face_verification,
                           required_acuity_check,
                           required_face_to_face_trans,
                           required_face_to_face_recipient,
                           required_manual_approval_nff,
                           required_dow_jones_check,
                           required_ofac_un_mas_check,
                           match_criteria,
                           interval_of_dow_jones,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by
                           ');
        $this->db->from('iafb_remittance.remittance_company');
        if( !$deleted )
            $this->db->where('deleted_at', NULL);
        $this->db->where('id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByFilter(RemittanceCompany $remittanceCompany)
    {
        $this->db->select('id as remittance_company_id,
                           service_provider_id,
                           company_code,
                           receipt_footer,
                           receipt_format,
                           uen,
                           mas_license_no,
                           required_face_to_face_verification,
                           required_acuity_check,
                           required_face_to_face_trans,
                           required_face_to_face_recipient,
                           required_manual_approval_nff,
                           required_dow_jones_check,
                           required_ofac_un_mas_check,
                           match_criteria,
                           interval_of_dow_jones,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.remittance_company');
        $this->db->where('deleted_at', NULL);
        if( $remittanceCompany->getId() )
            $this->db->where('remittance_company_id', $remittanceCompany->getId());
        if( $remittanceCompany->getServiceProviderId() )
            $this->db->where('service_provider_id', $remittanceCompany->getServiceProviderId());
        if( $remittanceCompany->getCompanyCode() )
            $this->db->where('company_code', $remittanceCompany->getCompanyCode());

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceCompanyCollection(), $query->num_rows());
        }

        return false;
    }

    public function updateByServiceProviderId(RemittanceCompany $remittanceCompany)
    {

        $this->db->set('company_code', $remittanceCompany->getCompanyCode());
        $this->db->set('receipt_footer', $remittanceCompany->getReceiptFooter());
        $this->db->set('receipt_format', $remittanceCompany->getReceiptFormat());
        $this->db->set('uen', $remittanceCompany->getUen());
        $this->db->set('mas_license_no', $remittanceCompany->getMasLicenseNo());
        $this->db->set('required_face_to_face_verification', $remittanceCompany->getRequiredFaceToFaceVerification());
        $this->db->set('required_acuity_check', $remittanceCompany->getRequiredAcuityCheck());
		$this->db->set('required_face_to_face_trans', $remittanceCompany->getRequiredFaceToFaceTrans());
		$this->db->set('required_face_to_face_recipient', $remittanceCompany->getRequiredFaceToFaceRecipient());
		$this->db->set('required_manual_approval_nff', $remittanceCompany->getRequiredManualApprovalNFF());

        $this->db->set('required_dow_jones_check', $remittanceCompany->getRequiredDowJonesCheck());
        $this->db->set('required_ofac_un_mas_check', $remittanceCompany->getRequiredOFACUNMASCheck());
        $this->db->set('match_criteria', $remittanceCompany->getMatchCriteria());
        $this->db->set('interval_of_dow_jones', $remittanceCompany->getIntervalOfDowJones());

        $this->db->set('updated_by', $remittanceCompany->getUpdatedBy());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());

        $this->db->where('id', $remittanceCompany->getId());
        $this->db->where('service_provider_id', $remittanceCompany->getServiceProviderId());

        $this->db->update('iafb_remittance.remittance_company');

        if( $this->db->affected_rows() > 0 )
        {
            return true;
        }

        return false;
    }
}