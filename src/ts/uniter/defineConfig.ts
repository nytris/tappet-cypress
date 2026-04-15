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
import dotPhpFactoryImport = require('dotphp');
// eslint-disable-next-line @typescript-eslint/no-require-imports
import phpEvalPlugin = require('phpruntime/src/plugin/eval');

export interface DefineConfigOptions {
    bootstraps?: string[];
    include?: string[];
    stub?: Record<string, null>;
    stubComposerAutoloadFiles?: boolean;
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
    configDir: string,
    relativeRootDir: string,
    {
        bootstraps = [],
        include = [],
        stub = {},
        stubComposerAutoloadFiles = true,
    }: DefineConfigOptions = {},
    dotPhpFactory: typeof dotPhpFactoryImport = dotPhpFactoryImport,
): UniterConfig {
    const bootstrapsDir = path.join(__dirname, 'bootstraps');

    if (stubComposerAutoloadFiles) {
        // Composer autoload files will cause fatal errors if missing from the bundle,
        // as Composer require()'s them - so we must either include them (and any other modules they require)
        // or stub them out for the browser build.

        const dotPhp = dotPhpFactory.create(
            path.join(__dirname, 'env/composer/'),
        );

        const autoloadFilesGetterModule = dotPhp.require(
            path.resolve(__dirname, 'env/composer/get_autoload_files.php'),
        );

        const projectRoot = path.resolve(configDir, relativeRootDir);

        const files = autoloadFilesGetterModule().execute().getNative()(
            projectRoot,
        ) as string[];

        for (const file of files) {
            // Paths must be relative to the project root.
            const relativeFile = path.relative(projectRoot, file);

            include.push(relativeFile);
            stub[relativeFile] = null;
        }
    }

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

                    ...bootstraps,
                ],
                include: [
                    'vendor/composer/**/*.php',
                    '!vendor/composer/pcre/**',
                    'vendor/tappet/tappet/src/{Core,Suite}/**/*.php',
                    'vendor/tappet/cypress/src/php/**/*.php',

                    ...include,
                ],
                rootDir: relativeRootDir,
                stub: {
                    ...stub,
                },
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
