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
                        env(name: string): unknown;
                    };
                    describe(name: string, fn: () => void): void;
                    expect: unknown;
                    it(name: string, fn: () => unknown): void;
                };

                const { describe, it, cy, Cypress, fetch } = cypressWindow;

                const apiBaseUrl = Cypress.env('tappetBaseUrl');

                if (!apiBaseUrl) {
                    throw new Error(
                        'Tappet Cypress: Cypress environment variable "tappetBaseUrl" not set',
                    );
                }

                environment.defineCoercingFunction(
                    'tappet_get_fixture_api',
                    () => {
                        return {
                            loadFixture: async (
                                fixtureFqcn: string,
                                fixturePayload: string,
                            ) => {
                                return (
                                    await fetch(
                                        apiBaseUrl + '/load/' + fixtureFqcn,
                                        {
                                            method: 'POST',
                                            body: fixturePayload,
                                        },
                                    )
                                ).text();
                            },
                            purge: async (
                                modelsToPurge: {
                                    fixture: string;
                                    model: string;
                                }[],
                            ) => {
                                return (
                                    await fetch(apiBaseUrl + '/purge', {
                                        method: 'POST',
                                        body: JSON.stringify(modelsToPurge),
                                    })
                                ).text();
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

                environment.defineCoercingFunction(
                    'tappet_get_describe',
                    (modelRepository: unknown) => {
                        return async (suite: {
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

                            for (const scenario of await suite.getScenarios()) {
                                scenarios.push({
                                    description:
                                        await scenario.getDescription(),
                                    scenario: scenario,
                                });
                            }

                            describe(await suite.getDescription(), () => {
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
                                    it(description, () => {
                                        return cy.then(() =>
                                            scenario.perform(),
                                        );
                                    });
                                }
                            });
                        };
                    },
                );
            },
        ],
    },
];
