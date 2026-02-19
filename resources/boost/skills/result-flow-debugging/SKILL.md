# ResultFlow Debugging and Metadata Safety Skill

## Mission and scope

Diagnose and improve `Result` workflows with safe debugging output, metadata hygiene, and clear failure-path troubleshooting steps.

Use this skill when investigating unexpected failures, noisy error payloads, or missing trace context.

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
- Preserve metadata contract:
  - Keep `array<string,mixed>` keys stable.
  - Add diagnostics via `mergeMeta` or `mapMeta`, not ad-hoc globals.
- Use explicit branch handlers (`match`, `otherwise`) to show where errors are transformed.

Hard constraints:

- Do not leak secrets in examples or generated debug output.
- Do not remove existing metadata keys unless explicitly requested.
- Do not replace expected failures with exception-only handling.
- Do not reference internal `src/Support/*` helpers in app-level usage; keep debugging through `Result` APIs.
- Keep generated updates compatible with repository checks (`composer pint-test`, `composer rector-dry`, `composer analyse`, `composer test`).

## Troubleshooting checklist

- Confirm where first failure occurs (which `ensure`/`then` step).
- Confirm failure payload type consistency through `otherwise`.
- Confirm metadata is preserved on each transition.
- Confirm debug output redacts sensitive values.
- Confirm edge boundary completion uses `match`/`toResponse`/`unwrap*` intentionally.

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
