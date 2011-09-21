<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node\Behaviour;

use PHPPdf\Exception\Exception,
    PHPPdf\Node\Manager;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Factory
{
    private $nodeManager;
    
    public function setNodeManager(Manager $manager)
    {
        $this->nodeManager = $manager;
    }
    
    /**
     * @return Behaviour
     */
    public function create($name, $mainArg, array $options = array())
    {
        switch($name)
        {
            case 'href':
                return new GoToUrl($mainArg);
            case 'ref':
                return new GoToInternal($this->nodeManager->get($mainArg));
            case 'bookmark':
                return new Bookmark($mainArg, $options);
            case 'note':
                return new StickyNote($mainArg);
            default:
                throw new Exception(sprintf('Behaviour "%s" dosn\'t exist.', $name));
        }
    }

    public function getSupportedBehaviourNames()
    {
        return array('href', 'ref', 'bookmark', 'note');
    }
}