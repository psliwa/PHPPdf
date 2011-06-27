<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Configuration\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    PHPPdf\Configuration\LoaderImpl as BaseLoader,
    Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of configuration loader using DependencyInjection Container (from Symfony2) for
 * loading glyph factory. This implementation is more flexible than standard PHPPdf\Configuration\LoaderImpl.
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class LoaderImpl extends BaseLoader
{
    private $container;
    
    public function __construct(ContainerInterface $container = null, $enhancementFile = null, $fontFile = null)
    {
        $this->container = $container;
        parent::__construct(null, $enhancementFile, $fontFile);
    }
    
    protected function loadGlyphs()
    {
        if(!$this->container)
        {
            $container = new ContainerBuilder();    
            $extension = new Extension();
            $extension->load(array(), $container);

            $this->container = $container;           
        }

        return $this->container->get('phppdf.glyph_factory');
    }
}