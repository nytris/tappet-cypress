/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import * as uniterPlugin from './plugin/';
import path from 'path';

// eslint-disable-next-line @typescript-eslint/no-require-imports
import phpEvalPlugin = require('phpruntime/src/plugin/eval');

export interface DefineConfigOptions {
    include?: string[];
}

export interface PhpifySettings {
    bootstraps?: string[];
    include?: string[];
    rootDir?: string;
    stub?: Record<string, null>;
}

export interface PhpCoreSettings {
    stackCleaning?: boolean;
}

export interface PhpToJsSettings {
    lineNumbers?: boolean;
    mode?: string;
    stackCleaning?: boolean;
}

export interface UniterSettings {
    phpcore?: PhpCoreSettings;
    phpify?: PhpifySettings;
    phptojs?: PhpToJsSettings;
}

export interface UniterConfig {
    plugins?: unknown[];
    settings?: UniterSettings;
}

/**
 * Creates the Uniter configuration for Tappet Cypress.
 */
export function defineConfig(
    relativeRootDir: string,
    { include = [] }: DefineConfigOptions = {},
): UniterConfig {
    const bootstrapsDir = path.join(__dirname, 'bootstraps');

    return {
        plugins: [
            // Install PHP eval(...) support.
            phpEvalPlugin,

            uniterPlugin,
        ],
        settings: {
            phpcore: {
                // Make stack frames that come from PHP code that was transpiled to JavaScript
                // appear more cleanly in the stack traces that Cypress displays.
                stackCleaning: true,
            },
            phpify: {
                bootstraps: [
                    path.join(bootstrapsDir, 'stub.php'),
                    // Pull in Composer's autoloader.
                    'vendor/autoload.php',
                    path.join(bootstrapsDir, 'bootstrap.php'),
                ],
                include: [
                    'tests/tappet/app/**/*.php',
                    'vendor/autoload.php',
                    'vendor/composer/**/*.php',
                    '!vendor/composer/pcre/**',
                    'vendor/nytris/tappet/**/*.php',
                    'vendor/nytris/tappet-cypress/**/*.php',

                    ...include,
                ],
                rootDir: relativeRootDir,
            },
            phptojs: {
                lineNumbers: true,
                mode: 'async',
                // As above.
                stackCleaning: true,
            },
        },
    };
}
