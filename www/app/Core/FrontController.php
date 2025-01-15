<?php

namespace Com\Daw2\Core;

use Steampixel\Route;

class FrontController
{
    public static function main()
    {        
        Route::pathNotFound(
            function () {

            }
        );

        Route::methodNotAllowed(
            function () {

            }
        );
        
        Route::run();
    }
}
