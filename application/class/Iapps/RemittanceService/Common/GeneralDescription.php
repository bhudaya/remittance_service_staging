<?php

namespace Iapps\RemittanceService\Common;

class GeneralDescription{

    protected $data = array();

    public function add($title, $value)
    {
        $field['title'] = $title;
        $field['value'] = $value;

        $this->data[] = $field;
        return $this;
    }

    public function setArray(array $option)
    {
        $this->data = $option;
    }

    public function setArrayToKeyPair(array $option)
    {
        foreach ($option as $key => $val) {
            $this->add($key, $val);
        }
    }

    public function setJson($option)
    {
        $this->data = json_decode($option, true);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function toJson()
    {
        return json_encode($this->data);
    }
}