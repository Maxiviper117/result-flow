#!/usr/bin/env bun

const args = Bun.argv.slice(2);
const blacklistFlags = "nsfw,religious,political,racist,sexist";

const usage = `Usage:
  bun .github/skills/joke-fetcher/get-joke.js [category]
  bun .github/skills/joke-fetcher/get-joke.js --category <name> [--safe]

Options:
  -c, --category  Joke category (e.g., Programming, Misc, Dark, Any)
  --safe          Exclude flagged jokes (nsfw, religious, political, racist, sexist)
  -h, --help      Show this help message
`;

function printUsage(exitCode = 0) {
    console.log(usage);
    process.exit(exitCode);
}

let category = "Any";
let safe = false;

for (let i = 0; i < args.length; i += 1) {
    const arg = args[i];
    if (arg === "--help" || arg === "-h") {
        printUsage(0);
    } else if (arg === "--safe") {
        safe = true;
    } else if (arg === "--category" || arg === "-c") {
        const next = args[i + 1];
        if (!next || next.startsWith("-")) {
            console.error("Missing category value.");
            printUsage(1);
        }
        category = next;
        i += 1;
    } else if (!arg.startsWith("-") && category === "Any") {
        category = arg;
    } else {
        console.error(`Unknown argument: ${arg}`);
        printUsage(1);
    }
}

const url = new URL(`https://sv443.net/jokeapi/v2/joke/${encodeURIComponent(category)}`);
if (safe) {
    url.searchParams.set("blacklistFlags", blacklistFlags);
}

const response = await fetch(url.toString());
if (!response.ok) {
    console.error(`Request failed with status ${response.status}`);
    process.exit(1);
}

const data = await response.json();
if (data.error) {
    console.error(data.message || "JokeAPI returned an error.");
    process.exit(1);
}

if (data.type === "single") {
    console.log(`[${data.category}] ${data.joke}`);
} else if (data.type === "twopart") {
    console.log(`[${data.category}] ${data.setup}\n${data.delivery}`);
} else {
    console.log(JSON.stringify(data, null, 2));
}