<?php


namespace PHPPdf\Test\Helper;


class Image extends \PHPPdf\Core\Node\Image
{
    public function setAttribute($name, $value)
    {
        if($name === 'original-height')
        {
            $this->originalHeight = $value;
        }
        elseif($name === 'original-width')
        {
            $this->originalWidth = $value;
        }
        else
        {
            parent::setAttribute($name, $value);
        }
    }
} 