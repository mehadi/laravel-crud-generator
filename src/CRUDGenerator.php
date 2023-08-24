<?php

namespace Mehadi\CRUDGenerator;

class CRUDGenerator
{

    public static function event(string $name)
    {
        return $name . config('crudconfig.option_name');
    }

}
