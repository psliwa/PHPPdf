<?php

namespace PHPPdf\Parser;

use PHPPdf\Cache\Cache;

class CachingStylesheetConstraint extends StylesheetConstraint
{
    private $resultMap = array();
    private $resultMapModified = false;

    public function find(array $query)
    {
        $queryAsString = $this->transformQueryToString($query);

        if(isset($this->resultMap[$queryAsString]))
        {
            $bag = $this->resultMap[$queryAsString];
        }
        else
        {
            $bag = parent::find($query);
            $this->resultMap[$queryAsString] = $bag;
            $this->setResultMapModified(true);
        }

        return $bag;
    }

    private function transformQueryToString(array $query)
    {
        $queryParts = array();
        foreach($query as $queryElement)
        {
            $tag = $queryElement['tag'];
            $classes = $queryElement['classes'];

            $queryParts[] = sprintf('%s.%s', $tag, implode('.', $classes));
        }

        return implode(' ', $queryParts);
    }

    private function setResultMapModified($flag)
    {
        $this->resultMapModified = (bool) $flag;
    }

    public function isResultMapModified()
    {
        return $this->resultMapModified;
    }

    protected function getDataToSerialize()
    {
        $data = parent::getDataToSerialize();

        $data['resultMap'] = $this->resultMap;

        return $data;
    }

    protected function restoreDataAfterUnserialize($data)
    {
        parent::restoreDataAfterUnserialize($data);

        $this->resultMap = $data['resultMap'];
    }
}