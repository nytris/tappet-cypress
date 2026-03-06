/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import prettierConfig from 'buildbelt/prettier.config.mjs';

export default {
    ...prettierConfig,
    'plugins': ['@trivago/prettier-plugin-sort-imports'],
};
