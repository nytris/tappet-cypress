<?php

/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

/*
 * Fetches the autoload _files_ (a list of paths always required,
 * for defining global functions, which are not autoloaded, for example)
 * of installed Composer dependencies.
 */

return function (string $projectRoot) {
    $autoloadFiles = require $projectRoot . '/vendor/composer/autoload_files.php';

    return array_values($autoloadFiles);
};
