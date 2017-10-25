<?php

namespace REBELinBLUE\Zxcvbn;

use Illuminate\Support\Facades\Facade;

class ZxcvbnFacade extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'zxcvbn';
    }
}
