<?php

namespace Iapps\RemittanceService\Attribute;

use Iapps\Common\Core\IappsBaseEntity;

class Attribute extends IappsBaseEntity{

    protected $input_type;
    protected $selection_only;
    protected $code;
    protected $name;
    protected $description;

    public function setInputType($type)
    {
        $this->input_type = $type;
        return $this;
    }

    public function getInputType()
    {
        return $this->input_type;
    }

    public function setSelectionOnly($flag)
    {
        $this->selection_only = $flag;
        return $this;
    }

    public function getSelectionOnly()
    {
        return $this->selection_only;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription($desc)
    {
        $this->description = $desc;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['input_type'] = $this->getInputType();
        $json['code'] = $this->getCode();
        $json['name'] = $this->getName();
        $json['selection_only'] = $this->getSelectionOnly();
        $json['description'] = $this->getDescription();

        return $json;
    }

    public function isSelectionOnly()
    {   
        return ($this->getSelectionOnly() == '1');
    }

    public function equals(Attribute $attribute)
    {
        return ($attribute->getCode() == $this->getCode());
    }
}

