/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import {
    UniterAddon,
    UniterEnvironment,
    addons,
} from '../../../../src/ts/uniter/plugin/phpcore.config';
import { expect } from 'chai';
import * as sinon from 'sinon';

/**
 * Polls `predicate` (using real timers) until it returns true, or fails the test after `timeoutMs`.
 *
 * Needed because `addToCypressCommandQueueAllowingReentry()`'s retry loop schedules
 * real `setTimeout`s between polls, so tests exercising it must wait in real time rather than
 * relying on synchronous execution or fake timers (which would not advance the underlying
 * native Promise microtask queue in lockstep in the way the production code depends on).
 */
const waitUntil = async (
    predicate: () => boolean,
    timeoutMs = 500,
): Promise<void> => {
    const start = Date.now();

    while (!predicate()) {
        if (Date.now() - start > timeoutMs) {
            throw new Error(
                'waitUntil(...) timed out waiting for the predicate to become true',
            );
        }

        await new Promise((resolve) => setTimeout(resolve, 5));
    }
};

describe('uniter/plugin/phpcore.config', () => {
    let cypressConfig: sinon.SinonStub;
    let cypressCy: {
        task: sinon.SinonStub;
        then: sinon.SinonStub;
        visit: sinon.SinonStub;
        wrap: sinon.SinonStub;
    };
    let cypressEnv: sinon.SinonStub;
    let cypressIsCy: sinon.SinonStub;
    let cypressOn: sinon.SinonStub;
    let errorMock: { stackTraceLimit: number };

    beforeEach(() => {
        cypressConfig = sinon.stub();
        cypressCy = {
            task: sinon.stub(),
            then: sinon.stub(),
            visit: sinon.stub(),
            wrap: sinon.stub(),
        };
        cypressEnv = sinon.stub();
        cypressIsCy = sinon.stub().returns(false);
        cypressOn = sinon.stub();

        cypressConfig.withArgs('projectRoot').returns('/my/project/ui-tests');
        cypressConfig.withArgs('repoRoot').returns('/my/project');

        cypressEnv.withArgs('tappetSuite').returns('my-suite');

        // cy.task() returns a thenable so PHP can .then() the result.
        cypressCy.task.returns({
            then: sinon.stub().callsFake((fn) => fn('result-serialisation')),
        });

        errorMock = { stackTraceLimit: 0 };

        // Set up minimal window globals for Cypress context.
        (global as { [key: string]: unknown }).window = {
            beforeEach: sinon.stub(),
            Cypress: {
                config: cypressConfig,
                env: cypressEnv,
                isCy: cypressIsCy,
                on: cypressOn,
            },
            cy: cypressCy,
            describe: sinon.stub(),
            Error: errorMock,
            expect: sinon.stub(),
            it: sinon.stub(),
        };
    });

    afterEach(() => {
        delete (global as { [key: string]: unknown }).window;
    });

    describe('addons', () => {
        it('should be an array', () => {
            expect(addons).to.be.an('array');
        });

        it('should contain one addon', () => {
            expect(addons).to.have.length(1);
        });

        it('should have initialiserGroups on the first addon', () => {
            expect(addons[0]).to.have.property('initialiserGroups');
        });

        it('should have one initialiser group', () => {
            expect(addons[0].initialiserGroups).to.have.length(1);
        });

        it('should have a function as the initialiser group', () => {
            expect(addons[0].initialiserGroups[0]).to.be.a('function');
        });
    });

    describe('initialiser group', () => {
        let addon: UniterAddon;
        let environment: sinon.SinonStubbedInstance<UniterEnvironment>;

        beforeEach(() => {
            addon = addons[0];

            environment = {
                defineCoercingFunction: sinon.stub(),
            };
        });

        it('should increase Error.stackTraceLimit to 50', () => {
            addon.initialiserGroups[0]({ environment });

            expect(errorMock.stackTraceLimit).to.equal(50);
        });

        it('should call defineCoercingFunction for tappet_get_fixture_api_base_url()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_get_fixture_api_base_url'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_set_fixture_api_base_url()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_set_fixture_api_base_url'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_get_fixture_api()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_get_fixture_api'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_get_cypress_api()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_get_cypress_api'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_get_describe()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_get_describe'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_get_base_url()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_get_base_url'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_get_cypress_project_root()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_get_cypress_project_root'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_get_suite_name()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_get_suite_name'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_add_window_load_handler()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_add_window_load_handler'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_add_window_beforeload_handler()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_add_window_beforeload_handler'),
                sinon.match.func,
            );
        });

        it('should call defineCoercingFunction for tappet_init_transition_log()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been.calledWith(
                sinon.match('tappet_init_transition_log'),
                sinon.match.func,
            );
        });

        it('should throw if tappetSuite is not set', () => {
            cypressEnv.withArgs('tappetSuite').returns(null);

            expect(() => addon.initialiserGroups[0]({ environment })).to.throw(
                'Tappet Cypress: Cypress environment variable "tappetSuite" not set',
            );
        });

        it('should register twelve coercing functions in total()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(
                (environment.defineCoercingFunction as sinon.SinonStub)
                    .callCount,
            ).to.equal(12);
        });

        describe('tappet_get_fixture_api_base_url() handler', () => {
            it('should return the value resolved by cy.task("tappetCypressGetApiBaseUrl")', async () => {
                cypressCy.task.withArgs('tappetCypressGetApiBaseUrl').returns({
                    then: sinon
                        .stub()
                        .callsFake((fn) =>
                            fn('https://node-side.test/.well-known/tappet'),
                        ),
                });

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (call) =>
                            call.args[0] === 'tappet_get_fixture_api_base_url',
                    );
                const handler = call!.args[1] as () => Promise<unknown>;

                expect(await handler()).to.equal(
                    'https://node-side.test/.well-known/tappet',
                );
            });
        });

        describe('tappet_set_fixture_api_base_url() handler', () => {
            it('should call cy.task("tappetCypressSetApiBaseUrl") with the new base URL', async () => {
                cypressCy.task
                    .withArgs('tappetCypressSetApiBaseUrl', sinon.match.object)
                    .returns({
                        then: sinon.stub().callsFake((fn) => fn(null)),
                    });

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (call) =>
                            call.args[0] === 'tappet_set_fixture_api_base_url',
                    );
                const handler = call!.args[1] as (
                    baseUrl: string,
                ) => Promise<void>;

                await handler('https://my-new-app.test/.well-known/tappet');

                expect(cypressCy.task).to.have.been.calledWith(
                    'tappetCypressSetApiBaseUrl',
                    { baseUrl: 'https://my-new-app.test/.well-known/tappet' },
                );
            });
        });

        describe('tappet_get_fixture_api() handler', () => {
            it('should return an object with loadFixture, loadMultipleFixtures and purge methods', () => {
                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((call) => call.args[0] === 'tappet_get_fixture_api');
                const handler = call!.args[1] as () => {
                    loadFixture: unknown;
                    loadMultipleFixtures: unknown;
                    purge: unknown;
                };
                const api = handler();

                expect(api)
                    .to.have.property('loadFixture')
                    .that.is.a('function');
                expect(api)
                    .to.have.property('loadMultipleFixtures')
                    .that.is.a('function');
                expect(api).to.have.property('purge').that.is.a('function');
            });
        });

        describe('tappet_get_fixture_api() handler > loadFixture', () => {
            let getApi: () => {
                loadFixture: (
                    fixtureClass: string,
                    fixturePayload: string,
                ) => Promise<string>;
            };

            beforeEach(() => {
                addon.initialiserGroups[0]({ environment });

                getApi = () => {
                    const call = (
                        environment.defineCoercingFunction as sinon.SinonStub
                    )
                        .getCalls()
                        .find(
                            (call) => call.args[0] === 'tappet_get_fixture_api',
                        );
                    return (call!.args[1] as () => ReturnType<typeof getApi>)();
                };
            });

            it('should call cy.task() with tappetCypressLoadFixture', async () => {
                await getApi().loadFixture('my-fixture', 'payload');

                expect(cypressCy.task).to.have.been.calledWith(
                    'tappetCypressLoadFixture',
                    sinon.match.object,
                );
            });

            it('should pass the fixture class and fixture payload in the task payload', async () => {
                await getApi().loadFixture('my-fixture', 'payload');

                expect(cypressCy.task).to.have.been.calledWith(
                    'tappetCypressLoadFixture',
                    {
                        fixtureClass: 'my-fixture',
                        fixturePayload: 'payload',
                    },
                );
            });
        });

        describe('tappet_get_fixture_api() handler > loadMultipleFixtures', () => {
            let getApi: () => {
                loadMultipleFixtures: (
                    fixturesPayload: string,
                ) => Promise<string>;
            };

            beforeEach(() => {
                addon.initialiserGroups[0]({ environment });

                getApi = () => {
                    const call = (
                        environment.defineCoercingFunction as sinon.SinonStub
                    )
                        .getCalls()
                        .find(
                            (call) => call.args[0] === 'tappet_get_fixture_api',
                        );
                    return (call!.args[1] as () => ReturnType<typeof getApi>)();
                };
            });

            it('should call cy.task with tappetCypressLoadMultipleFixtures', async () => {
                await getApi().loadMultipleFixtures('a:2:{...}');

                expect(cypressCy.task).to.have.been.calledWith(
                    'tappetCypressLoadMultipleFixtures',
                    sinon.match.object,
                );
            });

            it('should pass the serialised fixtures payload in the task payload', async () => {
                await getApi().loadMultipleFixtures(
                    'a:2:{s:5:"first";s:3:"..."}',
                );

                expect(cypressCy.task).to.have.been.calledWith(
                    sinon.match.string,
                    sinon.match({
                        fixturesPayload: 'a:2:{s:5:"first";s:3:"..."}',
                    }),
                );
            });
        });

        describe('tappet_get_fixture_api() handler > purge', () => {
            let getApi: () => {
                purge: (
                    modelsToPurge: {
                        fixture: string;
                        model: string;
                    }[],
                    modelsToDeferredPurge: {
                        fixture: string;
                        model: string;
                    }[],
                ) => Promise<void>;
            };

            beforeEach(() => {
                addon.initialiserGroups[0]({ environment });

                getApi = () => {
                    const call = (
                        environment.defineCoercingFunction as sinon.SinonStub
                    )
                        .getCalls()
                        .find(
                            (call) => call.args[0] === 'tappet_get_fixture_api',
                        );
                    return (call!.args[1] as () => ReturnType<typeof getApi>)();
                };
            });

            it('should call cy.task() with tappetCypressPurgeFixtures', async () => {
                await getApi().purge([], []);

                expect(cypressCy.task).to.have.been.calledWith(
                    'tappetCypressPurgeFixtures',
                    sinon.match.object,
                );
            });

            it('should pass empty models data in the task payload when empty', async () => {
                await getApi().purge([], []);

                expect(cypressCy.task).to.have.been.calledWith(
                    'tappetCypressPurgeFixtures',
                    { modelsToPurge: [], modelsToDeferredPurge: [] },
                );
            });

            it('should pass the models data in the task payload when non-empty', async () => {
                const modelsToPurge = [
                    {
                        fixture: 'serialised-fixture-1',
                        model: 'serialised-model-1',
                    },
                    {
                        fixture: 'serialised-fixture-2',
                        model: 'serialised-model-2',
                    },
                ];

                await getApi().purge(modelsToPurge, []);

                expect(cypressCy.task).to.have.been.calledWith(
                    'tappetCypressPurgeFixtures',
                    { modelsToPurge, modelsToDeferredPurge: [] },
                );
            });

            it('should pass the deferred-purge models data in the task payload when non-empty', async () => {
                const modelsToDeferredPurge = [
                    {
                        fixture: 'serialised-fixture-1',
                        model: 'serialised-model-1',
                    },
                ];

                await getApi().purge([], modelsToDeferredPurge);

                expect(cypressCy.task).to.have.been.calledWith(
                    'tappetCypressPurgeFixtures',
                    { modelsToPurge: [], modelsToDeferredPurge },
                );
            });
        });

        describe('tappet_get_cypress_api() handler', () => {
            it('should return the cy object', () => {
                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((call) => call.args[0] === 'tappet_get_cypress_api');
                const handler = call!.args[1] as () => unknown;

                expect(handler()).to.equal(cypressCy);
            });
        });

        describe('tappet_get_describe() handler', () => {
            let cypressBeforeEachHandlers: (() => void)[];
            let cypressItCalls: {
                description: string;
                fn: () => void;
                skipped: boolean;
            }[];
            let thenRejections: unknown[];
            let getDescribeHandler: (modelRepository: unknown) => (module: {
                getDescription(): Promise<string>;
                getScenarios(): Promise<
                    {
                        getDescription(): Promise<string>;
                        perform(): Promise<void>;
                    }[]
                >;
            }) => Promise<void>;

            beforeEach(() => {
                cypressBeforeEachHandlers = [];
                cypressItCalls = [];
                thenRejections = [];

                const window = (global as { [key: string]: unknown })
                    .window as Record<string, unknown>;

                window.describe = sinon
                    .stub()
                    .callsFake((_name: string, fn: () => void) => fn());

                window.beforeEach = sinon.stub().callsFake((fn: () => void) => {
                    cypressBeforeEachHandlers.push(fn);
                });

                const itStub = sinon
                    .stub()
                    .callsFake((description: string, fn: () => void) => {
                        cypressItCalls.push({
                            description,
                            fn,
                            skipped: false,
                        });
                    }) as sinon.SinonStub & { skip: sinon.SinonStub };
                itStub.skip = sinon
                    .stub()
                    .callsFake((description: string, fn: () => void) => {
                        cypressItCalls.push({ description, fn, skipped: true });
                    });
                window.it = itStub;

                /*
                 * Stub cy.then(...)'s real behaviour of invoking the callback and
                 * awaiting/propagating a returned Promise's rejection as a command failure,
                 * so addToCypressCommandQueueAllowingReentry()'s retry loop can be exercised
                 * end-to-end (rather than the default stub, which never invokes its callback).
                 */
                cypressCy.then.callsFake((fn: () => unknown) => {
                    const result = fn();

                    if (
                        result &&
                        typeof (result as Promise<unknown>).then === 'function'
                    ) {
                        (result as Promise<unknown>).catch((error) => {
                            thenRejections.push(error);
                        });
                    }

                    return result;
                });

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((call) => call.args[0] === 'tappet_get_describe');

                getDescribeHandler = call!.args[1] as typeof getDescribeHandler;
            });

            it('should wait for the transition log reset and model repository purge to settle before the test proceeds', async () => {
                const purge = sinon.stub().resolves();
                const reset = sinon.stub().resolves();

                const initCall = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (call) => call.args[0] === 'tappet_init_transition_log',
                    );
                (initCall!.args[1] as (log: unknown) => void)({ reset });

                await getDescribeHandler({ purge })({
                    getDescription: async () => 'My module',
                    getScenarios: async () => [],
                });

                expect(cypressBeforeEachHandlers).to.have.length(1);

                cypressBeforeEachHandlers[0]();

                await waitUntil(() => purge.called);

                expect(reset).to.have.been.calledOnce;
                expect(purge).to.have.been.calledOnce;
                expect(thenRejections).to.deep.equal([]);
            });

            it('should invoke scenario.perform() and wait for it to settle when the test runs', async () => {
                const perform = sinon.stub().resolves();

                await getDescribeHandler({ purge: sinon.stub().resolves() })({
                    getDescription: async () => 'My module',
                    getScenarios: async () => [
                        {
                            getDescription: async () => 'does the thing',
                            perform,
                        },
                    ],
                });

                expect(cypressItCalls).to.have.length(1);
                expect(cypressItCalls[0].skipped).to.equal(false);

                cypressItCalls[0].fn();

                await waitUntil(() => perform.called);

                expect(perform).to.have.been.calledOnce;
                expect(thenRejections).to.deep.equal([]);
            });

            it('should propagate a rejection from scenario.perform() as a command failure rather than swallowing it', async () => {
                const failure = new Error('Scenario failed');
                const perform = sinon.stub().rejects(failure);

                await getDescribeHandler({ purge: sinon.stub().resolves() })({
                    getDescription: async () => 'My module',
                    getScenarios: async () => [
                        {
                            getDescription: async () => 'does the thing',
                            perform,
                        },
                    ],
                });

                cypressItCalls[0].fn();

                await waitUntil(() => thenRejections.length > 0);

                expect(thenRejections).to.deep.equal([failure]);
            });
        });

        describe('tappet_get_base_url() handler', () => {
            it('should return the value of Cypress.config("baseUrl")', () => {
                cypressConfig
                    .withArgs('baseUrl')
                    .returns('https://my-app.test');

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((call) => call.args[0] === 'tappet_get_base_url');
                const handler = call!.args[1] as () => unknown;

                expect(handler()).to.equal('https://my-app.test');
            });
        });

        describe('tappet_get_cypress_project_root() handler', () => {
            it('should return the relative path when projectRoot starts with repoRoot', () => {
                cypressConfig
                    .withArgs('projectRoot')
                    .returns('/home/user/repo/inside/my-project');
                cypressConfig.withArgs('repoRoot').returns('/home/user/repo');

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (call) =>
                            call.args[0] === 'tappet_get_cypress_project_root',
                    );
                const handler = call!.args[1] as () => string;

                expect(handler()).to.equal('inside/my-project');
            });

            it('should return projectRoot as-is when it does not start with repoRoot', () => {
                cypressConfig.withArgs('projectRoot').returns('/other/path');
                cypressConfig.withArgs('repoRoot').returns('/home/user/repo');

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (call) =>
                            call.args[0] === 'tappet_get_cypress_project_root',
                    );
                const handler = call!.args[1] as () => string;

                expect(handler()).to.equal('/other/path');
            });
        });

        describe('tappet_get_suite_name() handler', () => {
            it('should return the suite name from Cypress.env("tappetSuite")', () => {
                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((call) => call.args[0] === 'tappet_get_suite_name');
                const handler = call!.args[1] as () => string;

                expect(handler()).to.equal('my-suite');
            });
        });

        describe('tappet_add_window_load_handler() handler', () => {
            it('should add the given handler to windowLoadHandlers', () => {
                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (call) =>
                            call.args[0] === 'tappet_add_window_load_handler',
                    );
                const handler = call!.args[1] as (
                    fn: (win: Window) => void,
                ) => void;

                const myLoadHandler = sinon.stub();
                handler(myLoadHandler);

                // Trigger a window:load to verify the handler was registered.
                const loadCb = cypressOn
                    .getCalls()
                    .find((call) => call.args[0] === 'window:load')!
                    .args[1] as (win: unknown) => void;
                const fakeWin: Record<string, unknown> = {
                    addEventListener: sinon.stub(),
                    location: { pathname: '/page', search: '', hash: '' },
                };
                loadCb(fakeWin);

                expect(myLoadHandler).to.have.been.calledOnceWith(fakeWin);
            });
        });

        describe('tappet_add_window_beforeload_handler() handler', () => {
            it('should add the given handler to windowBeforeLoadHandlers', () => {
                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (call) =>
                            call.args[0] ===
                            'tappet_add_window_beforeload_handler',
                    );
                const handler = call!.args[1] as (
                    fn: (win: Window) => void,
                ) => void;

                const myBeforeLoadHandler = sinon.stub();
                handler(myBeforeLoadHandler);

                // Trigger a window:before:load to verify the handler was registered.
                const beforeLoadCb = cypressOn
                    .getCalls()
                    .find((call) => call.args[0] === 'window:before:load')!
                    .args[1] as (win: unknown) => void;
                const fakeWin: Record<string, unknown> = {
                    addEventListener: sinon.stub(),
                    location: { href: 'http://example.com/' },
                };
                beforeLoadCb(fakeWin);

                expect(myBeforeLoadHandler).to.have.been.calledOnceWith(
                    fakeWin,
                );
            });
        });

        describe('tappet_init_transition_log() handler', () => {
            it('should store the given PHP-land transition log proxy', () => {
                addon.initialiserGroups[0]({ environment });
                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (call) => call.args[0] === 'tappet_init_transition_log',
                    );
                const handler = call!.args[1] as (log: unknown) => void;

                expect(() => {
                    // Should not throw when called with a stub log proxy.
                    handler({ reset: sinon.stub() });
                }).not.to.throw();
            });

            it('should register window:load and window:before:load listeners', () => {
                addon.initialiserGroups[0]({ environment });

                expect(cypressOn).to.have.been.calledWith(
                    'window:load',
                    sinon.match.func,
                );
                expect(cypressOn).to.have.been.calledWith(
                    'window:before:load',
                    sinon.match.func,
                );
            });
        });
    });
});
