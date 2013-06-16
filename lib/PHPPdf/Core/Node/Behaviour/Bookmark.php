<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Behaviour;

use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\Core\Node\Node;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Bookmark extends Behaviour
{
    private static $bookmarks = array();
    
    private $name;
    private $options = array(
        'id' => null,
        'parentId' => null,
    );
    
    public function __construct($name, array $options = array())
    {
        $this->name = (string) $name;

        foreach($options as $optionName => $value)
        {
            if(!in_array($optionName, array_keys($this->options)))
            {
                throw new InvalidArgumentException(sprintf('Option "%s" is not supported by "%s" class.', $optionName, get_class($this)));
            }
            
            $this->options[$optionName] = $value;
        }
    }

    protected function doAttach(GraphicsContext $gc, Node $node)
    {
        $parentBookmarkIdentifier = $this->getParentBookmarkIdentifier($node);
        $firstPoint = self::getFirstPointOf($node);
        $gc->addBookmark($this->getUniqueId(), $this->name, $firstPoint->getY(), $parentBookmarkIdentifier);
        $this->setPassive(true);
    }
    
    private function getParentBookmarkIdentifier(Node $node)
    {
        if($this->options['parentId'])
        {
            return $this->options['parentId'];
        }
        
        for($parent = $node->getParent(); $parent !== null; $parent = $parent->getParent())
        {
            foreach($parent->getBehaviours() as $behaviour)
            {
                if($behaviour instanceof Bookmark)
                {
                    return $behaviour->getUniqueId();
                }
            }
        }
        
        return null;
    }
    
    public function getUniqueId()
    {
        return $this->options['id'] ? : parent::getUniqueId();
    }
}