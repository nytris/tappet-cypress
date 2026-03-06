/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

// eslint-disable-next-line @typescript-eslint/no-require-imports
import UniterPlugin = require('webpack-uniter-plugin');

/**
 * Type of the preprocessor factory function from @cypress/webpack-preprocessor.
 */
export type WebpackPreprocessorFactory = (options: {
    webpackOptions: {
        plugins: unknown[];
    };
}) => unknown;

/**
 * Type of the Cypress `on` event registration function.
 */
export type CypressOnFunction = (event: string, handler: unknown) => void;

/**
 * Creates a Cypress plugin registration function with injectable dependencies.
 */
export function createPlugin(
    webpackPreprocessor: WebpackPreprocessorFactory,
    UniterPluginCtor: typeof UniterPlugin,
): (on: CypressOnFunction) => void {
    return (on: CypressOnFunction): void => {
        on(
            'file:preprocessor',
            webpackPreprocessor({
                webpackOptions: {
                    plugins: [new UniterPluginCtor()],
                },
            }),
        );
    };
}

/**
 * Registers the Tappet Cypress Webpack preprocessor plugin with Cypress.
 *
 * Usage in `cypress.config.js`:
 * ```js
 * const { register } = require('@tappet/cypress/cypress/plugin');
 *
 * module.exports = defineConfig({
 *   e2e: {
 *     setupNodeEvents(on) {
 *       register(on);
 *     },
 *   },
 * });
 * ```
 */
export function register(on: CypressOnFunction): void {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    createPlugin(require('@cypress/webpack-preprocessor'), UniterPlugin)(on);
}
