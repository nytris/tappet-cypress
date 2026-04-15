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
import path from 'node:path';
import * as sinon from 'sinon';

describe('defineConfig()', () => {
    describe('with only the required configDir and relativeRootDir arguments', () => {
        it('should return the plugins array', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.plugins).to.be.an('array');
            expect(config.plugins).to.have.length(2);
        });

        it('should set the rootDir in PHPify settings', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phpify?.rootDir).to.equal('../my-app');
        });

        it('should include the stub bootstrap in PHPify bootstraps', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            const hasStub = config.settings?.phpify?.bootstraps?.some((b) =>
                b.endsWith('stub.php'),
            );
            expect(hasStub).to.be.true;
        });

        it('should include the main bootstrap in PHPify bootstraps', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            const hasBootstrap = config.settings?.phpify?.bootstraps?.some(
                (b) => b.endsWith('bootstrap.php'),
            );
            expect(hasBootstrap).to.be.true;
        });

        it('should include vendor/autoload.php in PHPify bootstraps', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phpify?.bootstraps).to.include(
                'vendor/autoload.php',
            );
        });

        it('should set stub.php before bootstrap.php in bootstraps', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            const bootstraps = config.settings?.phpify?.bootstraps;
            const stubIndex =
                bootstraps?.findIndex((b) => b.endsWith('stub.php')) ?? -1;
            const bootstrapIndex =
                bootstraps?.findIndex((b) => b.endsWith('bootstrap.php')) ?? -1;
            expect(stubIndex).to.not.equal(-1);
            expect(bootstrapIndex).to.not.equal(-1);
            expect(stubIndex).to.be.lessThan(bootstrapIndex);
        });

        it('should include vendor Composer patterns in PHPify includes', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phpify?.include).to.include(
                'vendor/composer/**/*.php',
            );
            expect(config.settings?.phpify?.include).to.include(
                '!vendor/composer/pcre/**',
            );
        });

        it('should include vendor Tappet patterns in PHPify includes', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phpify?.include).to.include(
                'vendor/tappet/tappet/src/{Core,Suite}/**/*.php',
            );
            expect(config.settings?.phpify?.include).to.include(
                'vendor/tappet/cypress/src/php/**/*.php',
            );
        });

        it('should enable PHPCore stack cleaning', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phpcore?.stackCleaning).to.be.true;
        });

        it('should enable PHPToJS stack cleaning', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phptojs?.stackCleaning).to.be.true;
        });

        it('should enable PHPToJS line numbers', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phptojs?.lineNumbers).to.be.true;
        });

        it('should set PHPToJS mode to async', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phptojs?.mode).to.equal('async');
        });
    });

    describe('with a custom include list', () => {
        it('should append custom includes after the default includes', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                include: ['my/custom/path/**/*.php'],
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phpify?.include).to.deep.equal([
                'vendor/composer/**/*.php',
                '!vendor/composer/pcre/**',
                'vendor/tappet/tappet/src/{Core,Suite}/**/*.php',
                'vendor/tappet/cypress/src/php/**/*.php',
                // Custom include.
                'my/custom/path/**/*.php',
            ]);
        });

        it('should include all provided custom patterns', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                include: ['path/one/**/*.php', 'path/two/**/*.php'],
                stubComposerAutoloadFiles: false,
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
        it('should not add any additional include entries', () => {
            const config = defineConfig('/my/config/dir', '../my-app', {
                stubComposerAutoloadFiles: false,
            });

            expect(config.settings?.phpify?.include).to.deep.equal([
                'vendor/composer/**/*.php',
                '!vendor/composer/pcre/**',
                'vendor/tappet/tappet/src/{Core,Suite}/**/*.php',
                'vendor/tappet/cypress/src/php/**/*.php',
            ]);
        });
    });

    describe('with stubComposerAutoloadFiles: true', () => {
        const CONFIG_DIR = '/test/config';
        const RELATIVE_ROOT_DIR = 'my-project';
        const PROJECT_ROOT = '/test/config/my-project';

        let autoloadFilesFn: sinon.SinonStub;
        let createStub: sinon.SinonStub;
        let dotPhpFactory: typeof import('dotphp');
        let requireStub: sinon.SinonStub;

        beforeEach(() => {
            autoloadFilesFn = sinon.stub().returns([]);

            createStub = sinon.stub();
            dotPhpFactory = {
                create: createStub,
            };
            const engineStub = {
                execute: sinon.stub().returns({
                    getNative: sinon.stub().returns(autoloadFilesFn),
                }),
            };
            const moduleFactoryStub = sinon.stub().returns(engineStub);

            requireStub = sinon.stub().returns(moduleFactoryStub);
            createStub.returns({ require: requireStub });
        });

        it('should call dotPhpFactory.create(...) with the env/composer directory', () => {
            defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(createStub).to.have.been.calledOnce;
            expect(createStub.firstCall.args[0]).to.equal(
                path.resolve(
                    __dirname + '/../../../src/ts/uniter/env/composer',
                ) + '/',
            );
        });

        it('should require the get_autoload_files.php script', () => {
            defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(requireStub).to.have.been.calledOnce;
            expect(requireStub.firstCall.args[0]).to.equal(
                path.resolve(
                    __dirname + '/../../../src/ts/uniter/env/composer',
                ) + '/get_autoload_files.php',
            );
        });

        it('should call the autoload files getter with the resolved project root', () => {
            defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(autoloadFilesFn).to.have.been.calledOnceWith(PROJECT_ROOT);
        });

        it('should not add extra includes when no autoload files are returned', () => {
            autoloadFilesFn.returns([]);

            const config = defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(config.settings?.phpify?.include).to.deep.equal([
                'vendor/composer/**/*.php',
                '!vendor/composer/pcre/**',
                'vendor/tappet/tappet/src/{Core,Suite}/**/*.php',
                'vendor/tappet/cypress/src/php/**/*.php',
            ]);
        });

        it('should add a single autoload file to phpify.include as a relative path after the default include patterns', () => {
            autoloadFilesFn.returns([
                `${PROJECT_ROOT}/vendor/vnd/pkg/src/functions.php`,
            ]);

            const config = defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(config.settings?.phpify?.include).to.deep.equal([
                'vendor/composer/**/*.php',
                '!vendor/composer/pcre/**',
                'vendor/tappet/tappet/src/{Core,Suite}/**/*.php',
                'vendor/tappet/cypress/src/php/**/*.php',
                // Autoload file.
                'vendor/vnd/pkg/src/functions.php',
            ]);
        });

        it('should add multiple autoload files to phpify.include as relative paths after the default include patterns', () => {
            autoloadFilesFn.returns([
                `${PROJECT_ROOT}/vendor/vnd/pkg/src/functions.php`,
                `${PROJECT_ROOT}/vendor/vnd/other/bootstrap.php`,
            ]);

            const config = defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(config.settings?.phpify?.include).to.deep.equal([
                'vendor/composer/**/*.php',
                '!vendor/composer/pcre/**',
                'vendor/tappet/tappet/src/{Core,Suite}/**/*.php',
                'vendor/tappet/cypress/src/php/**/*.php',
                // Autoload files.
                'vendor/vnd/pkg/src/functions.php',
                'vendor/vnd/other/bootstrap.php',
            ]);
        });

        it('should add autoload files to phpify.stub with null values', () => {
            autoloadFilesFn.returns([
                `${PROJECT_ROOT}/vendor/vnd/pkg/src/functions.php`,
                `${PROJECT_ROOT}/vendor/vnd/other/bootstrap.php`,
            ]);

            const config = defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(config.settings?.phpify?.stub).to.have.property(
                'vendor/vnd/pkg/src/functions.php',
                null,
            );
            expect(config.settings?.phpify?.stub).to.have.property(
                'vendor/vnd/other/bootstrap.php',
                null,
            );
        });

        it('should preserve caller-provided include entries alongside autoload files', () => {
            autoloadFilesFn.returns([
                `${PROJECT_ROOT}/vendor/vnd/pkg/src/functions.php`,
            ]);

            const config = defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    include: ['src/my-custom/**/*.php'],
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(config.settings?.phpify?.include).to.deep.equal([
                'vendor/composer/**/*.php',
                '!vendor/composer/pcre/**',
                'vendor/tappet/tappet/src/{Core,Suite}/**/*.php',
                'vendor/tappet/cypress/src/php/**/*.php',
                // Caller-provided include entry.
                'src/my-custom/**/*.php',
                // Autoload file.
                'vendor/vnd/pkg/src/functions.php',
            ]);
        });

        it('should preserve caller-provided stub entries alongside autoload stubs', () => {
            autoloadFilesFn.returns([
                `${PROJECT_ROOT}/vendor/vnd/pkg/src/functions.php`,
            ]);

            const config = defineConfig(
                CONFIG_DIR,
                RELATIVE_ROOT_DIR,
                {
                    stub: { 'src/some/file.php': null },
                    stubComposerAutoloadFiles: true,
                },
                dotPhpFactory,
            );

            expect(config.settings?.phpify?.stub).to.have.property(
                'vendor/vnd/pkg/src/functions.php',
                null,
            );
            expect(config.settings?.phpify?.stub).to.have.property(
                'src/some/file.php',
                null,
            );
        });
    });
});
