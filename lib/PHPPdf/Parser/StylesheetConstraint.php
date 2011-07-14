<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Util\AttributeBag,
    PHPPdf\Enhancement\EnhancementBag;

/**
 * Constraints encapsulate Attribute and Enhancement Bag in tree structure.
 *
 * This class provides find method. One single object of StylesheetConstraint may by act as
 * repository of BagContainers.
 *
 * Example:
 * [code]
 * $constraint = ....;
 * $constraint1 = ...;
 * $constraint2 = ...;
 *
 * $constraint2->addClass('someClass');
 *
 * $constraint1->addConstraint('someTag', $constraint2);
 * $constraint->addConstraint('anotherTag', $constraint1);
 *
 * //$bagContainer contains elements from $constraint2 bag container.
 * $bagContainer = $constraint->find(array(
 *      array('tag' => 'someTag'),
 *      array('tag' => 'anotherTag', 'classes' => array('someClass')),
 * ));
 * [/code]
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class StylesheetConstraint extends BagContainer implements \Countable
{
    const TAG_WILDCARD = 'any';

    private $constraints = array();
    private $classes = array();
    private $tag = self::TAG_WILDCARD;

    public function addClass($class)
    {
        $this->classes[$class] = true;
    }

    public function hasClass($class)
    {
        return isset($this->classes[$class]);
    }

    public function getClasses()
    {
        return array_keys($this->classes);
    }

    public function setTag($tag)
    {
        $this->tag = (string) $tag;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function removeClass($class)
    {
        if($this->hasClass($class))
        {
            unset($this->classes[$class]);
        }
    }

    public function addConstraint($name, StylesheetConstraint $constraint)
    {
        $name = (string) $name;
        $constraint->setTag($name);

        $this->constraints[] = $constraint;
    }

    public function getConstraints()
    {
        return $this->constraints;
    }

    public function count()
    {
        return count($this->getConstraints());
    }

    public function find(array $query)
    {
        if(count($query) === 0)
        {
            return new BagContainer($this->getAttributeBag(), $this->getEnhancementBag());
        }

        $containers = array();
        while($queryElement = array_shift($query))
        {
            $tag = $this->getTagFromQueryElement($queryElement);
            $classes = $this->getClassesFromQueryElement($queryElement);

            foreach($this->constraints as $order => $constraint)
            {
                $matchingIndex = $this->getMatchingIndex($constraint, $tag, $classes);
                if($matchingIndex > 0)
                {
                    $container = $constraint->find($query);
                    $container->addWeight($matchingIndex);
                    $container->setOrder($order);
                    $containers[] = $container;
                }
            }
        }

        usort($containers, function($container1, $container2){
            $result = $container1->getWeight() - $container2->getWeight();
            
            if($result == 0)
            {
                $result = $container1->getOrder() - $container2->getOrder();
            }
            
            return $result;
        });

        return BagContainer::merge($containers);
    }

    private function getTagFromQueryElement($queryElement)
    {
        return isset($queryElement['tag']) ? $queryElement['tag'] : null;
    }

    private function getClassesFromQueryElement($queryElement)
    {
        $classes = (array) (isset($queryElement['classes']) ? $queryElement['classes'] : array());

        return $classes;
    }

    private function getMatchingIndex($constraint, $tag, array $classes)
    {
        $matchingIndex = 0;
        $constraintClasses = $constraint->getClasses();
        $classMatchingIndex = 0;
        if(($constraint->getTag() == self::TAG_WILDCARD || $constraint->getTag() == $tag) && (!$constraintClasses || $classMatchingIndex = $this->getClassMatchingIndex($constraint, $classes)))
        {
            $matchingIndex += 1 + $classMatchingIndex;
        }

        return $matchingIndex;
    }

    private function getClassMatchingIndex($constraint, $classes)
    {
        $constraintClasses = $constraint->getClasses();

        $classesCount = count(array_intersect($constraintClasses, $classes));

        $matchingIndex = 0;

        if($classesCount == count($constraintClasses))
        {
            $matchingIndex += $classesCount;
        }

        return $matchingIndex;
    }

    protected function getDataToSerialize()
    {
        $data = parent::getDataToSerialize();

        $data['tag'] = $this->tag;
        $data['classes'] = $this->getClasses();
        $data['constraints'] = $this->constraints;

        return $data;
    }

    protected function restoreDataAfterUnserialize($data)
    {
        parent::restoreDataAfterUnserialize($data);

        $this->setTag($data['tag']);

        foreach((array) $data['classes'] as $class)
        {
            $this->addClass($class);
        }

        foreach((array) $data['constraints'] as $constraint)
        {
            $this->addConstraint($constraint->getTag(), $constraint);
        }
    }
}