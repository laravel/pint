const fs = require("node:fs");
const path = require("node:path");

const projectRoot = process.argv[2] || process.cwd();
const configPath = process.argv[3];

const prettier = require(require.resolve("prettier", { paths: [projectRoot] }));

const bundledOptions = require(configPath);

function defaultTailwindStylesheet() {
    const stylesheet = path.join(projectRoot, "resources", "css", "app.css");

    try {
        return /@import\s+["']tailwindcss["']/.test(
            fs.readFileSync(stylesheet, "utf8"),
        )
            ? stylesheet
            : null;
    } catch {
        return null;
    }
}

const tailwindStylesheet = defaultTailwindStylesheet();

function resolvePlugins(plugins) {
    if (!Array.isArray(plugins)) {
        return plugins;
    }

    return plugins.map((plugin) =>
        typeof plugin === "string"
            ? require.resolve(plugin, { paths: [projectRoot] })
            : plugin,
    );
}

function resolveOptionPlugins(options) {
    if (options.plugins) {
        options.plugins = resolvePlugins(options.plugins);
    }

    if (Array.isArray(options.overrides)) {
        for (const override of options.overrides) {
            if (override.options && override.options.plugins) {
                override.options.plugins = resolvePlugins(
                    override.options.plugins,
                );
            }
        }
    }

    return options;
}

process.stdin.setEncoding("utf-8");

let buffer = "";
let queue = Promise.resolve();

process.stdin.on("data", function (chunk) {
    buffer += chunk;

    let newlineIndex;

    while ((newlineIndex = buffer.indexOf("\n")) !== -1) {
        const line = buffer.slice(0, newlineIndex);
        buffer = buffer.slice(newlineIndex + 1);

        if (line.trim() === "") {
            continue;
        }

        queue = queue.then(() => handleMessage(line));
    }
});

async function handleMessage(input) {
    try {
        const { path: filepath, content } = JSON.parse(input);

        const resolved = filepath.trim();

        const options = resolveOptionPlugins({ ...bundledOptions });

        if (tailwindStylesheet && !options.tailwindStylesheet) {
            options.tailwindStylesheet = tailwindStylesheet;
        }

        const formatted = await prettier.format(content, {
            ...options,
            filepath: resolved,
        });

        process.stdout.write(
            `[PINT_PRETTIER_WORKER_START]${formatted}[PINT_PRETTIER_WORKER_END]`,
        );
    } catch (error) {
        process.stderr.write(`${error.stack || error.message}\n`);
    }
}
