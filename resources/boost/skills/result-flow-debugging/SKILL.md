# ResultFlow Debugging and Metadata Safety Skill

## Mission and scope

Diagnose and improve `Result` workflows with safe debugging output, metadata hygiene, and clear failure-path troubleshooting steps.

Use this skill in downstream applications when investigating unexpected failures, noisy error payloads, or missing trace context.

## Inputs expected from user/codebase

- The `Result` chain being debugged.
- Current metadata keys and expected observability fields.
- Desired debug output target (`toDebugArray`, `toArray`, logs, response payload).
- Redaction requirements (sensitive keys, truncation limits, masking policy).

## Generation rules

- Prefer non-invasive inspection first:
  - `inspect`, `inspectError`, `tapMeta`, `toDebugArray`.
- Keep domain behavior deterministic:
  - Debug instrumentation must not alter success/failure semantics.
- If failures appear after setup side effects, isolate setup with `Result::defer(fn () => ...)`.
- Preserve metadata contract:
  - Keep `array<string,mixed>` keys stable.
  - Add diagnostics via `mergeMeta` or `mapMeta`, not globals.
- Use explicit branch handlers (`match`, `otherwise`) to show error transformation points.

Hard constraints:

- Do not leak secrets in examples or generated debug output.
- Do not remove existing metadata keys unless explicitly requested.
- Do not replace expected failures with exception-only handling.
- Use only documented public `Result` APIs.
- Follow host-project coding standards and test requirements.

## Troubleshooting checklist

- Confirm where first failure occurs (which `ensure`/`then` step).
- Confirm failure payload type consistency through `otherwise`.
- Confirm metadata is preserved on each transition.
- For `bracket(...)`, confirm `bracket.release_exception` metadata appears when release fails after a use failure.
- Confirm debug output redacts sensitive values.
- Confirm boundary completion uses `match`/`toResponse`/`unwrap*` intentionally.

## Example prompts and outcomes

### Prompt
Add safe diagnostics to a chain that fails in production without enough context.

### Outcome shape

- Add `tapMeta` for correlation IDs.
- Add `inspectError` to log sanitized failure details.
- Use `toDebugArray($sanitizer)` for safe payload snapshots.

### Prompt
Normalize inconsistent error shapes from multiple service calls.

### Outcome shape

- Use `otherwise` to map to one stable error schema.
- Preserve original details in metadata keys for diagnostics.
