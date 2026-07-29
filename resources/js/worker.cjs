const projectRoot = process.argv[2] || process.cwd();
const configPath = process.argv[3];

const prettier = require(require.resolve("prettier", { paths: [projectRoot] }));

const bundledOptions = require(configPath);

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

        if (type === "ranges" && !hasIgnoreRangeMarker(content)) {
            writeResponse({ ranges: [] });

            return;
        }

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

        if (
            typeof prettier.__debug?.parse !== "function" ||
            typeof prettier.__debug?.formatAST !== "function"
        ) {
            throw new Error(
                "The installed Prettier version does not expose the Blade ignore range formatter API.",
            );
        }

        const { ast } = await prettier.__debug.parse(content, parseOptions);
        const sourceRanges = ignoreRanges(ast, normalizedLength(content));

        if (type === "ranges") {
            writeResponse({
                ranges: sourceRanges.map((range) =>
                    toByteRange(content, range),
                ),
            });

            return;
        }

        let { formatted } = await prettier.__debug.formatAST(ast, parseOptions);

        if (content.charCodeAt(0) === 0xfeff) {
            formatted = `\ufeff${formatted}`;
        }

        const { ast: formattedAst } = await prettier.__debug.parse(
            formatted,
            parseOptions,
        );
        const formattedRanges = ignoreRanges(
            formattedAst,
            normalizedLength(formatted),
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

function hasIgnoreRangeMarker(content) {
    const lower = content.toLowerCase();

    return (
        lower.includes("format-ignore-start") ||
        lower.includes("prettier-ignore-start")
    );
}

function ignoreRanges(ast, contentLength) {
    const ranges = ast?.buildResult?.ignoreRanges;

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
        start: Buffer.byteLength(
            content.slice(0, sourceOffset(content, start)),
            "utf8",
        ),
        end: Buffer.byteLength(
            content.slice(0, sourceOffset(content, end)),
            "utf8",
        ),
    };
}

function normalizedLength(content) {
    let length = content.length;
    let offset = 0;

    if (content.charCodeAt(0) === 0xfeff) {
        length--;
        offset++;
    }

    while ((offset = content.indexOf("\r\n", offset)) !== -1) {
        length--;
        offset += 2;
    }

    return length;
}

function sourceOffset(content, normalizedOffset) {
    let offset = content.charCodeAt(0) === 0xfeff ? 1 : 0;
    let normalized = 0;

    while (normalized < normalizedOffset) {
        offset +=
            content[offset] === "\r" && content[offset + 1] === "\n" ? 2 : 1;
        normalized++;
    }

    return offset;
}

function writeResponse(response) {
    process.stdout.write(
        `[PINT_PRETTIER_WORKER_START]${JSON.stringify(response)}[PINT_PRETTIER_WORKER_END]`,
    );
}
