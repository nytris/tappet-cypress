const { defineConfig } = require('@tappet/cypress/uniter/defineConfig');

// From tests/Functional/Fixtures/MyTestApp/test/ up to the package root (5 levels).
module.exports = defineConfig(__dirname, '../../../../../', {
    include: [
        // PHP fixture-support classes for this test app.
        'tests/Functional/Fixtures/MyTestApp/test/app/**/*.php',
        'tests/Functional/Fixtures/MyTestApp/test/tappet.my-suite.suite.php',

        // As we're within this project, the vendor/tappet/cypress/* path in defineConfig won't match,
        // so we need to specify it relative to the project root.
        'src/php/**/*.php'
    ],
});
