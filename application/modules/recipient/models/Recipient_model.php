<?php

use Iapps\RemittanceService\Recipient\IRecipientDataMapper;
use Iapps\RemittanceService\Recipient\Recipient;
use Iapps\RemittanceService\Recipient\RecipientCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\RemittanceService\ValueObject\EncryptedFieldFactory;

class Recipient_model extends Base_Model
                      implements IRecipientDataMapper{

    public function map(stdClass $data)
    {
        $encrypted_code = EncryptedFieldFactory::build();

        $entity = new Recipient();

        if( isset($data->recipient_id) )
            $entity->setId($data->recipient_id);

        if( isset($data->user_profile_id) )
            $entity->setUserProfileId($data->user_profile_id);

        if( isset($data->recipient_type) )
            $entity->setRecipientType($data->recipient_type);

        if( isset($data->recipient_user_profile_id) )
            $entity->setRecipientUserProfileId($data->recipient_user_profile_id);

        if( isset($data->recipient_dialing_code) )
            $entity->getRecipientDialingCode()->setEncryptedValue($data->recipient_dialing_code);

        if( isset($data->recipient_mobile_number) )
            $entity->getRecipientMobileNumber()->setEncryptedValue($data->recipient_mobile_number);

        if( isset($data->recipient_alias) )
            $entity->setRecipientAlias($data->recipient_alias);

        if( isset($data->photo_image_url) ){
            $encrypted_code->setEncryptedValue($data->photo_image_url);
            if( $encrypted_code->getValue() )
                $entity->setPhotoImageUrl($encrypted_code->getValue());
        }

        if( isset($data->is_active) )
            $entity->setIsActive($data->is_active);

        if( isset($data->is_international) )
            $entity->setIsInternational($data->is_international);

        if( isset($data->last_sent_at))
            $entity->setLastSentAt(IappsDateTime::fromUnix($data->last_sent_at));

        if( isset($data->last_edited_at))
            $entity->setLastEditedAt(IappsDateTime::fromUnix($data->last_edited_at));

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
        $this->db->select('id as recipient_id,
                           user_profile_id,
                           recipient_type,
                           recipient_user_profile_id,
                           recipient_dialing_code,
                           recipient_mobile_number,
                           recipient_alias,
                           photo_image_url,
                           is_international,
                           is_active,
                           last_sent_at,
                           last_edited_at,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.recipient');
        if( !$deleted )
            $this->db->where('deleted_at', NULL);
        $this->db->where('id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByUserProfileId($user_profile_id)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as recipient_id,
                           user_profile_id,
                           recipient_type,
                           recipient_user_profile_id,
                           recipient_dialing_code,
                           recipient_mobile_number,
                           recipient_alias,
                           photo_image_url,
                           is_international,
                           is_active,
                           last_sent_at,
                           last_edited_at,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.recipient');
        $this->db->where('deleted_at', NULL);
        $this->db->where('user_profile_id', $user_profile_id);
        $this->db->order_by('created_at', 'desc');
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RecipientCollection(), $total);
        }

        return false;
    }

    public function findByMobileNumber($user_profile_id, $dialing_code, $mobile_number)
    {
        $encrypted_dialing_code = EncryptedFieldFactory::build();
        $encrypted_dialing_code->setValue($dialing_code);

        $encrypted_mobile_number = EncryptedFieldFactory::build();
        $encrypted_mobile_number->setValue($mobile_number);

        $this->db->select('id as recipient_id,
                           user_profile_id,
                           recipient_type,
                           recipient_user_profile_id,
                           recipient_dialing_code,
                           recipient_mobile_number,
                           recipient_alias,
                           photo_image_url,
                           is_international,
                           is_active,
                           last_sent_at,
                           last_edited_at,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.recipient');
        $this->db->where('deleted_at', NULL);
        $this->db->where('user_profile_id', $user_profile_id);
        $this->db->where('hashed_recipient_dialing_code', $encrypted_dialing_code->getHashedValue());
        $this->db->where('hashed_recipient_mobile_number', $encrypted_mobile_number->getHashedValue());

        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RecipientCollection(), $query->num_rows());
        }

        return false;
    }

    public function findByParam(Recipient $recipient, array $recipient_id_arr = NULL, $limit, $page)
    {
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as recipient_id,
                           user_profile_id,
                           recipient_type,
                           recipient_user_profile_id,
                           recipient_dialing_code,
                           recipient_mobile_number,
                           recipient_alias,
                           photo_image_url,
                           is_international,
                           is_active,
                           last_sent_at,
                           last_edited_at,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.recipient');
        $this->db->where('deleted_at', NULL);
        if($recipient_id_arr != NULL)
        {
            $this->db->where_in('id', $recipient_id_arr);
        }
        if($recipient != NULL) {
            if ($recipient->getRecipientUserProfileId()) {
                $this->db->where('recipient_user_profile_id', $recipient->getRecipientUserProfileId());
            }
            if ($recipient->getUserProfileId()) {
                $this->db->where('user_profile_id', $recipient->getUserProfileId());
            }
            if( $recipient->getIsInternational() !== NULL) {
                $this->db->where('is_international', $recipient->getIsInternational());
            }            
            if( !is_null($recipient->getIsActive()) )
                $this->db->where('is_active', $recipient->getIsActive());
            if( $recipient->getRecipientDialingCode()->getHashedValue() )
                $this->db->where('hashed_recipient_dialing_code', $recipient->getRecipientDialingCode()->getHashedValue());
            if( $recipient->getRecipientMobileNumber()->getHashedValue() )
                $this->db->where('hashed_recipient_mobile_number', $recipient->getRecipientMobileNumber()->getHashedValue());
        }

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();

        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RecipientCollection(), $total);
        }

        return false;
    }

    public function insert(Recipient $recipient)
    {
        $this->db->set('id', $recipient->getId());
        $this->db->set('user_profile_id', $recipient->getUserProfileId());
        $this->db->set('recipient_type', $recipient->getRecipientType());
        $this->db->set('recipient_user_profile_id', $recipient->getRecipientUserProfileId());
        $this->db->set('recipient_dialing_code', $recipient->getRecipientDialingCode()->getEncodedValue());
        $this->db->set('hashed_recipient_dialing_code', $recipient->getRecipientDialingCode()->getHashedValue());
        $this->db->set('recipient_mobile_number', $recipient->getRecipientMobileNumber()->getEncodedValue());
        $this->db->set('hashed_recipient_mobile_number', $recipient->getRecipientMobileNumber()->getHashedValue());
        $this->db->set('recipient_alias', $recipient->getRecipientAlias());
        $this->db->set('photo_image_url', $recipient->getPhotoImageUrl() ? $recipient->getPhotoImageUrl()->getUrlEncryptedField()->getEncodedValue() : null);
        $this->db->set('is_international', $recipient->getIsInternational());
        $this->db->set('is_active', $recipient->getIsActive());
        $this->db->set('last_sent_at', $recipient->getLastSentAt()->getUnix());
        $this->db->set('last_edited_at', $recipient->getLastEditedAt()->getUnix());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $recipient->getCreatedBy());

        if( $this->db->insert('iafb_remittance.recipient') )
        {
            return true;
        }

        return false;
    }

    public function update(Recipient $recipient)
    {

        if( $recipient->getRecipientAlias() != NULL ) {

            $this->db->set('recipient_alias', $recipient->getRecipientAlias());
        }

        if( $recipient->getRecipientType() != NULL){

            $this->db->set('recipient_type', $recipient->getRecipientType());
        }

        if( $recipient->getRecipientUserProfileId() != NULL){

            $this->db->set('recipient_user_profile_id', $recipient->getRecipientUserProfileId());
        }
        

        if( $recipient->getRecipientDialingCode()->getValue() != NULL ) {

            $this->db->set('recipient_dialing_code', $recipient->getRecipientDialingCode()->getEncodedValue());
            $this->db->set('hashed_recipient_dialing_code', $recipient->getRecipientDialingCode()->getHashedValue());
        }

        if( $recipient->getRecipientMobileNumber()->getValue() != NULL ) {

            $this->db->set('recipient_mobile_number', $recipient->getRecipientMobileNumber()->getEncodedValue());
            $this->db->set('hashed_recipient_mobile_number', $recipient->getRecipientMobileNumber()->getHashedValue());
        }
        if( $recipient->getPhotoImageUrl() != NULL){
            $this->db->set('photo_image_url', $recipient->getPhotoImageUrl()->getUrlEncryptedField()->getEncodedValue());
        }

        if( !$recipient->getLastSentAt()->isNull() )
            $this->db->set('last_sent_at', $recipient->getLastSentAt()->getUnix());

        if( !$recipient->getLastEditedAt()->isNull() )
            $this->db->set('last_edited_at', $recipient->getLastEditedAt()->getUnix());
		
		if( !is_null($recipient->getIsActive()) )
			$this->db->set('is_active', $recipient->getIsActive());
		
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $recipient->getUpdatedBy());

        $this->db->where('id', $recipient->getId());

        //echo "<pre>"; print_r($this->db->get_compiled_update('iafb_remittance.recipient')); die(" hoho");
        if( $this->db->update('iafb_remittance.recipient') )
        {
            return true;
        }

        return false;
    }

    public function findByHashedMobileNumber($hashed_dialing_code, $hashed_mobile_number)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('id as recipient_id,
                           user_profile_id,
                           recipient_type,
                           recipient_user_profile_id,
                           recipient_dialing_code,
                           recipient_mobile_number,
                           recipient_alias,
                           photo_image_url,
                           is_international,
                           is_active,
                           last_sent_at,
                           last_edited_at,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_remittance.recipient');
        $this->db->where('deleted_at', NULL);
        $this->db->where('hashed_recipient_dialing_code', $hashed_dialing_code);
        $this->db->where('hashed_recipient_mobile_number', $hashed_mobile_number);

        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $query = $this->db->get();

        $this->db->flush_cache();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new RecipientCollection(), $total);
        }

        return false;
    }
}