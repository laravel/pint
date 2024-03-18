const prettier = require("prettier");
const fs = require("fs").promises;
const options = require(__dirname + '/prettierrc.json');
process.stdin.setEncoding('utf-8');

process.stdin.on('data', async function (path) {
    try {
        const content = await fs.readFile(path.trim(), "utf8");

        const formatted = await prettier.format(content, { ...options, filepath: path.trim() });

        process.stdout.write(formatted);
    } catch (error) {
        process.stderr.write(`${error.message}`);
    }
});
