---
title: Composition Patterns
---

# Composition Patterns

_Reading time: ~3 minutes. Prerequisite: [Getting Started](/getting-started)._ 

Use this section for Effect-style guidance: how methods compose across a full flow, what defaults to expect, and where each boundary belongs.

## Core paths

- [Core Pipelines](/result/compositions/core-pipelines)
- [Failure and Recovery](/result/compositions/failure-recovery)
- [Finalization Boundaries](/result/compositions/finalization-boundaries)
- [Metadata and Observability](/result/compositions/metadata-observability)

## Mental model

Most production flows follow this shape:

```text
entry -> guard/transform -> failure mapping -> boundary
```

In ResultFlow terms:

```text
of|defer|ok|fail -> ensure/map/then -> otherwise/recover -> match|unwrap*|toResponse
```

Use the pages above to choose defaults, avoid edge-case traps, and compose methods intentionally.

## Contract reference

- Method contracts and signatures: [API Reference](/api)
- Task-oriented result pages: [Result Guide](/result/)
