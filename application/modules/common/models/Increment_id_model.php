<?php
/**
 * Created by PhpStorm.
 * User: chunyap
 * Date: 26/1/16
 * Time: 11:39 AM
 */

use Iapps\Common\IncrementID\IIncrementIDMapper;
use Iapps\Common\IncrementID\IncrementID;
use Iapps\Common\Core\IappsDateTime;

class increment_id_model extends Base_Model implements IIncrementIDMapper{

    function __construct()
    {
        parent::__construct();

        $this->inc_db = $this->load->database('iafb_remittance', true);
    }

    /*
     * override this function to have a separate db connection
     */
    public function TransStart()
    {
        $this->inc_db->trans_start();
    }

    public function TransRollback()
    {
        $this->inc_db->trans_rollback();
    }

    public function TransComplete()
    {
        $this->inc_db->trans_complete();
    }

    public function map(\stdClass $data)
    {
        $obj = new IncrementID();

        if( isset($data->increment_table_id) )
            $obj->setId($data->increment_table_id);
        if( isset($data->attribute) )
            $obj->setAttribute($data->attribute);
        if( isset($data->value) )
            $obj->setValue($data->value);
        if( isset($data->last_increment_date) )
            $obj->setLastIncrementDate(IappsDateTime::fromUnix($data->last_increment_date));
        if( isset($data->prefix) )
            $obj->setPrefix($data->prefix);
        if( isset($data->suffix) )
            $obj->setSuffix($data->suffix);
        if( isset($data->created_at) )
            $obj->setCreatedAt(IappsDateTime::fromUnix($data->created_at));
        if( isset($data->created_by) )
            $obj->setCreatedBy($data->created_by);
        if( isset($data->updated_at) )
            $obj->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));
        if( isset($data->updated_by) )
            $obj->setUpdatedBy($data->updated_by);
        if( isset($data->deleted_at) )
            $obj->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));
        if( isset($data->deleted_by) )
            $obj->setDeletedBy($data->deleted_by);

        return $obj;
    }

    public function findById($id, $deleted = false)
    {
        $this->inc_db->select('id as increment_table_id,
                               attribute,
                               value,
                               last_increment_date,
                               prefix,
                               suffix,
                               created_at,
                               created_by,
                               updated_at,
                               updated_by,
                               deleted_at,
                               deleted_by');
        $this->inc_db->from('iafb_remittance.increment_table');
        if(!$deleted)
        {
            $this->inc_db->where('deleted_at', NULL);
        }
        $this->inc_db->where('id', $id);

        $query = $this->inc_db->get();
        if($query->num_rows() >  0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByAttribute($attribute)
    {
        $query = $this->inc_db->query("SELECT
                                       id as increment_table_id,
                                       attribute,
                                       value,
                                       last_increment_date,
                                       prefix,
                                       suffix,
                                       created_at,
                                       created_by,
                                       updated_at,
                                       updated_by,
                                       deleted_at,
                                       deleted_by
                                       FROM iafb_remittance.increment_table where attribute = '"
                                        . $attribute . "'
                                       and deleted_at is null
                                       for update" );

        if($query->num_rows() >  0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function updateIncrementNumber(IncrementID $data)
    {
        $this->inc_db->set('value', $data->getValue());
        $this->inc_db->set('last_increment_date',IappsDateTime::now()->getUnix());
        $this->inc_db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->inc_db->set('updated_by', $data->getUpdatedBy());
        $this->inc_db->where('id', $data->getId());
        $this->inc_db->where('deleted_at', NULL);

        if ($this->inc_db->update('iafb_remittance.increment_table'))
        {
            return $this->inc_db->affected_rows();
        }

        return false;
    }

    public function addIncrementNumber(IncrementID $data, $value)
    {
        $this->inc_db->set('value', "value + $value", FALSE);
        $this->inc_db->set('last_increment_date', IappsDateTime::now()->getUnix());
        $this->inc_db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->inc_db->set('updated_by', $data->getUpdatedBy());
        $this->inc_db->where('id', $data->getId());
        $this->inc_db->where('deleted_at', NULL);

        if ($this->inc_db->update('iafb_remittance.increment_table'))
        {
            return $this->inc_db->affected_rows();
        }

        return false;
    }

    public function insert(IncrementID $inc)
    {
        $this->inc_db->set('id', $inc->getId());
        $this->inc_db->set('attribute', $inc->getAttribute());
        $this->inc_db->set('value', $inc->getValue());
        $this->inc_db->set('last_increment_date', $inc->getLastIncrementDate()->getUnix());
        $this->inc_db->set('prefix', $inc->getPrefix());
        $this->inc_db->set('suffix', $inc->getSuffix());
        $this->inc_db->set('created_at', IappsDateTime::now()->getUnix());
        $this->inc_db->set('created_by', $inc->getUpdatedBy());

        if ($this->inc_db->insert('iafb_remittance.increment_table'))
        {
            if($this->inc_db->affected_rows() > 0)
            {
                return $inc;
            }
        }

        return false;
    }
}