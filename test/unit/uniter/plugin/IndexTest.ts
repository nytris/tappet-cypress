/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import * as pluginIndex from '../../../../src/ts/uniter/plugin/index';
import { expect } from 'chai';

describe('uniter/plugin/index', () => {
    it('should export a phpcore property', () => {
        expect(pluginIndex).to.have.property('phpcore');
    });

    it('should export phpcore as a string path', () => {
        expect(pluginIndex.phpcore).to.be.a('string');
    });

    it('should export a phpcore path ending with phpcore.config.js', () => {
        expect(pluginIndex.phpcore).to.match(/phpcore\.config\.js$/);
    });

    it('should export a phpcore path that is absolute', () => {
        expect(pluginIndex.phpcore).to.match(/^\//);
    });

    it('should export a phpcore path within the plugin directory', () => {
        expect(pluginIndex.phpcore).to.include('plugin');
    });
});
