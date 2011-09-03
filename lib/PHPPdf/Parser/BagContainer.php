<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Enhancement\EnhancementBag,
    PHPPdf\Util\AttributeBag;

/**
 * Class to encapsulate two bags: AttributeBag and EnhancementBag
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class BagContainer implements \Serializable
{
    protected $attributeBag;
    protected $enhancementBag;
    protected $weight;
    protected $order = 0;

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
    
    public function setOrder($order)
    {
        $this->order = (int) $order;
    }
    
    public function getOrder()
    {
        return $this->order;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function addWeight($weight)
    {
        $this->weight += $weight;
    }

    public function serialize()
    {
        return serialize($this->getDataToSerialize());
    }

    protected function getDataToSerialize()
    {
        return array(
            'attributes' => $this->getAttributeBag()->getAll(),
            'enhancements' => $this->getEnhancementBag()->getAll(),
            'weight' => $this->weight,
        );
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        $this->restoreDataAfterUnserialize($data);
    }

    protected function restoreDataAfterUnserialize($data)
    {
        $this->attributeBag = new AttributeBag($data['attributes']);
        $this->enhancementBag = new EnhancementBag($data['enhancements']);
        $this->weight = (float) $data['weight'];
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