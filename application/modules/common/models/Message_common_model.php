<?php

use Iapps\Common\MessageCommon\IMessageCommonMapper;
use Iapps\Common\MessageCommon\MessageCommon;
use Iapps\Common\Core\Language;

class Message_common_model extends Base_Model implements IMessageCommonMapper{

    public function map(\stdClass $data)
    {
        $obj = new MessageCommon();

        $obj->setId($data->message_common_id);
        if( isset($data->country_language_code) )
        {
            $lang = new Language();
            $lang->setCode($data->country_language_code);
            $obj->setLanguage($lang);
        }
        $obj->setCode($data->code);
        $obj->setMessage($data->message);

        return $obj;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select('id as message_common_id,
                           country_language_code,
                           code,
                           message');
        $this->db->from('iafb_remittance.message_common');
        if(!$deleted)
        {
            $this->db->where('deleted_at', NULL);
        }
        $this->db->where('id', $id);

        $query = $this->db->get();
        if($query->num_rows() >  0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByCode($code, Language $lang)
    {
        $this->db->select('message_common.id as message_common_id,
                           country_language_code,
                           message_common.code,
                           message');
        $this->db->from('iafb_remittance.message_common');
        $this->db->where('message_common.deleted_at', NULL);
        $this->db->where('message_common.code', $code);
        $this->db->where('message_common.country_language_code', $lang->getCode());

        $query = $this->db->get();
        if($query->num_rows() >  0)
        {
            return $this->map($query->row());
        }

        return false;
    }
}