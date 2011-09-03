<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node\Behaviour;

use PHPPdf\Engine\GraphicsContext,
    PHPPdf\Node\Node;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Bookmark extends Behaviour
{
    private $name;
    
    public function __construct($name)
    {
        $this->name = (string) $name;
    }

    protected function doAttach(GraphicsContext $gc, Node $node)
    {
        $parentBookmarkIdentifier = $this->getParentBookmarkIdentifier($node);
        $gc->addBookmark($this->getUniqueId(), $this->name, $node->getFirstPoint()->getY(), $parentBookmarkIdentifier);
        $this->setPassive(true);
    }
    
    private function getParentBookmarkIdentifier(Node $node)
    {
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
}