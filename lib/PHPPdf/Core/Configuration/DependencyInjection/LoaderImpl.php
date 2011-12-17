<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Configuration\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    PHPPdf\Core\Configuration\LoaderImpl as BaseLoader,
    Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of configuration loader using DependencyInjection Container (from Symfony2) for
 * loading node factory. This implementation is more flexible than standard PHPPdf\Core\Configuration\LoaderImpl.
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class LoaderImpl extends BaseLoader
{
    private $container;
    
    public function __construct(ContainerInterface $container = null, $complexAttributeFile = null, $fontFile = null)
    {
        $this->container = $container;
        parent::__construct(null, $complexAttributeFile, $fontFile);
    }
    
    protected function loadNodes()
    {
        return $this->getContainer()->get('phppdf.node_factory');
    }
    
    private function getContainer()
    {
        if(!$this->container)
        {
            $container = new ContainerBuilder();    
            $extension = new Extension();
            $extension->load(array(), $container);
            
            $this->container = $container;
        }
        
        return $this->container;
    }
}