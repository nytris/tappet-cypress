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

describe('uniter/plugin/phpcore.config', () => {
    let cypressConfig: sinon.SinonStub;
    let cypressCy: { then: sinon.SinonStub };
    let cypressEnv: sinon.SinonStub;
    let fetch: sinon.SinonStub;

    beforeEach(() => {
        cypressConfig = sinon.stub();
        cypressCy = { then: sinon.stub() };
        cypressEnv = sinon.stub();
        fetch = sinon.stub().resolves({
            json: sinon
                .stub()
                .resolves({ serialisation: 'result-serialisation' }),
        });

        cypressConfig.withArgs('projectRoot').returns('/my/project/ui-tests');
        cypressConfig.withArgs('repoRoot').returns('/my/project');

        cypressEnv.withArgs('tappetApiBaseUrl').returns('http://localhost');
        cypressEnv.withArgs('tappetApiKey').returns('my-secret-key');
        cypressEnv.withArgs('tappetSuite').returns('my-suite');

        // Set up minimal window globals for Cypress context.
        (global as { [key: string]: unknown }).window = {
            Cypress: {
                config: cypressConfig,
                env: cypressEnv,
            },
            cy: cypressCy,
            describe: sinon.stub(),
            expect: sinon.stub(),
            fetch,
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

        it('should throw if tappetSuite is not set', () => {
            cypressEnv.withArgs('tappetSuite').returns(null);

            expect(() => addon.initialiserGroups[0]({ environment })).to.throw(
                'Tappet Cypress: Cypress environment variable "tappetSuite" not set',
            );
        });

        it('should throw if tappetApiKey is not set', () => {
            cypressEnv.withArgs('tappetApiKey').returns(null);

            expect(() => addon.initialiserGroups[0]({ environment })).to.throw(
                'Tappet Cypress: Cypress environment variable "tappetApiKey" not set',
            );
        });

        it('should register six coercing functions in total()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(
                (environment.defineCoercingFunction as sinon.SinonStub)
                    .callCount,
            ).to.equal(6);
        });

        it('should throw if tappetApiBaseUrl is not set', () => {
            cypressEnv.withArgs('tappetApiBaseUrl').returns(null);

            expect(() => addon.initialiserGroups[0]({ environment })).to.throw(
                'Tappet Cypress: Cypress environment variable "tappetApiBaseUrl" not set',
            );
        });

        describe('tappet_get_fixture_api() handler', () => {
            it('should return an object with loadFixture, loadMultipleFixtures and purge methods', () => {
                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((c) => c.args[0] === 'tappet_get_fixture_api');
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
                        .find((c) => c.args[0] === 'tappet_get_fixture_api');
                    return (call!.args[1] as () => ReturnType<typeof getApi>)();
                };
            });

            it('should call fetch', async () => {
                await getApi().loadFixture('my-fixture', 'payload');

                expect(fetch).to.have.been.calledOnce;
            });

            it('should send Authorization header', async () => {
                await getApi().loadFixture('my-fixture', 'payload');

                expect(fetch).to.have.been.calledWith(
                    sinon.match.string,
                    sinon.match({
                        headers: sinon.match({
                            Authorization: 'Bearer my-secret-key',
                        }),
                    }),
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
                        .find((c) => c.args[0] === 'tappet_get_fixture_api');
                    return (call!.args[1] as () => ReturnType<typeof getApi>)();
                };
            });

            it('should call fetch once for a bulk payload', async () => {
                await getApi().loadMultipleFixtures('a:2:{...}');

                expect(fetch).to.have.been.calledOnce;
            });

            it('should POST to the bulk fixtures endpoint', async () => {
                await getApi().loadMultipleFixtures('a:2:{...}');

                expect(fetch).to.have.been.calledWith(
                    'http://localhost/.well-known/tappet/fixtures',
                    sinon.match.object,
                );
            });

            it('should POST the serialised fixtures payload as JSON', async () => {
                await getApi().loadMultipleFixtures(
                    'a:2:{s:5:"first";s:3:"..."}',
                );

                expect(fetch).to.have.been.calledWith(
                    sinon.match.string,
                    sinon.match({
                        method: 'POST',
                        body: JSON.stringify({
                            serialisation: 'a:2:{s:5:"first";s:3:"..."}',
                        }),
                    }),
                );
            });

            it('should return the serialised models map from the response', async () => {
                fetch.resolves({
                    json: sinon
                        .stub()
                        .resolves({ serialisation: 'a:2:{...models...}' }),
                });

                const result = await getApi().loadMultipleFixtures('a:2:{...}');

                expect(result).to.equal('a:2:{...models...}');
            });

            it('should send Authorization header', async () => {
                await getApi().loadMultipleFixtures('a:2:{...}');

                expect(fetch).to.have.been.calledWith(
                    sinon.match.string,
                    sinon.match({
                        headers: sinon.match({
                            Authorization: 'Bearer my-secret-key',
                        }),
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
                ) => Promise<void>;
            };

            beforeEach(() => {
                addon.initialiserGroups[0]({ environment });

                getApi = () => {
                    const call = (
                        environment.defineCoercingFunction as sinon.SinonStub
                    )
                        .getCalls()
                        .find((c) => c.args[0] === 'tappet_get_fixture_api');
                    return (call!.args[1] as () => ReturnType<typeof getApi>)();
                };
            });

            it('should POST the serialised models payload as JSON', async () => {
                const modelsToPurge = [
                    { fixture: 'first', model: 'model-1' },
                    { fixture: 'second', model: 'model-2' },
                ];

                await getApi().purge(modelsToPurge);

                expect(fetch).to.have.been.calledWith(
                    sinon.match.string,
                    sinon.match({
                        method: 'DELETE',
                        body: JSON.stringify(modelsToPurge),
                    }),
                );
            });

            it('should send Authorization header', async () => {
                await getApi().purge([]);

                expect(fetch).to.have.been.calledWith(
                    sinon.match.string,
                    sinon.match({
                        headers: sinon.match({
                            Authorization: 'Bearer my-secret-key',
                        }),
                    }),
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
                    .find((c) => c.args[0] === 'tappet_get_cypress_api');
                const handler = call!.args[1] as () => unknown;

                expect(handler()).to.equal(cypressCy);
            });
        });

        describe('tappet_get_base_url() handler', () => {
            it('should return the value of Cypress.config("baseUrl")', () => {
                cypressConfig.withArgs('baseUrl').returns('http://my-app.test');

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((c) => c.args[0] === 'tappet_get_base_url');
                const handler = call!.args[1] as () => unknown;

                expect(handler()).to.equal('http://my-app.test');
            });
        });

        describe('tappet_get_cypress_project_root() handler', () => {
            it('should return the relative path when projectRoot starts with repoRoot', () => {
                cypressConfig
                    .withArgs('projectRoot')
                    .returns('/home/user/repo/my-project');
                cypressConfig.withArgs('repoRoot').returns('/home/user/repo');

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find(
                        (c) => c.args[0] === 'tappet_get_cypress_project_root',
                    );
                const handler = call!.args[1] as () => string;

                expect(handler()).to.equal('my-project');
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
                        (c) => c.args[0] === 'tappet_get_cypress_project_root',
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
                    .find((c) => c.args[0] === 'tappet_get_suite_name');
                const handler = call!.args[1] as () => string;

                expect(handler()).to.equal('my-suite');
            });
        });
    });
});
