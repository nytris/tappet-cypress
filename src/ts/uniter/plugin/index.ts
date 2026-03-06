/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import path from 'path';

/**
 * Path to the PHPCore addon configuration for this plugin.
 *
 * Uniter requires this to be a filesystem path to the config module.
 */
export const phpcore: string = path.join(__dirname, 'phpcore.config.js');
