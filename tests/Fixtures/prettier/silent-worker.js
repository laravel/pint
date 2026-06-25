// A stub Prettier worker that stays alive but never answers: it consumes the
// request and then idles forever, standing in for a worker wedged on input it
// can neither finish nor fail. It exists so the read loop's idle-timeout safety
// net can be exercised without depending on real prettier ever looping.
process.stdin.setEncoding("utf-8");
process.stdin.on("data", function () {
    // Swallow the request and never write a response.
});

// Keep the event loop alive so the process does not exit on its own.
setInterval(function () {}, 1 << 30);
