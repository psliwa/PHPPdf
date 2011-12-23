<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Behaviour;

use PHPPdf\Exception\RuntimeException;
use PHPPdf\Core\Node\Manager;

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
                if(strpos($mainArg, '#') === 0)
                {
                    //anchor detected, go to "ref" section
                    $mainArg = substr($mainArg, 1);
                }
                else
                {
                    return new GoToUrl($mainArg);
                }
            case 'ref':
                return new GoToInternal($this->nodeManager->get($mainArg));
            case 'bookmark':
                return new Bookmark($mainArg, $options);
            case 'note':
                return new StickyNote($mainArg);
            default:
                throw new RuntimeException(sprintf('Behaviour "%s" dosn\'t exist.', $name));
        }
    }

    public function getSupportedBehaviourNames()
    {
        return array('href', 'ref', 'bookmark', 'note');
    }
}