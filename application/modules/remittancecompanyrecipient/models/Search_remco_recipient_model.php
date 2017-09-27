<?php

use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipientCollection;
use Iapps\RemittanceService\RemittanceCompanyRecipient\RemittanceCompanyRecipient;
use Iapps\RemittanceService\Recipient\RecipientCollection;
use Iapps\RemittanceService\Attribute\RecipientAttributeCollection;
use Iapps\RemittanceService\SearchRemcoRecipient\ISearchRemcoRecipientDataMapper;
use Iapps\Common\Core\IappsDateTime;

class Search_remco_recipient_model extends Base_Model
                                   implements ISearchRemcoRecipientDataMapper{
        
    public function map(\stdClass $data) {
        
        $entity = new RemittanceCompanyRecipient();
        
        if( isset($data->remittance_company_recipient_id) )
            $entity->setId($data->remittance_company_recipient_id);
        
        if( isset($data->country_code) )
            $entity->setCountryCode($data->country_code);
            
        if( isset($data->remittance_company_id) )
            $entity->getRemittanceCompany()->setId($data->remittance_company_id);
        
        if( isset($data->recipient_id) )
            $entity->getRecipient()->setId($data->recipient_id);

        if( isset($data->user_profile_id) )
            $entity->getRecipient()->setUserProfileId($data->user_profile_id);

        if( isset($data->recipient_type) )
            $entity->getRecipient()->setRecipientType($data->recipient_type);

        if( isset($data->is_international) )
            $entity->getRecipient()->setIsInternational($data->is_international);

        if( isset($data->recipient_user_profile_id) )
            $entity->getRecipient()->getRecipientUser()->setId($data->recipient_user_profile_id);

        if( isset($data->recipient_alias) )
            $entity->getRecipient()->setRecipientAlias($data->recipient_alias);

        if( isset($data->recipient_dialing_code) )
            $entity->getRecipient()->getRecipientDialingCode()->setEncryptedValue($data->recipient_dialing_code);

        if( isset($data->recipient_mobile_number) )
            $entity->getRecipient()->getRecipientMobileNumber()->setEncryptedValue($data->recipient_mobile_number);

        if( isset($data->is_active) )
            $entity->getRecipient()->setIsActive($data->is_active);

        if( isset($data->photo_image_url) )
            $entity->getRecipient()->setPhotoImageUrl($data->photo_image_url);

        if( isset($data->last_sent_at) )
            $entity->getRecipient()->getLastSentAt()->fromUnix($data->last_sent_at);

        if( isset($data->last_edited_at) )
            $entity->getRecipient()->getLastEditedAt()->fromUnix($data->last_edited_at);

        if( isset($data->recipient_created_at) )
            $entity->getRecipient()->setCreatedAt(IappsDateTime::fromUnix($data->recipient_created_at));
   
        if( isset($data->recipient_created_by) )
            $entity->getRecipient()->setCreatedBy($data->recipient_created_by);
   
        if( isset($data->recipient_updated_at) )
            $entity->getRecipient()->setUpdatedAt(IappsDateTime::fromUnix($data->recipient_updated_at));
   
        if( isset($data->recipient_updated_by) )
            $entity->getRecipient()->setUpdatedBy($data->recipient_updated_by);
   
        if( isset($data->recipient_deleted_at) )
            $entity->getRecipient()->setDeletedAt(IappsDateTime::fromUnix($data->recipient_deleted_at));
   
        if( isset($data->recipient_deleted_by) )
            $entity->getRecipient()->setDeletedBy($data->recipient_deleted_by);
        
        if( isset($data->recipient_status_id) )
            $entity->getRecipientStatus()->setId($data->recipient_status_id);

        if( isset($data->recipient_status_code) )
            $entity->getRecipientStatus()->setCode($data->recipient_status_code);

        if( isset($data->recipient_status_display_name) )
            $entity->getRecipientStatus()->setDisplayName($data->recipient_status_display_name);
        
        if( isset($data->face_to_face_verified_at) )
            $entity->getFaceToFaceVerifiedAt()->fromUnix($data->face_to_face_verified_at);
   
        if( isset($data->face_to_face_verified_by) )
            $entity->setFaceToFaceVerifiedBy($data->face_to_face_verified_by);
   
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
    
    protected function _select()
    {
        $this->db->select('rcr.id remittance_company_recipient_id,
                           rcr.country_code,
                           rcr.remittance_company_id,
                           r.id recipient_id,
                           r.user_profile_id,
                           r.recipient_type,
                           r.is_international,
                           r.recipient_user_profile_id,
                           r.recipient_alias,
                           r.recipient_dialing_code,
                           r.recipient_mobile_number,
                           r.is_active,
                           r.photo_image_url,
                           r.last_sent_at,
                           r.last_edited_at,
                           rcstat.id recipient_status_id,
                           rcstat.code recipient_status_code,
                           rcstat.display_name recipient_status_display_name,
                           rcr.face_to_face_verified_at,
                           rcr.face_to_face_verified_by,
                           rcr.created_at,
                           rcr.created_by,
                           rcr.updated_at,
                           rcr.updated_by,
                           rcr.deleted_at,
                           rcr.deleted_by,
                           r.created_at recipient_created_at,
                           r.created_by recipient_created_by,
                           r.updated_at recipient_updated_at,
                           r.updated_by recipient_updated_by,
                           r.deleted_at recipient_deleted_at,
                           r.deleted_by recipient_deleted_by');
    }
    
    public function findById($id, $deleted = false) {
        $this->_select();
        $this->db->from('iafb_remittance.remittance_company_recipient rcr');
        $this->db->join('iafb_remittance.recipient r', 'rcr.recipient_id = r.id');
        $this->db->join('iafb_remittance.system_code rcstat', 'rcr.recipient_status_id = rcstat.id');
        
        if( !$deleted )
        {
            $this->db->where('rcr.deleted_at', NULL);
            $this->db->where('r.deleted_at', NULL);
        }
        $this->db->where('rcr.id', $id);
        
        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }
            
        return false;
    }

    public function findByRecipientsAttributes(RemittanceCompanyRecipientCollection $remcoRecipientFilters,
                                               RecipientCollection $recipientFilters = NULL,
                                               RecipientAttributeCollection $attributeFilters = NULL)
    {
        $this->_select();
        $this->db->from('iafb_remittance.remittance_company_recipient rcr');
        $this->db->join('iafb_remittance.recipient r', 'rcr.recipient_id = r.id');
        $this->db->join('iafb_remittance.system_code rcstat', 'rcr.recipient_status_id = rcstat.id');
        
        $this->db->where('rcr.deleted_at', NULL);
        $this->db->where('r.deleted_at', NULL);
        
        //remco recipient
        if( count($remcoRecipientFilters) > 0 )
        {
            //only support gender and country code
            $statuses = array();
            $remco_ids = array();

            foreach($remcoRecipientFilters AS $remcoRecipient)
            {
                if( $remcoRecipient->getRecipientStatus()->getCode() != NULL )
                    $statuses[] = $remcoRecipient->getRecipientStatus()->getCode();

                if( $remcoRecipient->getRemittanceCompany()->getId() != NULL )
                    $remco_ids[] = $remcoRecipient->getRemittanceCompany()->getId();
            }

            if( count($statuses) > 0 )
                $this->db->where_in('rcstat.code', $statuses);
            if( count($remco_ids) > 0 )
                $this->db->where_in('rcr.remittance_company_id', $remco_ids);
        }
        
        //recipient
        if( count($recipientFilters) > 0 )
        {
            //
            $user_profile_ids = array();
            $dialing_codes = array();
            $mobile_numbers = array();

            foreach($recipientFilters AS $recipient)
            {
                if( $recipient->getUserProfileId() != NULL )
                    $user_profile_ids[] = $recipient->getUserProfileId();

                if( $recipient->getRecipientDialingCode()->getHashedValue() != NULL )
                    $dialing_codes[] = $recipient->getRecipientDialingCode()->getHashedValue();
                
                if( $recipient->getRecipientMobileNumber()->getHashedValue() != NULL )
                    $mobile_numbers[] = $recipient->getRecipientMobileNumber()->getHashedValue();
            }

            if( count($user_profile_ids) > 0 )
                $this->db->where_in('r.user_profile_id', $user_profile_ids);
            if( count($dialing_codes) > 0 )
                $this->db->where_in('r.hashed_recipient_dialing_code', $dialing_codes);
            if( count($mobile_numbers) > 0 )
                $this->db->where_in('r.hashed_recipient_mobile_number', $mobile_numbers);
        }
        
        //attributes
        if( count($attributeFilters) > 0 )
        {
            foreach($attributeFilters AS $attribute)
            {
                if( $attribute->getAttribute()->getCode() )
                {
                    $code = $attribute->getAttribute()->getCode();
                    $this->db->select("ra_$code.other_value");
                    $this->db->join("iafb_remittance.recipient_attribute ra_$code", "r.id = ra_$code.recipient_id" );
                    $this->db->join("iafb_remittance.attribute a_$code", "ra_$code.attribute_id = a_$code.id AND a_$code.code = '$code'");
                    $this->db->where("ra_$code.hashed_other_value", $attribute->getValue(false)->getHashedValue());
                    $this->db->where("ra_$code.deleted_at", NULL);
                }
            }
        }        
        
        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new RemittanceCompanyRecipientCollection(), $query->num_rows());
        }
        
        return false;
    }
}
