/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

declare module 'dotphp' {
    function create(contextDirectory: string): {
        require(filePath: string): ModuleFactory;
    };

    interface Engine {
        execute(): {
            getNative(): (...args: unknown[]) => unknown;
        };
    }

    type ModuleFactory = () => Engine;
}

declare module 'phpruntime/src/plugin/eval' {
    const plugin: unknown;
    export = plugin;
}

declare module '@cypress/webpack-preprocessor' {
    interface WebpackPreprocessorOptions {
        webpackOptions: {
            plugins: unknown[];
        };
    }

    function webpackPreprocessor(options: WebpackPreprocessorOptions): unknown;

    export = webpackPreprocessor;
}

declare module 'webpack-uniter-plugin' {
    class UniterPlugin {
        constructor();
    }

    export = UniterPlugin;
}
