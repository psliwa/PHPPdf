<?php

namespace PHPPdf\Parser;

use PHPPdf\Enhancement\EnhancementBag,
    PHPPdf\Util\AttributeBag;

/**
 * Class to encapsulate two bags: AttributeBag and EnhancementBag
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class BagContainer
{
    private $attributeBag;
    private $enhancementBag;
    private $weight;

    public function __construct(AttributeBag $attributeBag = null, EnhancementBag $enhancementBag = null, $weight = 0)
    {
        if($attributeBag === null)
        {
            $attributeBag = new AttributeBag();
        }

        if($enhancementBag === null)
        {
            $enhancementBag = new EnhancementBag();
        }

        $this->attributeBag = $attributeBag;
        $this->enhancementBag = $enhancementBag;
        $this->weight = (double) $weight;
    }

    /**
     * @return EnhancementBag
     */
    public function getEnhancementBag()
    {
        return $this->enhancementBag;
    }

    /**
     * @return AttributeBag
     */
    public function getAttributeBag()
    {
        return $this->attributeBag;
    }

    public function setAttributeBag(AttributeBag $attributeBag)
    {
        $this->attributeBag = $attributeBag;
    }

    public function setEnhancementBag(EnhancementBag $enhancementBag)
    {
        $this->enhancementBag = $enhancementBag;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function addWeight($weight)
    {
        $this->weight += $weight;
    }

    /**
     * Marge couple of BagContainers into one object. 
     * 
     * Result of merging always is BagContainer.
     *
     * @param array $containers
     * @return BagContainer Result of merging
     */
    public static function merge(array $containers)
    {
        $attributeBags = array();
        $enhancementBags = array();

        $weight = 0;
        foreach($containers as $container)
        {
            $weight += $container->getWeight();
            $attributeBags[] = $container->getAttributeBag();
            $enhancementBags[] = $container->getEnhancementBag();
        }

        return new BagContainer(AttributeBag::merge($attributeBags), EnhancementBag::merge($enhancementBags), $weight);
    }
}