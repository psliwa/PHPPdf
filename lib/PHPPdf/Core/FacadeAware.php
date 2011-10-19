<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

/**
 * Interface of type that is aware of Facade existance.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface FacadeAware
{
    public function setFacade(Facade $facade);
}