<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Core\UnitConverter;
use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\PageContext;
use PHPPdf\Core\Document;

/**
 * Page being able to break
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class DynamicPage extends Page
{
    private $prototype = null;
    private $currentPage = null;
    private $pages = array();
    private $pagesHistory = array();
    private $currentPageNumber = 1;
    private $nodeFormattingMap = array();
    private $numberOfPages = 0;

    public function __construct(Page $prototype = null, UnitConverter $unitConverter = null)
    {
        $this->setPrototypePage($prototype ? $prototype : new Page(array(), $unitConverter));
        static::initializeTypeIfNecessary();
        $this->initialize();
    }
    
    public function markAsFormatted(Node $node)
    {
        $this->nodeFormattingMap[spl_object_hash($node)] = true;
    }
    
    public function isMarkedAsFormatted(Node $node)
    {
        return isset($this->nodeFormattingMap[spl_object_hash($node)]);
    }

    public function getBoundary()
    {
        return $this->getCurrentPage()->getBoundary();
    }

    public function getCurrentPage($createIfNotExists = true)
    {
        if($createIfNotExists && $this->currentPage === null)
        {
            $this->createNextPage();
        }

        return $this->currentPage;
    }

    /**
     * @return PHPPdf\Core\Node\Page
     */
    public function getPrototypePage()
    {
        return $this->prototype;
    }

    public function setPrototypePage(Page $page)
    {
        $this->prototype = $page;
    }

    /**
     * @return Page
     */
    public function createNextPage()
    {
        $this->currentPage = $this->prototype->copy();
        $this->currentPage->setContext(new PageContext($this->currentPageNumber++, $this));
        $this->pages[] = $this->currentPage;
        $this->pagesHistory[] = $this->currentPage;
        $this->numberOfPages++;

        return $this->currentPage;
    }
    
    public function getNumberOfPages()
    {
        return $this->numberOfPages;
    }

    public function copy()
    {
        $copy = parent::copy();
        $copy->prototype = $this->prototype->copy();
        $copy->reset();
        $copy->nodeFormattingMap = array();
        $copy->numberOfPages = 0;

        return $copy;
    }

    public function reset()
    {
        $this->pages = array();
        $this->currentPage = null;
        $this->currentPageNumber = 1;
        $this->numberOfPages = 0;
    }

    public function getPages()
    {
        return $this->pages;
    }
    
    public function removeAllPagesExceptsCurrent()
    {
        $this->pages = $this->currentPage ? array($this->currentPage) : array();
    }
    
    public function getAllPagesExceptsCurrent()
    {
        $pages = $this->pages;
        array_pop($pages);
        
        return $pages;
    }

    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        foreach($this->getPages() as $page)
        {
            $page->collectOrderedDrawingTasks($document, $tasks);
        }
    }

    public function getAttribute($name)
    {
        return $this->getPrototypePage()->getAttribute($name);
    }

    public function setAttribute($name, $value)
    {
        foreach($this->pages as $page)
        {
            $page->setAttribute($name, $value);
        }

        return $this->getPrototypePage()->setAttribute($name, $value);
    }
    
    public function mergeComplexAttributes($name, array $attributes = array())
    {
        $this->prototype->mergeComplexAttributes($name, $attributes);
    }

    protected function getAttributeDirectly($name)
    {
        return $this->getPrototypePage()->getAttributeDirectly($name);
    }

    public function getWidth()
    {
        return $this->getPrototypePage()->getWidth();
    }

    public function getHeight()
    {
        return $this->getPrototypePage()->getHeight();
    }

    protected function getHeader()
    {
        return $this->getPrototypePage()->getHeader();
    }

    protected function getFooter()
    {
        return $this->getPrototypePage()->getFooter();
    }

    public function setHeader(Container $header)
    {
        return $this->getPrototypePage()->setHeader($header);
    }

    public function setFooter(Container $footer)
    {
        return $this->getPrototypePage()->setFooter($footer);
    }

    public function setWatermark(Container $watermark)
    {
        return $this->getPrototypePage()->setWatermark($watermark);
    }

    protected function beforeFormat(Document $document)
    {
        $gc = $this->getGraphicsContextFromSourceDocument($document);
        if($gc)
        {
            $this->setPageSize($gc->getWidth().':'.$gc->getHeight());
        }

        $this->getPrototypePage()->prepareTemplate($document);
    }

    public function getDiagonalPoint()
    {
        return $this->getPrototypePage()->getDiagonalPoint();
    }

    public function getFirstPoint()
    {
        return $this->getPrototypePage()->getFirstPoint();
    }
    
    protected function getDataForSerialize()
    {
        $data = parent::getDataForSerialize();
        $data['prototype'] = $this->prototype;
        
        return $data;
    }
    
    protected function setDataFromUnserialize(array $data)
    {
        parent::setDataFromUnserialize($data);
        
        $this->prototype = $data['prototype'];
    }

    public function flush()
    {
        foreach($this->pages as $page)
        {
            $page->flush();
        }
        
        $this->pages = array();        
        $this->currentPage = null;

        parent::flush();
    }
    
    public function collectUnorderedDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
        foreach($this->getPages() as $page)
        {
            $page->collectUnorderedDrawingTasks($document, $tasks);
        }
    }
    
    public function collectPostDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
        foreach($this->pagesHistory as $page)
        {
            $page->collectPostDrawingTasks($document, $tasks);
        }
    }
}