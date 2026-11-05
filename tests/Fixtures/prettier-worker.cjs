const readline = require("node:readline");

const lines = readline.createInterface({
    input: process.stdin,
});

lines.on("line", (line) => {
    const { content } = JSON.parse(line);

    process.stdout.write("unrelated stdout");
    process.stdout.write(
        "[PINT_PRETTIER_WORKER]" +
            JSON.stringify({ formatted: content }) +
            "\n",
    );
});
