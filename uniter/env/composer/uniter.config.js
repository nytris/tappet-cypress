/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

module.exports = {
    settings: {
        phptojs: {
            // Use sync mode when fetching the Composer autoload files,
            // as the host application's `uniter.config.js` where this is needed will also be loaded synchronously.
            mode: 'sync'
        }
    }
};
