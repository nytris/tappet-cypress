/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */

/**
 * Interface for the Uniter environment object provided to initialiser groups.
 */
export interface UniterEnvironment {
    defineCoercingFunction(
        name: string,
        handler: (...args: unknown[]) => unknown,
    ): void;
}

/**
 * Interface for an initialiser group function.
 */
export type InitialiserGroup = (params: {
    environment: UniterEnvironment;
}) => void;

/**
 * Interface for a Uniter addon configuration.
 */
export interface UniterAddon {
    initialiserGroups: InitialiserGroup[];
}

/**
 * The PHPCore addon configuration for Tappet Cypress.
 *
 * Registers coercing functions that bridge between PHP (transpiled via Uniter)
 * and the Cypress JavaScript environment.
 */
export const addons: UniterAddon[] = [
    {
        initialiserGroups: [
            ({ environment }: { environment: UniterEnvironment }): void => {
                const cypressWindow = window as unknown as Window & {
                    cy: {
                        then(fn: () => unknown): unknown;
                    };
                    Cypress: {
                        config(name: string): unknown;
                        env(name: string): unknown;
                    };
                    describe(name: string, fn: () => void): void;
                    expect: unknown;
                    it: {
                        (name: string, fn: () => unknown): void;
                        skip(name: string, fn: () => unknown): void;
                    };
                };

                const { describe, it, cy, Cypress, fetch } = cypressWindow;

                const apiBaseUrl = Cypress.env('tappetApiBaseUrl');

                if (!apiBaseUrl) {
                    throw new Error(
                        'Tappet Cypress: Cypress environment variable "tappetApiBaseUrl" not set',
                    );
                }

                const apiKey = Cypress.env('tappetApiKey');

                if (!apiKey) {
                    throw new Error(
                        'Tappet Cypress: Cypress environment variable "tappetApiKey" not set',
                    );
                }

                environment.defineCoercingFunction(
                    'tappet_get_base_url',
                    () => {
                        return Cypress.config('baseUrl');
                    },
                );

                environment.defineCoercingFunction(
                    'tappet_get_fixture_api',
                    () => {
                        return {
                            loadFixture: async (
                                fixtureClass: string,
                                fixturePayload: string,
                            ): Promise<string> => {
                                const response = await fetch(
                                    apiBaseUrl +
                                        '/.well-known/tappet/fixture/' +
                                        fixtureClass.replace(/\\/g, '--'),
                                    {
                                        method: 'POST',
                                        headers: {
                                            Authorization: `Bearer ${apiKey}`,
                                            'Content-Type': 'application/json',
                                        },
                                        // JSON-encode the fixture serialisation payload,
                                        // as it may contain special characters.
                                        body: JSON.stringify({
                                            serialisation: fixturePayload,
                                        }),
                                    },
                                );

                                // Response fixture model's serialisation payload will be JSON-encoded
                                // to support special characters.
                                return (await response.json()).serialisation;
                            },
                            loadMultipleFixtures: async (
                                fixturesPayload: string,
                            ): Promise<string> => {
                                const response = await fetch(
                                    apiBaseUrl + '/.well-known/tappet/fixtures',
                                    {
                                        method: 'POST',
                                        headers: {
                                            Authorization: `Bearer ${apiKey}`,
                                            'Content-Type': 'application/json',
                                        },
                                        // JSON-encode the fixture serialisation payload,
                                        // as it may contain special characters.
                                        body: JSON.stringify({
                                            serialisation: fixturesPayload,
                                        }),
                                    },
                                );

                                // Response fixture models' serialisation payload will be JSON-encoded
                                // to support special characters.
                                return (await response.json()).serialisation;
                            },
                            purge: async (
                                modelsToPurge: {
                                    fixture: string;
                                    model: string;
                                }[],
                            ) => {
                                await fetch(
                                    apiBaseUrl + '/.well-known/tappet/fixtures',
                                    {
                                        method: 'DELETE',
                                        headers: {
                                            Authorization: `Bearer ${apiKey}`,
                                        },
                                        body: JSON.stringify(modelsToPurge),
                                    },
                                );
                            },
                        };
                    },
                );

                environment.defineCoercingFunction(
                    'tappet_get_cypress_api',
                    () => {
                        return cy;
                    },
                );

                const filterString = Cypress.env('tappetFilter') as
                    | string
                    | null
                    | undefined;
                const filterRegex = filterString
                    ? new RegExp(filterString)
                    : null;

                environment.defineCoercingFunction(
                    'tappet_get_describe',
                    (modelRepository: unknown) => {
                        return async (module: {
                            getDescription(): Promise<string>;
                            getScenarios(): Promise<
                                {
                                    getDescription(): Promise<string>;
                                    perform(): Promise<void>;
                                }[]
                            >;
                        }) => {
                            const scenarios: {
                                description: string;
                                scenario: {
                                    perform(): Promise<void>;
                                };
                            }[] = [];

                            for (const scenario of await module.getScenarios()) {
                                scenarios.push({
                                    description:
                                        await scenario.getDescription(),
                                    scenario: scenario,
                                });
                            }

                            const moduleDescription =
                                await module.getDescription();
                            const moduleMatchesFilter =
                                filterRegex === null ||
                                filterRegex.test(moduleDescription);

                            describe(moduleDescription, () => {
                                beforeEach(() => {
                                    // TODO: scenario.beforeEach()?
                                    // Perform cleanup inside Mocha beforeEach so that it happens regardless of errors.
                                    cy.then(() =>
                                        (
                                            modelRepository as {
                                                purge(): unknown;
                                            }
                                        ).purge(),
                                    );
                                });

                                for (const {
                                    description,
                                    scenario,
                                } of scenarios) {
                                    const scenarioMatchesFilter =
                                        moduleMatchesFilter ||
                                        filterRegex.test(description);

                                    (scenarioMatchesFilter ? it : it.skip)(
                                        description,
                                        () => {
                                            return cy.then(() =>
                                                scenario.perform(),
                                            );
                                        },
                                    );
                                }
                            });
                        };
                    },
                );

                const cypressProjectRoot = Cypress.config(
                    'projectRoot',
                ) as string;
                const repoRoot = Cypress.config('repoRoot') as string;

                const projectRoot = cypressProjectRoot.startsWith(repoRoot)
                    ? cypressProjectRoot.substring(repoRoot.length + 1)
                    : cypressProjectRoot;

                environment.defineCoercingFunction(
                    'tappet_get_cypress_project_root',
                    () => projectRoot,
                );

                const suiteName = Cypress.env('tappetSuite');

                if (!suiteName) {
                    throw new Error(
                        'Tappet Cypress: Cypress environment variable "tappetSuite" not set',
                    );
                }

                environment.defineCoercingFunction(
                    'tappet_get_suite_name',
                    () => {
                        return suiteName;
                    },
                );
            },
        ],
    },
];
