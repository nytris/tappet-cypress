/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import buildbeltConfig from 'buildbelt/eslint.config.mjs';

export default [
    ...buildbeltConfig.map((config) => ({
        ...config,
        files: [
            '{src/ts,test}/**/*.{js,jsx,mjs,mts,ts,tsx}',
            '*.{js,jsx,mjs,mts,ts,tsx}',
        ],
        rules: {
            ...config.rules,
            // Allow TypeScript's any type where needed for flexibility.
            '@typescript-eslint/no-explicit-any': 'off',
        },
    })),
    {
        files: ['test/**/*.{js,jsx,mjs,mts,ts,tsx}'],
        rules: {
            // Allow assertion chains such as `.to.be.null`.
            '@typescript-eslint/no-unused-expressions': 'off',
        },
    },
];
