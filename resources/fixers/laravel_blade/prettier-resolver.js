const projectRoot = process.argv[2] || process.cwd();

const prettier = require(require.resolve("prettier", { paths: [projectRoot] }));

prettier
    .resolveConfig(`${projectRoot}/pint.blade.php`, { editorconfig: false })
    .then((options) => {
        process.stdout.write(JSON.stringify(options || {}));
    })
    .catch((error) => {
        process.stderr.write(`${error.stack || error.message}\n`);
        process.exit(1);
    });
