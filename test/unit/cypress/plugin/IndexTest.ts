/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import {
    CypressOnFunction,
    createPlugin,
} from '../../../../src/ts/cypress/plugin';
import { expect } from 'chai';
import * as sinon from 'sinon';

type TaskHandlers = Record<
    string,
    (args: Record<string, string>) => Promise<unknown>
>;

describe('cypress/plugin/index', () => {
    describe('createPlugin()', () => {
        let stubPreprocessorFactory: sinon.SinonStub;
        let StubAgent: sinon.SinonStub;
        let StubUniterPlugin: sinon.SinonStub;
        let stubRequest: sinon.SinonStub;

        beforeEach(() => {
            stubPreprocessorFactory = sinon.stub().returns('stub-preprocessor');
            StubAgent = sinon.stub().returns({});
            StubUniterPlugin = sinon.stub().returns({});
            stubRequest = sinon.stub();
        });

        it('should return a function', () => {
            const plugin = createPlugin(
                stubPreprocessorFactory,
                StubUniterPlugin as unknown as new () => object,
            );

            expect(plugin).to.be.a('function');
        });

        describe('returned plugin function', () => {
            let on: sinon.SinonStub<Parameters<CypressOnFunction>>;
            const validConfig = {
                env: {
                    tappetApiBaseUrl: 'https://my-app.example.com',
                    tappetApiKey: 'test-key',
                },
            };

            function createTestPlugin(): ReturnType<typeof createPlugin> {
                return createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );
            }

            beforeEach(() => {
                on = sinon.stub();
            });

            it('should register the file:preprocessor event', () => {
                const plugin = createTestPlugin();

                plugin(on, validConfig);

                expect(on.firstCall.args[0]).to.equal('file:preprocessor');
            });

            it('should call on with the result of the preprocessor factory', () => {
                const plugin = createTestPlugin();

                plugin(on, validConfig);

                expect(on.firstCall.args[1]).to.equal('stub-preprocessor');
            });

            it('should call the preprocessor factory with webpackOptions', () => {
                const plugin = createTestPlugin();

                plugin(on, validConfig);

                expect(stubPreprocessorFactory).to.have.been.calledOnce;
                expect(
                    stubPreprocessorFactory.firstCall.args[0],
                ).to.have.property('webpackOptions');
            });

            it('should include an instance of UniterPlugin in the webpack plugins', () => {
                const plugin = createTestPlugin();

                plugin(on, validConfig);

                const webpackOptions =
                    stubPreprocessorFactory.firstCall.args[0].webpackOptions;
                expect(webpackOptions.plugins).to.be.an('array');
                expect(webpackOptions.plugins).to.have.length(1);
                expect(StubUniterPlugin).to.have.been.calledWithNew;
            });

            it('should register the after:run event', () => {
                const plugin = createTestPlugin();

                plugin(on, validConfig);

                expect(on).to.have.been.calledWith(
                    'after:run',
                    sinon.match.func,
                );
            });

            it('should return the config with experimentalInteractiveRunEvents enabled, so that after:run also fires when closing a project in Cypress "open" mode', () => {
                const plugin = createTestPlugin();

                const result = plugin(on, validConfig);

                expect(result).to.have.property(
                    'experimentalInteractiveRunEvents',
                    true,
                );
            });

            it('should throw if tappetApiBaseUrl is not set in config.env', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                expect(() =>
                    plugin(on, { env: { tappetApiKey: 'test-key' } }),
                ).to.throw(
                    'Tappet Cypress: Cypress environment variable "tappetApiBaseUrl" not set',
                );
            });

            it('should throw if tappetApiKey is not set in config.env', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                expect(() =>
                    plugin(on, {
                        env: {
                            tappetApiBaseUrl: 'https://my-app.example.com',
                        },
                    }),
                ).to.throw(
                    'Tappet Cypress: Cypress environment variable "tappetApiKey" not set',
                );
            });

            function getTaskHandlers(
                localOn: sinon.SinonStub<Parameters<CypressOnFunction>>,
            ): TaskHandlers {
                const call = localOn
                    .getCalls()
                    .find((call) => call.args[0] === 'task');

                return call!.args[1] as TaskHandlers;
            }

            describe('host mapping', () => {
                function setupWithHosts(
                    hosts: Record<string, string>,
                ): TaskHandlers {
                    const localOn = sinon.stub<Parameters<CypressOnFunction>>();
                    createPlugin(
                        stubPreprocessorFactory,
                        StubUniterPlugin as unknown as new () => object,
                        stubRequest,
                    )(localOn, {
                        hosts,
                        env: {
                            tappetApiBaseUrl: 'https://placeholder.example.com',
                            tappetApiKey: 'test-key',
                        },
                    });

                    return getTaskHandlers(localOn);
                }

                async function loadFixture(
                    handlers: TaskHandlers,
                    apiBaseUrl: string,
                ): Promise<void> {
                    await handlers.tappetCypressSetApiBaseUrl({
                        baseUrl: apiBaseUrl,
                    });
                    await handlers.tappetCypressLoadFixture({
                        fixtureClass: 'My\\Fixture',
                        fixturePayload: '{}',
                    });
                }

                beforeEach(() => {
                    stubRequest.resolves({
                        body: {
                            json: () =>
                                Promise.resolve({
                                    serialisation: 'my serialisation',
                                }),
                        },
                    });
                });

                it('should use the original URL when no hosts are configured', async () => {
                    const handlers = setupWithHosts({});

                    await loadFixture(handlers, 'https://myapp.example.com');

                    expect(stubRequest.firstCall.args[0]).to.equal(
                        'https://myapp.example.com/.well-known/tappet/fixture/My--Fixture',
                    );
                });

                it('should not add a Host header when no hosts are configured', async () => {
                    const handlers = setupWithHosts({});

                    await loadFixture(handlers, 'https://myapp.example.com');

                    expect(
                        stubRequest.firstCall.args[1].headers,
                    ).not.to.have.property('Host');
                });

                it('should rewrite the URL for an exact host match', async () => {
                    const handlers = setupWithHosts({
                        'myapp.example.com': '127.0.0.1',
                    });

                    await loadFixture(handlers, 'https://myapp.example.com');

                    expect(stubRequest.firstCall.args[0]).to.equal(
                        'https://127.0.0.1/.well-known/tappet/fixture/My--Fixture',
                    );
                });

                it('should add the Host header for an exact host match', async () => {
                    const handlers = setupWithHosts({
                        'myapp.example.com': '127.0.0.1',
                    });

                    await loadFixture(handlers, 'https://myapp.example.com');

                    expect(
                        stubRequest.firstCall.args[1].headers,
                    ).to.have.property('Host', 'myapp.example.com');
                });

                it('should match a wildcard pattern against a single subdomain level', async () => {
                    const handlers = setupWithHosts({
                        '*.example.com': '127.0.0.1',
                    });

                    await loadFixture(handlers, 'https://myapp.example.com');

                    expect(stubRequest.firstCall.args[0]).to.equal(
                        'https://127.0.0.1/.well-known/tappet/fixture/My--Fixture',
                    );
                    expect(
                        stubRequest.firstCall.args[1].headers,
                    ).to.have.property('Host', 'myapp.example.com');
                });

                it('should match a wildcard pattern against multiple subdomain levels', async () => {
                    const handlers = setupWithHosts({
                        '*.example.com': '127.0.0.1',
                    });

                    await loadFixture(
                        handlers,
                        'https://sub.myapp.example.com',
                    );

                    expect(stubRequest.firstCall.args[0]).to.equal(
                        'https://127.0.0.1/.well-known/tappet/fixture/My--Fixture',
                    );
                    expect(
                        stubRequest.firstCall.args[1].headers,
                    ).to.have.property('Host', 'sub.myapp.example.com');
                });

                it('should not remap the URL when no pattern matches', async () => {
                    const handlers = setupWithHosts({
                        '*.other.com': '127.0.0.1',
                    });

                    await loadFixture(handlers, 'https://myapp.example.com');

                    expect(stubRequest.firstCall.args[0]).to.equal(
                        'https://myapp.example.com/.well-known/tappet/fixture/My--Fixture',
                    );
                    expect(
                        stubRequest.firstCall.args[1].headers,
                    ).not.to.have.property('Host');
                });

                it('should include the port in the Host header for a non-standard port', async () => {
                    const handlers = setupWithHosts({
                        'myapp.example.com': '127.0.0.1',
                    });

                    await loadFixture(
                        handlers,
                        'https://myapp.example.com:8443',
                    );

                    expect(stubRequest.firstCall.args[0]).to.equal(
                        'https://127.0.0.1:8443/.well-known/tappet/fixture/My--Fixture',
                    );
                    expect(
                        stubRequest.firstCall.args[1].headers,
                    ).to.have.property('Host', 'myapp.example.com:8443');
                });
            });

            describe('API base URL storage', () => {
                function setup(
                    env: Record<string, unknown> = {},
                ): TaskHandlers {
                    const localOn = sinon.stub<Parameters<CypressOnFunction>>();
                    createPlugin(
                        stubPreprocessorFactory,
                        StubUniterPlugin as unknown as new () => object,
                        stubRequest,
                    )(localOn, {
                        env: {
                            tappetApiBaseUrl: 'https://initial.example.com',
                            tappetApiKey: 'test-key',
                            ...env,
                        },
                    });

                    return getTaskHandlers(localOn);
                }

                it('should default tappetCypressGetApiBaseUrl to config.env.tappetApiBaseUrl', async () => {
                    const handlers = setup({
                        tappetApiBaseUrl: 'https://initial.example.com',
                    });

                    expect(
                        await handlers.tappetCypressGetApiBaseUrl({}),
                    ).to.equal('https://initial.example.com');
                });

                it('should update the value returned by tappetCypressGetApiBaseUrl after tappetCypressSetApiBaseUrl is called', async () => {
                    const handlers = setup({
                        tappetApiBaseUrl: 'https://initial.example.com',
                    });

                    await handlers.tappetCypressSetApiBaseUrl({
                        baseUrl: 'https://changed.example.com',
                    });

                    expect(
                        await handlers.tappetCypressGetApiBaseUrl({}),
                    ).to.equal('https://changed.example.com');
                });

                it('should use the value set via tappetCypressSetApiBaseUrl for subsequent fixture loads', async () => {
                    stubRequest.resolves({
                        body: {
                            json: () =>
                                Promise.resolve({
                                    serialisation: 'my serialisation',
                                }),
                        },
                    });

                    const handlers = setup();

                    await handlers.tappetCypressSetApiBaseUrl({
                        baseUrl: 'https://changed.example.com',
                    });
                    await handlers.tappetCypressLoadFixture({
                        fixtureClass: 'My\\Fixture',
                        fixturePayload: '{}',
                    });

                    expect(stubRequest.firstCall.args[0]).to.equal(
                        'https://changed.example.com/.well-known/tappet/fixture/My--Fixture',
                    );
                });
            });

            describe('TLS verification', () => {
                function setup(env: Record<string, unknown> = {}): void {
                    const localOn = sinon.stub<Parameters<CypressOnFunction>>();
                    createPlugin(
                        stubPreprocessorFactory,
                        StubUniterPlugin as unknown as new () => object,
                        stubRequest,
                        StubAgent as unknown as typeof import('undici').Agent,
                    )(localOn, {
                        env: {
                            tappetApiBaseUrl: 'https://my-app.example.com',
                            tappetApiKey: 'test-key',
                            ...env,
                        },
                    });
                }

                it('should verify TLS by default when tappetApiTlsVerification is not set', () => {
                    setup();

                    expect(StubAgent).to.have.been.calledOnce;
                    expect(StubAgent.firstCall.args[0]).to.deep.equal({
                        connect: { rejectUnauthorized: true },
                    });
                });

                it('should verify TLS when tappetApiTlsVerification is true', () => {
                    setup({ tappetApiTlsVerification: true });

                    expect(StubAgent).to.have.been.calledOnce;
                    expect(StubAgent.firstCall.args[0]).to.deep.equal({
                        connect: { rejectUnauthorized: true },
                    });
                });

                it('should not verify TLS when tappetApiTlsVerification is false', () => {
                    setup({ tappetApiTlsVerification: false });

                    expect(StubAgent).to.have.been.calledOnce;
                    expect(StubAgent.firstCall.args[0]).to.deep.equal({
                        connect: { rejectUnauthorized: false },
                    });
                });
            });

            describe('deferred purge', () => {
                type PurgeTaskHandlers = {
                    tappetCypressPurgeFixtures: (args: {
                        modelsToPurge: { fixture: string; model: string }[];
                        modelsToDeferredPurge: {
                            fixture: string;
                            model: string;
                        }[];
                    }) => Promise<null>;
                };

                function setup(): {
                    handlers: PurgeTaskHandlers;
                    localOn: sinon.SinonStub<Parameters<CypressOnFunction>>;
                } {
                    const localOn = sinon.stub<Parameters<CypressOnFunction>>();

                    createPlugin(
                        stubPreprocessorFactory,
                        StubUniterPlugin as unknown as new () => object,
                        stubRequest,
                    )(localOn, {
                        env: {
                            tappetApiBaseUrl: 'https://my-app.example.com',
                            tappetApiKey: 'test-key',
                        },
                    });

                    return {
                        handlers: getTaskHandlers(
                            localOn,
                        ) as unknown as PurgeTaskHandlers,
                        localOn,
                    };
                }

                function getAfterRunHandler(
                    localOn: sinon.SinonStub<Parameters<CypressOnFunction>>,
                ): () => Promise<void> {
                    const call = localOn
                        .getCalls()
                        .find((call) => call.args[0] === 'after:run');

                    return call!.args[1] as () => Promise<void>;
                }

                beforeEach(() => {
                    stubRequest.resolves({
                        body: { json: () => Promise.resolve({}) },
                    });
                });

                it('should register an after:run handler', () => {
                    const { localOn } = setup();

                    expect(getAfterRunHandler(localOn)).to.be.a('function');
                });

                it('should still send modelsToPurge immediately via DELETE when tappetCypressPurgeFixtures is called', async () => {
                    const { handlers } = setup();

                    await handlers.tappetCypressPurgeFixtures({
                        modelsToPurge: [
                            {
                                fixture: 'immediate-fixture',
                                model: 'immediate-model',
                            },
                        ],
                        modelsToDeferredPurge: [],
                    });

                    expect(stubRequest).to.have.been.calledOnce;
                    const [url, options] = stubRequest.firstCall.args;
                    expect(url).to.equal(
                        'https://my-app.example.com/.well-known/tappet/fixtures',
                    );
                    expect(options.method).to.equal('DELETE');
                    expect(JSON.parse(options.body)).to.deep.equal([
                        {
                            fixture: 'immediate-fixture',
                            model: 'immediate-model',
                        },
                    ]);
                });

                it('should not send deferred-purge models immediately when tappetCypressPurgeFixtures is called', async () => {
                    const { handlers } = setup();

                    await handlers.tappetCypressPurgeFixtures({
                        modelsToPurge: [],
                        modelsToDeferredPurge: [
                            {
                                fixture: 'deferred-fixture',
                                model: 'deferred-model',
                            },
                        ],
                    });

                    expect(stubRequest).to.have.been.calledOnce;
                    const options = stubRequest.firstCall.args[1];
                    expect(JSON.parse(options.body)).to.deep.equal([]);
                });

                it('should send only the enqueued deferred-purge models in the after:run DELETE request body', async () => {
                    const { handlers, localOn } = setup();

                    await handlers.tappetCypressPurgeFixtures({
                        modelsToPurge: [
                            {
                                fixture: 'immediate-fixture',
                                model: 'immediate-model',
                            },
                        ],
                        modelsToDeferredPurge: [
                            {
                                fixture: 'deferred-fixture',
                                model: 'deferred-model',
                            },
                        ],
                    });
                    stubRequest.resetHistory();

                    await getAfterRunHandler(localOn)();

                    expect(stubRequest).to.have.been.calledOnce;
                    const [url, options] = stubRequest.firstCall.args;
                    expect(url).to.equal(
                        'https://my-app.example.com/.well-known/tappet/fixtures',
                    );
                    expect(options.method).to.equal('DELETE');
                    expect(JSON.parse(options.body)).to.deep.equal([
                        {
                            fixture: 'deferred-fixture',
                            model: 'deferred-model',
                        },
                    ]);
                });

                it('should include the Authorization and Content-Type headers in the after:run DELETE request', async () => {
                    const { handlers, localOn } = setup();

                    await handlers.tappetCypressPurgeFixtures({
                        modelsToPurge: [],
                        modelsToDeferredPurge: [
                            {
                                fixture: 'deferred-fixture',
                                model: 'deferred-model',
                            },
                        ],
                    });
                    stubRequest.resetHistory();

                    await getAfterRunHandler(localOn)();

                    const options = stubRequest.firstCall.args[1];
                    expect(options.headers).to.deep.equal({
                        Authorization: 'Bearer test-key',
                        'Content-Type': 'application/json',
                    });
                });

                it('should send an empty array when no deferred-purge models were ever enqueued', async () => {
                    const { localOn } = setup();

                    await getAfterRunHandler(localOn)();

                    expect(stubRequest).to.have.been.calledOnce;
                    const options = stubRequest.firstCall.args[1];
                    expect(JSON.parse(options.body)).to.deep.equal([]);
                });

                it('should deduplicate a fixture model enqueued for deferred purge multiple times', async () => {
                    const { handlers, localOn } = setup();
                    const model = { fixture: 'f', model: 'm' };

                    await handlers.tappetCypressPurgeFixtures({
                        modelsToPurge: [],
                        modelsToDeferredPurge: [model],
                    });
                    await handlers.tappetCypressPurgeFixtures({
                        modelsToPurge: [],
                        modelsToDeferredPurge: [model],
                    });
                    stubRequest.resetHistory();

                    await getAfterRunHandler(localOn)();

                    const options = stubRequest.firstCall.args[1];
                    expect(JSON.parse(options.body)).to.deep.equal([model]);
                });

                it('should log the error and rethrow when the after:run DELETE request fails', async () => {
                    const { handlers, localOn } = setup();

                    await handlers.tappetCypressPurgeFixtures({
                        modelsToPurge: [],
                        modelsToDeferredPurge: [
                            {
                                fixture: 'deferred-fixture',
                                model: 'deferred-model',
                            },
                        ],
                    });

                    const consoleErrorStub = sinon.stub(console, 'error');
                    const consoleDirStub = sinon.stub(console, 'dir');
                    const failure = new Error('network down');
                    stubRequest.rejects(failure);

                    await expect(
                        getAfterRunHandler(localOn)(),
                    ).to.be.rejectedWith(failure);

                    expect(consoleErrorStub).to.have.been.calledWith(
                        'tappetCypressPurgeFixtures deferred-purge on after:run request() ERROR:',
                    );
                    expect(consoleDirStub).to.have.been.calledWith(failure);
                });
            });
        });
    });
});
