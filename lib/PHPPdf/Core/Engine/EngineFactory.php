<?php

namespace PHPPdf\Core\Engine;

interface EngineFactory
{
    public function createEngine($type, array $options = array());
}