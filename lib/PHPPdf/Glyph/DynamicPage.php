<?php

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\Page,
    PHPPdf\Glyph\PageContext,
    PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class DynamicPage extends Page
{
    private $prototype = null;
    private $currentPage = null;
    private $pages = array();

    public function __construct(Page $prototype = null)
    {
        $this->prototype = $prototype ? $prototype : new Page();

        $this->initialize();
    }

    public function getBoundary()
    {
        return $this->getCurrentPage()->getBoundary();
    }

    public function getCurrentPage()
    {
        if($this->currentPage === null)
        {
            $this->createNextPage();
        }
        
        return $this->currentPage;
    }

    /**
     * @return PHPPdf\Glyph\Page
     */
    public function getPrototypePage()
    {
        return $this->prototype;
    }

    /**
     * @return PHPPdf\Glyph\Page
     */
    public function createNextPage()
    {
        $this->currentPage = $this->prototype->copy();

        $index = count($this->pages);
        $this->currentPage->setContext(new PageContext($index+1, $this));
        $this->pages[$index] = $this->currentPage;

        return $this->currentPage;
    }

    public function copy()
    {
        $copy = parent::copy();
        $copy->prototype = $this->prototype->copy();
        $copy->reset();

        return $copy;
    }

    public function reset()
    {
        $this->pages = array();
        $this->currentPage = null;
    }

    public function getPages()
    {
        return $this->pages;
    }

    protected function doDraw(Document $document)
    {
        $this->splitChildrenIntoPages();

        foreach($this->getPages() as $page)
        {
            $tasks = $page->getDrawingTasks($document);

            foreach($tasks as $task)
            {
                $this->addDrawingTask($task);
            }
        }
    }

    private function splitChildrenIntoPages()
    {
        $prototype = $this->getPrototypePage();
        $currentPageHeight = $prototype->getHeight() + $prototype->getMarginBottom();
        $pageEnd = $this->getCurrentPage()->getBoundary()->getDiagonalPoint()->getY();

        $totalTranslation = 0;
        foreach($this->getChildren() as /* @var $child PHPPdf\Glyph\Glyph */ $child)
        {
            $originalChild = $child;
            $child->translate(0, -$totalTranslation);
            $translation = 0;
            while($child)
            {               
                list($xStart, $yStart) = $child->getBoundary()->getFirstPoint()->toArray();
                list($xEnd, $yEnd) = $child->getBoundary()->getDiagonalPoint()->toArray();
                $originalHeight = $child->getHeight();

                if($yEnd < $pageEnd)
                {
                    $splitLine = $translation + $yStart - $prototype->getMarginBottom();
                    $splitedGlyph = $splitLine > 0 ? $child->split($splitLine) : null;

                    list(,$yChildStart) = $child->getBoundary()->getFirstPoint()->toArray();
                    list(,$yChildEnd) = $child->getBoundary()->getDiagonalPoint()->toArray();

                    $heightAfterSplit = $splitedGlyph ? $splitedGlyph->getHeight() + $child->getHeight() : $child->getHeight();

                    if($splitedGlyph)
                    {
                        list($xStart, $yStart) = $splitedGlyph->getBoundary()->getFirstPoint()->toArray();
                        $this->getCurrentPage()->add($child);
                        $child->translate(0, -$translation);
                        $child = $splitedGlyph;
                    }

                    $translation += $currentPageHeight - ($translation + $yStart);
                    $this->createNextPage();

                    $this->getCurrentPage()->add($child);
                    $child->translate(0, -$translation);

                    $totalTranslation += $translation;
                    $translation = 0;
                    $originalChild = null;
                }
                else
                {
                    $child = null;
                    if($originalChild)
                    {
                        $this->getCurrentPage()->add($originalChild);
                        $originalChild->translate(0, -$translation);
                        $originalChild = null;
                    }
                }
            }
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

    public function preFormat(Document $document)
    {
        $this->getPrototypePage()->prepareTemplate($document);
    }
}