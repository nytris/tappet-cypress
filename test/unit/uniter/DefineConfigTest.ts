/*
 * Tappet Cypress - Enjoyable GUI testing with Tappet, using Cypress
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/tappet-cypress/
 *
 * Released under the MIT license.
 * https://github.com/nytris/tappet-cypress/raw/main/MIT-LICENSE.txt
 */
import { defineConfig } from '../../../src/ts/uniter/defineConfig';
import { expect } from 'chai';

describe('defineConfig()', () => {
    describe('with only the required rootDir argument', () => {
        it('should return the plugins array', () => {
            const config = defineConfig('../my-app');

            expect(config.plugins).to.be.an('array');
            expect(config.plugins).to.have.length(2);
        });

        it('should set the rootDir in phpify settings', () => {
            const config = defineConfig('../my-app');

            expect(config.settings?.phpify?.rootDir).to.equal('../my-app');
        });

        it('should include the stub bootstrap in phpify bootstraps', () => {
            const config = defineConfig('../my-app');

            const hasStub = config.settings?.phpify?.bootstraps?.some((b) =>
                b.endsWith('stub.php'),
            );
            expect(hasStub).to.be.true;
        });

        it('should include the main bootstrap in phpify bootstraps', () => {
            const config = defineConfig('../my-app');

            const hasBootstrap = config.settings?.phpify?.bootstraps?.some(
                (b) => b.endsWith('bootstrap.php'),
            );
            expect(hasBootstrap).to.be.true;
        });

        it('should include vendor/autoload.php in phpify bootstraps', () => {
            const config = defineConfig('../my-app');

            expect(config.settings?.phpify?.bootstraps).to.include(
                'vendor/autoload.php',
            );
        });

        it('should set stub.php before bootstrap.php in bootstraps', () => {
            const config = defineConfig('../my-app');

            const bootstraps = config.settings?.phpify?.bootstraps;
            const stubIndex =
                bootstraps?.findIndex((b) => b.endsWith('stub.php')) ?? -1;
            const bootstrapIndex =
                bootstraps?.findIndex((b) => b.endsWith('bootstrap.php')) ?? -1;
            expect(stubIndex).to.not.equal(-1);
            expect(bootstrapIndex).to.not.equal(-1);
            expect(stubIndex).to.be.lessThan(bootstrapIndex);
        });

        it('should include vendor autoload patterns in phpify includes', () => {
            const config = defineConfig('../my-app');

            expect(config.settings?.phpify?.include).to.include(
                'vendor/autoload.php',
            );
            expect(config.settings?.phpify?.include).to.include(
                'vendor/composer/**/*.php',
            );
        });

        it('should include the tappet app test pattern in phpify includes', () => {
            const config = defineConfig('../my-app');

            expect(config.settings?.phpify?.include).to.include(
                'tests/tappet/app/**/*.php',
            );
        });

        it('should enable phpcore stack cleaning', () => {
            const config = defineConfig('../my-app');

            expect(config.settings?.phpcore?.stackCleaning).to.be.true;
        });

        it('should enable phptojs stack cleaning', () => {
            const config = defineConfig('../my-app');

            expect(config.settings?.phptojs?.stackCleaning).to.be.true;
        });

        it('should enable phptojs line numbers', () => {
            const config = defineConfig('../my-app');

            expect(config.settings?.phptojs?.lineNumbers).to.be.true;
        });

        it('should set phptojs mode to async', () => {
            const config = defineConfig('../my-app');

            expect(config.settings?.phptojs?.mode).to.equal('async');
        });
    });

    describe('with a custom include list', () => {
        it('should append custom includes after the default includes', () => {
            const config = defineConfig('../my-app', {
                include: ['my/custom/path/**/*.php'],
            });

            const includes = config.settings?.phpify?.include;
            const vendorIndex = includes?.indexOf('vendor/autoload.php') ?? -1;
            const customIndex =
                includes?.indexOf('my/custom/path/**/*.php') ?? -1;

            expect(vendorIndex).to.not.equal(-1);
            expect(customIndex).to.not.equal(-1);
            expect(customIndex).to.be.greaterThan(vendorIndex);
        });

        it('should include all provided custom patterns', () => {
            const config = defineConfig('../my-app', {
                include: ['path/one/**/*.php', 'path/two/**/*.php'],
            });

            expect(config.settings?.phpify?.include).to.include(
                'path/one/**/*.php',
            );
            expect(config.settings?.phpify?.include).to.include(
                'path/two/**/*.php',
            );
        });
    });

    describe('with an empty options object', () => {
        it('should use an empty include list', () => {
            const withEmpty = defineConfig('../my-app', {});
            const withoutOptions = defineConfig('../my-app');

            expect(withEmpty.settings?.phpify?.include).to.deep.equal(
                withoutOptions.settings?.phpify?.include,
            );
        });
    });
});
