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

describe('cypress/plugin/index', () => {
    describe('createPlugin()', () => {
        let stubPreprocessorFactory: sinon.SinonStub;
        let StubUniterPlugin: sinon.SinonStub;

        beforeEach(() => {
            stubPreprocessorFactory = sinon.stub().returns('stub-preprocessor');
            StubUniterPlugin = sinon.stub().returns({});
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

            beforeEach(() => {
                on = sinon.stub();
            });

            it('should call on with the file:preprocessor event', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                plugin(on);

                expect(on).to.have.been.calledOnce;
                expect(on.firstCall.args[0]).to.equal('file:preprocessor');
            });

            it('should call on with the result of the preprocessor factory', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                plugin(on);

                expect(on.firstCall.args[1]).to.equal('stub-preprocessor');
            });

            it('should call the preprocessor factory with webpackOptions', () => {
                const plugin = createPlugin(
                    stubPreprocessorFactory,
                    StubUniterPlugin as unknown as new () => object,
                );

                plugin(on);

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

                plugin(on);

                const webpackOptions =
                    stubPreprocessorFactory.firstCall.args[0].webpackOptions;
                expect(webpackOptions.plugins).to.be.an('array');
                expect(webpackOptions.plugins).to.have.length(1);
                expect(StubUniterPlugin).to.have.been.calledWithNew;
            });
        });
    });
});
