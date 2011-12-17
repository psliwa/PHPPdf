<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

/**
 * Engine factory
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface EngineFactory
{
    /**
     * Create engine
     * 
     * @param string $type Type of engine
     * @param array $options Options of engine
     * 
     * @return Engine
     * 
     * @throws DomainException
     */
    public function createEngine($type, array $options = array());
}