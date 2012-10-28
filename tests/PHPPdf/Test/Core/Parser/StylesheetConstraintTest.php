<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Parser\BagContainer;
use PHPPdf\Core\Parser\StylesheetConstraint,
    PHPPdf\Core\AttributeBag;

class StylesheetConstraintTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $constraint;

    public function setUp()
    {
        $this->constraint = new StylesheetConstraint();
    }

    /**
     * @test
     */
    public function addingConstraints()
    {
        $this->assertEmpty(count($this->constraint));

        $name = 'name';
        $constraint = new StylesheetConstraint();
        $this->constraint->addConstraint($name, $constraint);

        $this->assertEquals(1, count($this->constraint));
    }

    /**
     * @test
     * @dataProvider provider
     */
    public function simpleFind($tag, $query, $classes = array(), $isFound = true)
    {
        $constraint = $this->createContainer(array(
            'someName1' => 'someValue1',
            'someName2' => 'someValue2',
        ), array(), $classes);

        $this->constraint->addConstraint($tag, $constraint);

        $container = $this->constraint->find($query);

        if($isFound)
        {
            $this->assertEquals($constraint->getAll(), $container->getAll());
        }
        else
        {
            $this->assertEquals(array(), $container->getAll());
        }
    }

    public function provider()
    {
        return array(
            array('tag', array(array('tag' => 'tag'))),
            array('any', array(array('tag' => 'tag', 'classes' => array('class'))), array('class')),
            array('tag', array(array('tag' => 'anotherTag', 'classes' => array('class'))), array('class'), false),
            array('tag', array(array('tag' => 'tag', 'classes' => array())), array('class'), false),
        );
    }

    /**
     * @test
     */
    public function findInComplexStructureByOnlyTags()
    {
        $constraint1 = $this->createContainer(array(
            'someName1' => 'someValue1',
            'someName2' => 'someValue2',
        ));

        $constraint2 = $this->createContainer(array(
            'someName2' => 'anotherValue2',
            'someName3' => 'someValue3',
        ));

        $constraint3 = $this->createContainer(array(
            'someName3' => 'anotherValue3',
            'someName4' => 'someValue4',
        ));
        
        $constraint4 = $this->createContainer(array(
            'someName5' => 'someValue5',
        ));

        $constraint5 = $this->createContainer();
        
        $constraint6 = $this->createContainer(array(
            'someName3' => 'yetAnotherValue3',
        ), array(), array('class1'));


        $constraint5->addConstraint('tag2', $constraint6);
        $constraint1->addConstraint('tag2', $constraint2);
        $this->constraint->addConstraint('tag2', $constraint3);
        $this->constraint->addConstraint('tag3', $constraint4);
        
        $this->constraint->addConstraint('tag1', $constraint5);
        $this->constraint->addConstraint('tag1', $constraint1);

        $constraint = $this->constraint->find(array(array('tag' => 'tag1'), array('tag' => 'tag2', 'classes' => array('class1'))));

        $this->assertEquals(array(
            'someName2' => 'anotherValue2',
            'someName3' => 'yetAnotherValue3',
            'someName4' => 'someValue4',
        ), $constraint->getAll());
    }

    private function createContainer(array $attributes = array(), array $complexAttributes = array(), array $classes = array())
    {
        $constraint = new StylesheetConstraint(array_merge($attributes, $complexAttributes));

        foreach($classes as $class)
        {
            $constraint->addClass($class);
        }

        return $constraint;
    }

    /**
     * @test
     */
    public function settingAndGettingClasses()
    {
        $class = 'class';
        $this->assertFalse($this->constraint->hasClass($class));

        $this->constraint->addClass($class);
        $this->assertTrue($this->constraint->hasClass($class));

        $this->constraint->addClass($class);
        $this->assertTrue($this->constraint->hasClass($class));

        $this->constraint->removeClass($class);
        $this->assertFalse($this->constraint->hasClass($class));
    }

    /**
     * @test
     */
    public function settingAndGettingTag()
    {
        $tag = 'tag';
        $this->assertNotEquals($tag, $this->constraint->getTag());
        $this->constraint->setTag($tag);
        $this->assertEquals($tag, $this->constraint->getTag());
    }

    /**
     * @test
     */
    public function unserializedConstraintIsCopyOfSerializedConstraint()
    {
        $this->constraint->setTag('some-tag');
        $this->constraint->addWeight(5);
        $this->constraint->add('someName', 'someValue');
        $this->constraint->add('someName', array('someKey' => 'someValue'));
        $this->constraint->addClass('some-class');

        $childConstraint = new StylesheetConstraint();
        $childConstraint->add('someName', 'someValue');
        $childConstraint->setTag('some-tag');
        $this->constraint->addConstraint('some-constraint', $childConstraint);

        $unserializedConstraint = unserialize(serialize($this->constraint));

        $this->assertStylesheetConstraintEquals($this->constraint, $unserializedConstraint);
    }

    private function assertStylesheetConstraintEquals(StylesheetConstraint $expected, StylesheetConstraint $actual)
    {
        $this->assertEquals($expected->getTag(), $actual->getTag());
        $this->assertEquals($expected->getWeight(), $actual->getWeight());
        $this->assertEquals($expected->getAll(), $actual->getAll());
        $this->assertEquals($expected->getClasses(), $actual->getClasses());

        $actualConstraintChildren = $actual->getConstraints();
        foreach($expected->getConstraints() as $name => $constraint)
        {
            $this->assertStylesheetConstraintEquals($constraint, $actualConstraintChildren[$name]);
        }
    }
    
    /**
     * @test
     */
    public function laterAddedConstraintsOverwritePreviouslyConstraints()
    {
        $constraint1 = $this->createContainer(array(
            'someName1' => 'someValue1',
            'someName2' => 'someValue2',
        ));

        $constraint2 = $this->createContainer(array(
            'someName1' => 'anotherValue1',
            'someName3' => 'someValue3',
        ));
        
        $this->constraint->addConstraint('tag1', $constraint1);
        $this->constraint->addConstraint('tag1', $constraint2);
        
        $constraint = $this->constraint->find(array(array('tag' => 'tag1')));

        $expectedAttributes = array(
            'someName1' => 'anotherValue1',
            'someName2' => 'someValue2',
            'someName3' => 'someValue3',
        );
        
        $actualAttributes = $constraint->getAll();
        ksort($actualAttributes);
        
        $this->assertEquals($expectedAttributes, $actualAttributes);
    }
    
    /**
     * @test
     * 
     * Number of matching children constraints should not affect on weight on parent constraint
     */
    public function weightOfConstraintIsMaxWeightOfChildrenMatchingConstraints()
    {
        $constraint1 = $this->createContainer(array());
        $constraint2 = $this->createContainer(array());
        
        $this->constraint->addConstraint('tag1', $constraint1);
        $this->constraint->addConstraint('tag1', $constraint2);
        
        $constraint = $this->constraint->find(array(array('tag' => 'tag1')));
        
        $this->assertEquals(1, $constraint->getWeight());
    }
}