<?php


namespace PHPPdf\Test\Helper;

use PHPPdf\Core\Node\Node;

class NodeBuilder
{
    private $attributes = array();
    private $parentBuilder;
    private $childBuilders = array();
    private $parentContainer;
    private $class = 'PHPPdf\Test\Helper\Container';

    /**
     * @return NodeBuilder
     */
    public static function create()
    {
        return new self();
    }

    private function __construct(NodeBuilder $parentBuilder = null)
    {
        $this->parentBuilder = $parentBuilder;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return NodeBuilder
     */
    public function attr($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * @param $class
     *
     * @return NodeBuilder
     */
    public function nodeClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @param array $attrs
     *
     * @return NodeBuilder
     */
    public function attrs(array $attrs)
    {
        $this->attributes = $attrs + $this->attributes;
        return $this;
    }

    /**
     * @return NodeBuilder
     */
    public function parent()
    {
        $this->parentContainer = new self($this);

        return $this->parentContainer;
    }

    /**
     * @return NodeBuilder
     */
    public function end()
    {
        return $this->parentBuilder;
    }

    /**
     * @return NodeBuilder
     */
    public function child()
    {
        $builder = new self($this);
        $this->childBuilders[] = $builder;

        return $builder;
    }

    /**
     * @return Node
     */
    public function getNode()
    {
        $class = $this->class;
        $container = new $class($this->attributes);

        if($this->parentContainer)
        {
            $this->parentContainer->getNode()->add($container);
        }

        foreach($this->childBuilders as $childBuilder)
        {
            $container->add($childBuilder->getNode());
        }

        return $container;
    }
}