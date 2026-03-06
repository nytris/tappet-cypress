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

            // Set up minimal window globals for Cypress context.
            (global as unknown as Record<string, unknown>).window = {
                Cypress: { env: sinon.stub().returns('http://localhost') },
                cy: { then: sinon.stub() },
                describe: sinon.stub(),
                expect: sinon.stub(),
                fetch: sinon
                    .stub()
                    .resolves({ text: sinon.stub().resolves('') }),
                it: sinon.stub(),
            };
        });

        afterEach(() => {
            delete (global as unknown as Record<string, unknown>).window;
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

        it('should register three coercing functions in total()', () => {
            addon.initialiserGroups[0]({ environment });

            expect(environment.defineCoercingFunction).to.have.been
                .calledThrice;
        });

        it('should throw if tappetBaseUrl is not set', () => {
            (global as unknown as Record<string, unknown>).window = {
                Cypress: { env: sinon.stub().returns(null) },
                cy: { then: sinon.stub() },
                describe: sinon.stub(),
                expect: sinon.stub(),
                fetch: sinon
                    .stub()
                    .resolves({ text: sinon.stub().resolves('') }),
                it: sinon.stub(),
            };

            expect(() => addon.initialiserGroups[0]({ environment })).to.throw(
                'Tappet Cypress: Cypress environment variable "tappetBaseUrl" not set',
            );
        });

        describe('tappet_get_fixture_api() handler', () => {
            it('should return an object with loadFixture and purge methods', () => {
                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((c) => c.args[0] === 'tappet_get_fixture_api');
                const handler = call!.args[1] as () => {
                    loadFixture: unknown;
                    purge: unknown;
                };
                const api = handler();

                expect(api)
                    .to.have.property('loadFixture')
                    .that.is.a('function');
                expect(api).to.have.property('purge').that.is.a('function');
            });

            it('should call fetch when loadFixture is invoked', async () => {
                const stubFetch = sinon
                    .stub()
                    .resolves({ text: sinon.stub().resolves('') });
                (global as unknown as Record<string, unknown>).window = {
                    Cypress: {
                        env: sinon.stub().returns('http://localhost'),
                    },
                    cy: { then: sinon.stub() },
                    describe: sinon.stub(),
                    expect: sinon.stub(),
                    fetch: stubFetch,
                    it: sinon.stub(),
                };

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((c) => c.args[0] === 'tappet_get_fixture_api');
                const handler = call!.args[1] as () => {
                    loadFixture: (
                        handle: string,
                        payload: string,
                    ) => Promise<string>;
                };
                const api = handler();
                await api.loadFixture('my-fixture', 'payload');

                expect(stubFetch).to.have.been.calledOnce;
            });
        });

        describe('tappet_get_cypress_api() handler', () => {
            it('should return the cy object', () => {
                const stubCy = { then: sinon.stub() };
                (global as unknown as Record<string, unknown>).window = {
                    Cypress: { env: sinon.stub().returns('http://localhost') },
                    cy: stubCy,
                    describe: sinon.stub(),
                    expect: sinon.stub(),
                    fetch: sinon
                        .stub()
                        .resolves({ text: sinon.stub().resolves('') }),
                    it: sinon.stub(),
                };

                addon.initialiserGroups[0]({ environment });

                const call = (
                    environment.defineCoercingFunction as sinon.SinonStub
                )
                    .getCalls()
                    .find((c) => c.args[0] === 'tappet_get_cypress_api');
                const handler = call!.args[1] as () => unknown;

                expect(handler()).to.equal(stubCy);
            });
        });
    });
});
