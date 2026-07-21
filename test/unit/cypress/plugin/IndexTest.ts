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

            beforeEach(() => {
                on = sinon.stub();
            });

            it('should register the file:preprocessor event', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                plugin(on, validConfig);

                expect(on.firstCall.args[0]).to.equal('file:preprocessor');
            });

            it('should call on with the result of the preprocessor factory', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                plugin(on, validConfig);

                expect(on.firstCall.args[1]).to.equal('stub-preprocessor');
            });

            it('should call the preprocessor factory with webpackOptions', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                plugin(on, validConfig);

                expect(stubPreprocessorFactory).to.have.been.calledOnce;
                expect(
                    stubPreprocessorFactory.firstCall.args[0],
                ).to.have.property('webpackOptions');
            });

            it('should include an instance of UniterPlugin in the webpack plugins', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                plugin(on, validConfig);

                const webpackOptions =
                    stubPreprocessorFactory.firstCall.args[0].webpackOptions;
                expect(webpackOptions.plugins).to.be.an('array');
                expect(webpackOptions.plugins).to.have.length(1);
                expect(StubUniterPlugin).to.have.been.calledWithNew;
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

                    return localOn.secondCall.args[1] as TaskHandlers;
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

                    return localOn.secondCall.args[1] as TaskHandlers;
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

                it('should verify TLS when tappetApiTlsVerification is not "false"', () => {
                    setup({ tappetApiTlsVerification: 'true' });

                    expect(StubAgent).to.have.been.calledOnce;
                    expect(StubAgent.firstCall.args[0]).to.deep.equal({
                        connect: { rejectUnauthorized: true },
                    });
                });

                it('should not verify TLS when tappetApiTlsVerification is "false"', () => {
                    setup({ tappetApiTlsVerification: 'false' });

                    expect(StubAgent).to.have.been.calledOnce;
                    expect(StubAgent.firstCall.args[0]).to.deep.equal({
                        connect: { rejectUnauthorized: false },
                    });
                });
            });
        });
    });
});
