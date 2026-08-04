/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import path from 'path';
import { Agent, request } from 'undici';
import UniterPlugin from 'webpack-uniter-plugin';

/**
 * Type of the preprocessor factory function from @cypress/webpack-preprocessor.
 */
export type WebpackPreprocessorFactory = (options: {
    webpackOptions: {
        plugins: unknown[];
        cache?: {
            type: 'filesystem';
            cacheDirectory: string;
            buildDependencies: Record<string, string[]>;
        };
    };
}) => unknown;

/**
 * This package's own `package.json`, whose `version` field changes on every release -
 * included as a webpack build dependency so that upgrading `@tappet/cypress` invalidates
 * any previously persisted Webpack filesystem cache, rather than serving stale bundles
 * compiled by an older version of this plugin/loader chain.
 */
const ownPackageJsonPath = path.join(__dirname, '..', '..', 'package.json');

/**
 * Type of the Cypress `on` event registration function.
 */
export type CypressOnFunction = (event: string, handler: unknown) => void;

/**
 * Subset of Cypress config options used by this plugin.
 */
export interface CypressConfig {
    env?: Record<string, unknown>;
    hosts?: Record<string, string>;
}

// A single `*` wildcard matches one or more subdomain labels (including dots),
// mirroring Cypress' own multi-level wildcard host behaviour.
function matchesHostPattern(hostname: string, pattern: string): boolean {
    const regexStr =
        '^' +
        pattern
            .split('.')
            .map((part) =>
                part === '*'
                    ? '.+'
                    : part.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'),
            )
            .join('\\.') +
        '$';
    return new RegExp(regexStr).test(hostname);
}

function resolveHostMapping(
    url: string,
    hosts: Record<string, string>,
): { url: string; extraHeaders: Record<string, string> } {
    const parsed = new URL(url);

    for (const [pattern, target] of Object.entries(hosts)) {
        if (matchesHostPattern(parsed.hostname, pattern)) {
            // Preserve the original Host header so the server knows which
            // virtual host was intended after the IP rewrite.
            const originalHost = parsed.host;

            parsed.hostname = target;

            return {
                url: parsed.toString(),
                extraHeaders: { Host: originalHost },
            };
        }
    }

    return { url, extraHeaders: {} };
}

/**
 * Creates a Cypress plugin registration function with injectable dependencies.
 */
export function createPlugin(
    webpackPreprocessor: WebpackPreprocessorFactory,
    UniterPluginCtor: typeof UniterPlugin,
    requestFn: typeof request = request,
    AgentCtor: typeof Agent = Agent,
): (on: CypressOnFunction, config?: CypressConfig) => CypressConfig {
    return (
        on: CypressOnFunction,
        config: CypressConfig = {},
    ): CypressConfig => {
        const hosts = config.hosts ?? {};
        const apiTlsVerification =
            (config.env?.tappetApiTlsVerification as boolean | undefined) ??
            true;
        const httpsAgent = new AgentCtor({
            connect: {
                rejectUnauthorized: apiTlsVerification,
            },
        });

        const mappedRequest = (
            url: string,
            options?: Parameters<typeof request>[1],
        ): ReturnType<typeof request> => {
            const { url: resolvedUrl, extraHeaders } = resolveHostMapping(
                url,
                hosts,
            );

            return requestFn(resolvedUrl, {
                ...options,
                headers: {
                    ...extraHeaders,
                    ...(options?.headers as Record<string, string> | undefined),
                },
            });
        };

        const webpackCacheDirectory = config.env
            ?.tappetWebpackCacheDirectory as string | undefined;

        on(
            'file:preprocessor',
            webpackPreprocessor({
                webpackOptions: {
                    plugins: [new UniterPluginCtor()],
                    ...(webpackCacheDirectory
                        ? {
                              cache: {
                                  type: 'filesystem',
                                  cacheDirectory: webpackCacheDirectory,
                                  buildDependencies: {
                                      tappetCypress: [ownPackageJsonPath],
                                  },
                              },
                          }
                        : {}),
                },
            }),
        );

        let apiBaseUrl =
            (config.env?.tappetApiBaseUrl as string | undefined) ?? '';
        const apiKey = (config.env?.tappetApiKey as string | undefined) ?? '';

        if (!apiBaseUrl) {
            throw new Error(
                'Tappet Cypress: Cypress environment variable "tappetApiBaseUrl" not set',
            );
        }

        if (!apiKey) {
            throw new Error(
                'Tappet Cypress: Cypress environment variable "tappetApiKey" not set',
            );
        }

        const loadedFixturesByKey = new Map();
        const loadedMultipleFixturesByKey = new Map();
        const deferredPurgeFixtureModelsByKey = new Map<
            string,
            { fixture: string; model: string }
        >();

        /*
         * Purges any fixtures enqueued via tappetCypressPurgeFixtures' `modelsToDeferredPurge`,
         * once the whole Cypress run finishes, rather than after every scenario. Clears the queue
         * first so that a repeat call is a harmless no-op rather than re-sending the same models.
         */
        on('after:run', async (): Promise<void> => {
            const deferredPurgeFixturesPayload = Array.from(
                deferredPurgeFixtureModelsByKey.values(),
            );
            deferredPurgeFixtureModelsByKey.clear();

            return mappedRequest(apiBaseUrl + '/.well-known/tappet/fixtures', {
                dispatcher: httpsAgent,
                method: 'DELETE',
                headers: {
                    Authorization: `Bearer ${apiKey}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(deferredPurgeFixturesPayload),
            })
                .then(() => undefined)
                .catch((error) => {
                    // Intentionally log details to the host console for inspection.
                    console.error(
                        'tappetCypressPurgeFixtures deferred-purge on after:run request() ERROR:',
                    );
                    console.dir(error);

                    throw error;
                });
        });

        on('task', {
            tappetCypressGetApiBaseUrl() {
                return apiBaseUrl;
            },
            tappetCypressSetApiBaseUrl({ baseUrl }: { baseUrl: string }) {
                apiBaseUrl = baseUrl;

                return null;
            },
            async tappetCypressLoadFixture({
                fixtureClass,
                fixturePayload,
            }: {
                fixtureClass: string;
                fixturePayload: string;
            }) {
                const cacheKey = `${fixtureClass}|${fixturePayload}`;

                if (loadedFixturesByKey.has(cacheKey)) {
                    return loadedFixturesByKey.get(cacheKey);
                }

                const response = await mappedRequest(
                    apiBaseUrl +
                        '/.well-known/tappet/fixture/' +
                        fixtureClass.replace(/\\/g, '--'),
                    {
                        dispatcher: httpsAgent,
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
                ).catch((error) => {
                    // Intentionally log details to the host console for inspection.
                    console.error('tappetCypressLoadFixture request() ERROR:');
                    console.dir(error);

                    throw error;
                });

                // Response fixture model's serialisation payload will be JSON-encoded
                // to support special characters.
                const serialisation = (
                    (await response.body.json()) as { serialisation: string }
                ).serialisation;

                loadedFixturesByKey.set(cacheKey, serialisation);

                return serialisation;
            },
            async tappetCypressLoadMultipleFixtures({
                fixturesPayload,
            }: {
                fixturesPayload: string;
            }) {
                const cacheKey = fixturesPayload;

                if (loadedMultipleFixturesByKey.has(cacheKey)) {
                    return loadedMultipleFixturesByKey.get(cacheKey);
                }

                const response = await mappedRequest(
                    apiBaseUrl + '/.well-known/tappet/fixtures',
                    {
                        dispatcher: httpsAgent,
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
                ).catch((error) => {
                    // Intentionally log details to the host console for inspection.
                    console.error(
                        'tappetCypressLoadMultipleFixtures request() ERROR:',
                    );
                    console.dir(error);

                    throw error;
                });

                // Response fixture models' serialisation payload will be JSON-encoded
                // to support special characters.
                const serialisation = (
                    (await response.body.json()) as { serialisation: string }
                ).serialisation;

                loadedMultipleFixturesByKey.set(cacheKey, serialisation);

                return serialisation;
            },
            async tappetCypressPurgeFixtures({
                modelsToPurge,
                modelsToDeferredPurge,
            }: {
                modelsToPurge: {
                    fixture: string;
                    model: string;
                }[];
                modelsToDeferredPurge: {
                    fixture: string;
                    model: string;
                }[];
            }) {
                loadedFixturesByKey.clear();
                loadedMultipleFixturesByKey.clear();

                // If already enqueued, these fixtures will only be deferred-purged once.
                for (const modelToPurge of modelsToDeferredPurge) {
                    deferredPurgeFixtureModelsByKey.set(
                        JSON.stringify(modelToPurge),
                        modelToPurge,
                    );
                }

                await mappedRequest(
                    apiBaseUrl + '/.well-known/tappet/fixtures',
                    {
                        dispatcher: httpsAgent,
                        method: 'DELETE',
                        headers: {
                            Authorization: `Bearer ${apiKey}`,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(modelsToPurge),
                    },
                ).catch((error) => {
                    // Intentionally log details to the host console for inspection.
                    console.error(
                        'tappetCypressPurgeFixtures request() ERROR:',
                    );
                    console.dir(error);

                    throw error;
                });

                return null;
            },
        });

        // For future use when needing to override/provide Cypress config.
        return {};
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
 *     setupNodeEvents(on, config) {
 *       // The return here is important - it allows the plugin to apply config overrides.
 *       return register(on, config);
 *     },
 *   },
 * });
 * ```
 */
export function register(
    on: CypressOnFunction,
    config: CypressConfig = {},
): CypressConfig {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    return createPlugin(require('@cypress/webpack-preprocessor'), UniterPlugin)(
        on,
        config,
    );
}
