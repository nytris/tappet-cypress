/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import { Agent, request } from 'undici';
import UniterPlugin from 'webpack-uniter-plugin';

// TODO: Only apply this when a certain config option is set - verify TLS by default.
const httpsAgent = new Agent({
    connect: {
        rejectUnauthorized: false,
    },
});

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
): (on: CypressOnFunction, config?: CypressConfig) => void {
    return (on: CypressOnFunction, config: CypressConfig = {}): void => {
        const hosts = config.hosts ?? {};
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

        on(
            'file:preprocessor',
            webpackPreprocessor({
                webpackOptions: {
                    plugins: [new UniterPluginCtor()],
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
            }: {
                modelsToPurge: string;
            }) {
                loadedFixturesByKey.clear();
                loadedMultipleFixturesByKey.clear();

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
 *       register(on, config);
 *     },
 *   },
 * });
 * ```
 */
export function register(
    on: CypressOnFunction,
    config: CypressConfig = {},
): void {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    createPlugin(require('@cypress/webpack-preprocessor'), UniterPlugin)(
        on,
        config,
    );
}
