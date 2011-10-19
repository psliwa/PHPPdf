<?php

namespace PHPPdf\Stub\Node;

use PHPPdf\Core\Node\Node;

class StubNode extends Node
{
    public function initialize()
    {
        parent::initialize();
        $this->addAttribute('name-two');
        $this->addAttribute('name', 'value');
    }

    public function setNameTwo($value)
    {
        $this->setAttributeDirectly('name-two', $value.' from setter');
    }
}
