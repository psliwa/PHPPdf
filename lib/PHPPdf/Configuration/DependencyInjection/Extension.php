<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Configuration\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader,
    Symfony\Component\Config\FileLocator;

class Extension implements ExtensionInterface
{
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config/services'));
        $loader->load('glyphs.xml');
    }
    
    public function getNamespace()
    {
        return 'http://ohey.pl/phppdf/schema/dic/'.$this->getAlias();
    }
    
    public function getXsdValidationBasePath()
    {
        return false;
    }
    
    public function getAlias()
    {
        return 'phppdf';
    }
}