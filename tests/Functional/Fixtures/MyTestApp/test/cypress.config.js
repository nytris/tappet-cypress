/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

const { defineConfig } = require('cypress');
const { register: tappetPlugin } = require('@tappet/cypress/cypress/plugin');

module.exports = defineConfig({
  e2e: {
    env: {
      tappetSuite: 'my-suite',
      tappetApiKey: 'test-api-key',
    },
    setupNodeEvents(on) {
      tappetPlugin(on);
    },
    specPattern: 'spec/**/*.spec.php',
  },
});
