const projectRoot = process.argv[2] || process.cwd();
const configPath = process.argv[3];

const prettier = require(require.resolve("prettier", { paths: [projectRoot] }));
const { getBladeIgnoreRanges } = require(
    require.resolve("prettier-plugin-blade", { paths: [projectRoot] }),
);

const bundledOptions = require(configPath);

if (typeof getBladeIgnoreRanges !== "function") {
    throw new Error(
        "The installed Blade plugin does not expose its ignore range API.",
    );
}

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
        const { type = "format", path: filepath, content } = JSON.parse(input);

        const resolved = filepath.trim();

        const options = resolveOptionPlugins({ ...bundledOptions });

        const parseOptions = {
            ...options,
            filepath: resolved,
        };

        if (type === "format") {
            writeResponse({
                formatted: await prettier.format(content, parseOptions),
            });

            return;
        }

        const sourceRanges = ignoreRanges(
            getBladeIgnoreRanges(content, parseOptions),
            content.length,
        );

        if (type === "ranges") {
            writeResponse({
                ranges: sourceRanges.map((range) =>
                    toByteRange(content, range),
                ),
            });

            return;
        }

        const formatted = await prettier.format(content, parseOptions);
        const formattedRanges = ignoreRanges(
            getBladeIgnoreRanges(formatted, parseOptions),
            formatted.length,
        );

        if (formattedRanges.length !== sourceRanges.length) {
            throw new Error(
                "Prettier did not preserve every Blade ignore range.",
            );
        }

        writeResponse({
            formatted,
            ranges: formattedRanges.map((range, index) => {
                const formattedRange = toByteRange(formatted, range);
                const sourceRange = toByteRange(content, sourceRanges[index]);

                return {
                    ...formattedRange,
                    sourceStart: sourceRange.start,
                    sourceEnd: sourceRange.end,
                };
            }),
        });
    } catch (error) {
        process.stderr.write(`${error.stack || error.message}\n`);
    }
}

function ignoreRanges(ranges, contentLength) {
    if (
        !Array.isArray(ranges) ||
        !ranges.every(
            (range, index) =>
                range !== null &&
                typeof range === "object" &&
                Number.isInteger(range.start) &&
                Number.isInteger(range.end) &&
                range.start >= 0 &&
                range.end >= range.start &&
                range.end <= contentLength &&
                (index === 0 || range.start >= ranges[index - 1].end),
        )
    ) {
        throw new Error(
            "The installed Blade plugin did not return valid ignore ranges.",
        );
    }

    return ranges;
}

function toByteRange(content, { start, end }) {
    return {
        start: Buffer.byteLength(content.slice(0, start), "utf8"),
        end: Buffer.byteLength(content.slice(0, end), "utf8"),
    };
}

function writeResponse(response) {
    process.stdout.write(`[PINT_PRETTIER_WORKER]${JSON.stringify(response)}\n`);
}
