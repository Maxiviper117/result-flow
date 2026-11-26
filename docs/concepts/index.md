# Understand a Result

When a function returns `Result`, the next line of code has two possible inputs: a value from a successful operation, or the reason it failed. Start with the question you have about that handoff.

| Question | Read |
| --- | --- |
| Can a successful lookup return null? | [The Result model](/concepts/result-model) |
| Which callback runs next, and what must it return? | [Chaining actions](/concepts/chaining) |
| How can a caller recover without losing the original reason? | [Handling failure](/concepts/failure-handling) |
| Where does a trace ID go when a step returns another Result? | [Metadata](/concepts/metadata) |

To see all four ideas in a running PHP script, follow [Save your first order](/getting-started).
