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
 * Proxy interface for the PHP-land TransitionLog object received via tappet_init_transition_log().
 */
interface TransitionLogProxy {
    reset(): Promise<void>;
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
                    beforeEach: (fn: () => void) => void;
                    cy: {
                        task(
                            name: string,
                            payload?: unknown,
                        ): {
                            then<TResult>(
                                onFulfilled: (result: TResult) => unknown,
                                onRejected?: (error: unknown) => unknown,
                            ): unknown;
                        };
                        then(fn: () => unknown): unknown;
                        visit(url: string): unknown;
                        wrap(value: unknown): {
                            should(fn: (value: unknown) => void): unknown;
                        };
                    };
                    Cypress: {
                        config(name: string, value?: unknown): unknown;
                        env(name: string): unknown;
                        isCy(value: unknown): boolean;
                        on(
                            event: string,
                            fn: (...args: unknown[]) => void,
                        ): void;
                    };
                    Error: typeof Error;
                    describe(name: string, fn: () => void): void;
                    expect: unknown;
                    it: {
                        (name: string, fn: () => unknown): void;
                        skip(name: string, fn: () => unknown): void;
                    };
                };

                const { beforeEach, describe, it, cy, Cypress } = cypressWindow;

                cypressWindow.Error.stackTraceLimit = 50;

                /*
                 * Storage of the current API base URL lives Node-side (see cypress/plugin/index.ts),
                 * alongside the fixture caches, so that it survives cases where this spec's own JS
                 * state doesn't (e.g. a fresh page load after the AUT is re-hosted under a
                 * fixture-generated subdomain). tappet_get_fixture_api_base_url()/
                 * tappet_set_fixture_api_base_url() are only ever called lazily, once a Cypress
                 * command queue is guaranteed to already exist - see ProxyConfiguration's use in
                 * uniter/bootstraps/bootstrap.php, which defers calling either of these until
                 * Configuration::getApiBaseUrl()/setApiBaseUrl() is actually invoked (e.g. while
                 * loading a fixture), rather than eagerly at bootstrap time.
                 */
                environment.defineCoercingFunction(
                    'tappet_get_fixture_api_base_url',
                    async () => {
                        return await new Promise((resolve) => {
                            cy.task('tappetCypressGetApiBaseUrl').then(resolve);
                        });
                    },
                );

                environment.defineCoercingFunction(
                    'tappet_set_fixture_api_base_url',
                    async (baseUrl) => {
                        await new Promise((resolve) => {
                            cy.task('tappetCypressSetApiBaseUrl', {
                                baseUrl,
                            }).then(resolve);
                        });
                    },
                );

                environment.defineCoercingFunction(
                    'tappet_get_base_url',
                    () => {
                        return Cypress.config('baseUrl');
                    },
                );

                environment.defineCoercingFunction(
                    'tappet_set_base_url',
                    (baseUrl) => {
                        Cypress.config('baseUrl', baseUrl);
                    },
                );

                /*
                 * Break the deadlock between Cypress' command queue and Promise resolution:
                 * a cy.then(...) callback that returns a pending Promise blocks the command
                 * queue until that Promise settles, but if the Promise's resolution itself
                 * depends on further Cypress commands (e.g. enqueued by a PHP coroutine that
                 * paused mid-callback), those commands are stuck behind the very .then(...)
                 * that's waiting on them - a deadlock. So instead of returning the pending
                 * Promise directly, poll its settlement via a chain of small, bounded
                 * cy.then(...) commands, each of which returns immediately - leaving room in
                 * the queue for anything enqueued asynchronously in between polls to run.
                 */
                const addToCypressCommandQueueAllowingReentry = (
                    callback: () => unknown,
                ) => {
                    cy.then(() => {
                        const result: unknown | Promise<unknown> = callback();

                        if (
                            !result ||
                            typeof (result as Promise<unknown>).then !==
                                'function' ||
                            Cypress.isCy(result)
                        ) {
                            return result;
                        }

                        let isSettled = false;
                        let returnValue: unknown | null = null;
                        let thrownError: Error | null = null;

                        (result as Promise<unknown>).then(
                            (result) => {
                                isSettled = true;
                                returnValue = result;
                            },
                            (error) => {
                                isSettled = true;
                                thrownError = error;
                            },
                        );

                        const check = () => {
                            if (!isSettled) {
                                cy.then(async () => {
                                    await new Promise((resolve) => {
                                        setTimeout(resolve, 1);
                                    });

                                    await check();
                                });

                                return;
                            }

                            if (thrownError) {
                                throw thrownError;
                            }

                            return returnValue;
                        };

                        cy.then(check);
                    });
                };

                environment.defineCoercingFunction(
                    'tappet_get_fixture_api',
                    () => {
                        return {
                            loadFixture: async (
                                fixtureClass: string,
                                fixturePayload: string,
                            ): Promise<string> => {
                                return await new Promise((resolve) => {
                                    cy.task('tappetCypressLoadFixture', {
                                        fixtureClass,
                                        fixturePayload,
                                    }).then(resolve);
                                });
                            },
                            loadMultipleFixtures: async (
                                fixturesPayload: string,
                            ): Promise<string> => {
                                return await new Promise((resolve) => {
                                    cy.task(
                                        'tappetCypressLoadMultipleFixtures',
                                        {
                                            fixturesPayload,
                                        },
                                    ).then(resolve);
                                });
                            },
                            purge: async (
                                modelsToPurge: {
                                    fixture: string;
                                    model: string;
                                }[],
                                modelsToDeferredPurge: {
                                    fixture: string;
                                    model: string;
                                }[],
                            ) => {
                                await new Promise((resolve) => {
                                    cy.task('tappetCypressPurgeFixtures', {
                                        modelsToPurge,
                                        modelsToDeferredPurge,
                                    }).then(resolve);
                                });
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
                    string | null | undefined;
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
                                    // Reset the PHP-land transition log before each test.
                                    addToCypressCommandQueueAllowingReentry(
                                        async () => {
                                            await transitionLog?.reset();
                                        },
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
                                            addToCypressCommandQueueAllowingReentry(
                                                async () => {
                                                    /*
                                                     * Purge fixtures once this scenario finishes, pass or
                                                     * fail, rather than in the next test's beforeEach() -
                                                     * relying on the *next* test's beforeEach() would never
                                                     * purge the fixtures loaded by the very last scenario to
                                                     * run in the whole suite, since there is no next test.
                                                     */
                                                    try {
                                                        await scenario.perform();
                                                    } finally {
                                                        await (
                                                            modelRepository as {
                                                                purge(): unknown;
                                                            }
                                                        ).purge();
                                                    }
                                                },
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

                // PHP-land TransitionLog instance, set via tappet_init_transition_log().
                let transitionLog: TransitionLogProxy | null = null;

                // Handlers called before each AUT window loads, registered via PHP bootstrap.
                const windowBeforeLoadHandlers: ((win: Window) => void)[] = [];

                // Handlers called when each AUT window fully loads, registered via PHP bootstrap.
                const windowLoadHandlers: ((win: Window) => void)[] = [];

                // Invoke PHP-land window before-load handlers before each new AUT window load.
                Cypress.on('window:before:load', (win) => {
                    const autWindow = win as Window & typeof globalThis;

                    windowBeforeLoadHandlers.forEach((handler) =>
                        handler(autWindow),
                    );
                });

                // Invoke PHP-land window load handlers whenever the AUT window fully loads.
                Cypress.on('window:load', (win) => {
                    const autWindow = win as Window & typeof globalThis;

                    windowLoadHandlers.forEach((handler) => handler(autWindow));
                });

                environment.defineCoercingFunction(
                    'tappet_add_window_beforeload_handler',
                    (handler) => {
                        windowBeforeLoadHandlers.push(
                            handler as (win: Window) => void,
                        );
                    },
                );

                environment.defineCoercingFunction(
                    'tappet_add_window_load_handler',
                    (handler) => {
                        windowLoadHandlers.push(
                            handler as (win: Window) => void,
                        );
                    },
                );

                // Receives the PHP-land TransitionLog instance so that the beforeEach() handler
                // can call reset() on it before each test.
                environment.defineCoercingFunction(
                    'tappet_init_transition_log',
                    (log) => {
                        transitionLog = log as TransitionLogProxy;
                    },
                );
            },
        ],
    },
];
