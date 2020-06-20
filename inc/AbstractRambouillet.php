<?php

namespace Rambouillet;

class AbstractRambouillet implements InterfaceRambuillet
{
    /**
     * AbstractRambouillet constructor.
     */
    public function __construct()
    {
        $this->addActions();
    }

    public function addActions()
    {
    }
}
