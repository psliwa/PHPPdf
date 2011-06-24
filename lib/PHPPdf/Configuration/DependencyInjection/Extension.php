<?php

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
        
        $this->loadGlyphs($container);
    }
    
    private function loadGlyphs(ContainerBuilder $container)
    {
        $ids = $container->findTaggedServiceIds('phppdf.glyph');
        
        $glyphFactory = $container->get('phppdf.glyph_factory');
        
        foreach($ids as $id => $def)
        {
            $name = substr($id, strrpos($id, '.')+1);
            $glyphFactory->addPrototype($name, $container->get($id));
        }
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