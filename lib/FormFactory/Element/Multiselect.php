<?php

namespace FormFactory\Element;

class MultiSelect extends \Zend_Form_Element_Multiselect
{

    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);
    }

}
