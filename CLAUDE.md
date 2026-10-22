# CLAUDE.md

## 1. Purpose

This file defines Claude-specific instructions for working on this repository.

Universal engineering rules are defined in `AGENTS.md`.

Project knowledge and canonical documentation are organized under `docs/` and
mapped through `docs/index.md`.

---

## 2. First-Read Instructions

Before making substantial changes to the project:

1. Read `AGENTS.md`.
2. Read `README.md`.
3. Read `docs/index.md`.
4. Identify the user's task and its affected area.
5. Read only the canonical documents relevant to that task.
6. Inspect the existing implementation before introducing or changing patterns.

When a task requires historical decisions, prior planning context, or confirmation
of an earlier agreement, inspect the checkpoint Markdown file (`.md`) provided by
the user first, if one is available. Treat the checkpoint as historical context and
the repository ZIP/current repository documents as the current project snapshot; do
not assume the checkpoint is contained inside the repository ZIP. If the checkpoint
file is unavailable or unreadable, state that limitation explicitly and do not guess
what it contains. Follow `AGENTS.md` for the current checkpoint naming rule; do not
create or rename a numbered checkpoint without the user's explicit decision.

Do not begin substantial implementation from the conversation context alone when
the relevant project documentation is available.

During the documentation reconciliation phase, work on one Markdown file per
turn, following the user's agreed file-by-file workflow. Do not update the
checkpoint after each file; checkpoint work is considered only after the current
documentation cluster has been reviewed, and the user must be informed and asked
for approval before a new checkpoint is created or updated.

For small, obvious changes, use reasonable judgment without unnecessarily loading
the entire documentation set.

---

## 3. Context Loading Strategy

Use progressive context loading.

Do not read every project document for every task.

### Product / requirement task

Read as relevant:

- `docs/product/PRD.md`
- `docs/product/scope.md`
- `docs/domain/glossary.md`

### Outreach task

Read as relevant:

- `docs/domain/forms/outreach.md`
- `docs/architecture/database.md`
- `docs/architecture/workflow.md`
- `docs/ux/design.md`

### Mustahik task

Read as relevant:

- `docs/domain/forms/mustahik.md`
- `docs/architecture/database.md`
- `docs/architecture/workflow.md`
- `docs/ux/design.md`

### Kifayah task

Read as relevant:

- `docs/domain/forms/kifayah.md`
- `docs/architecture/database.md`
- `docs/architecture/workflow.md`
- `docs/ux/design.md`

### Authorization / role task

Read as relevant:

- `docs/architecture/roles-permissions.md`
- `docs/architecture/workflow.md`
- `docs/architecture/security.md`
- `docs/architecture/database.md`

### UI / UX task

Read as relevant:

- `docs/ux/design.md`
- the relevant product/form/workflow documentation

### Architecture / infrastructure task

Read as relevant:

- `docs/architecture/architecture.md`
- `docs/architecture/database.md`
- `docs/architecture/security.md`

### Testing task

Read as relevant:

- `docs/engineering/testing.md`
- the canonical documentation of the feature being tested

This is a routing strategy, not a requirement to read every listed file for every
task.

---

## 4. Task Execution Protocol

Use the following general flow:

```text
Understand
→ Locate source of truth
→ Inspect existing implementation
→ Assess impact
→ Plan
→ Implement
→ Verify
→ Synchronize documentation/tasks when needed
```

### Understand

Determine:

- what the user is asking for;
- which feature/domain is affected;
- whether the task is prototype or production related;
- whether the requested change conflicts with an existing decision.

### Inspect

Before creating a new implementation:

- search for related routes;
- search for existing components;
- search for existing services;
- inspect relevant models/migrations;
- inspect existing validation/policies;
- inspect existing UI patterns.

Prefer modifying or reusing the established pattern when appropriate.

### Assess impact

Identify whether the change affects:

- architecture;
- database schema;
- workflow/state;
- authorization;
- security;
- UI behavior;
- documented requirements;
- prototype scope.

### Implement

Make the smallest coherent change that satisfies the documented requirement.

For the initial prototype, the intended scope is local, non-production use with
dummy data and a demo on one computer. Hosting/domain and multi-user LAN use are
outside the initial demo scope unless the user explicitly changes that scope.

Do not treat every unfinished document or external validation as a blocker to all
coding. Before implementation begins, identify and resolve critical documentation
conflicts that could change business behavior, authorization, security, workflow,
or a locked baseline. Keep validations that require the prototype or confirmation
from the Kelurahan clearly marked `PENDING VALIDATION`; do not present them as
confirmed decisions.

The implementation gate remains closed until the user explicitly approves opening
it after receiving a concise readiness summary, including any unresolved critical
conflicts. Approval to open the gate does not authorize unapproved changes to the
locked database baseline, workflow, or other consequential decisions.

During the ongoing canonical documentation reconciliation, permission to make a
small local change does not automatically authorize editing canonical requirements,
changing a locked decision, or modifying files outside the agreed task scope. For
such changes, use this process:

```text
Inspect
→ Report findings
→ Propose the change and its impact
→ Wait for user approval
→ Apply the approved change
→ Verify
```

Do not overwrite a canonical baseline or expand the agreed scope before the user
approves the specific change.

### Verify

Run the most relevant tests/checks and inspect the result.

---

## 5. Planning Rules

For a substantial task, provide a concise implementation plan before making broad
changes.

The plan should identify:

- affected modules/files;
- important dependencies;
- data or workflow impact;
- validation/authorization considerations;
- verification steps.

For small, localized changes, a formal plan is not required.

Do not create long planning documents for routine edits.

---

## 6. Source-of-Truth Behavior

The canonical documentation map is defined in `docs/index.md`.

Follow `AGENTS.md` for universal source-of-truth and change rules.

### Important rules

- Do not invent undocumented product requirements.
- Do not silently override locked decisions.
- Do not treat pending validation as approved.
- Do not use an old implementation as proof that the implementation is still the
  intended behavior.
- Inspect the current canonical documentation before making consequential changes.

When documentation and implementation disagree, investigate the mismatch instead
of automatically preserving one side.

When a conflict cannot be resolved from the checkpoint and decision history,
ask the user before making a consequential behavioral or architectural choice.

---

## 7. Consequential Change Approval

Claude may directly implement small, local, low-risk code changes when the
context is clear and the task scope is already authorized. This permission does
not automatically authorize substantive edits to canonical documentation during
reconciliation, changes to locked decisions, or changes outside the agreed scope.
For those changes, report findings and impact, propose the specific change, and
wait for user approval before applying it.

Claude must **ask for confirmation before implementing a consequential change**
that affects any of the following:

- Architecture
- Database / ERD
- Workflow / state transitions
- Roles & permissions
- Security model
- Project scope
- Locked project decisions

### Required behavior

```text
Identify impact
→ Explain the proposed change
→ State relevant assumptions / consequences
→ Ask for confirmation
→ Implement only after approval
```

Do not bypass this rule because the change appears technically convenient.

### Exceptions

A consequential change may be implemented without an additional confirmation only
when the user has already explicitly approved the same change in the current
project context and the canonical documentation has been updated accordingly.

---

## 8. Clean Code & Code Quality

Write code with readability, maintainability, and consistency as primary goals.

### Prefer

- clear and descriptive names;
- focused functions/methods;
- single, understandable responsibilities;
- consistent project patterns;
- small, coherent changes;
- explicit error handling;
- reusable code when duplication is genuinely present;
- abstractions that solve a real problem.

### Avoid

- excessively long or multi-purpose functions;
- unclear names;
- unnecessary nesting;
- duplicate implementations;
- magic numbers and magic strings where named constants/configuration make the
  intent clearer;
- comments that merely restate the code;
- clever code that sacrifices readability;
- abstraction for abstraction's sake.

### Important balance

Clean Code does **not** mean creating more classes, layers, interfaces, or
patterns by default.

For this project:

```text
Readable
→ Consistent
→ Testable
→ Maintainable
```

is preferred over unnecessary architectural complexity.

Follow the project's Modular Monolith design and the architecture rules in
`AGENTS.md` and `docs/architecture/architecture.md`.

---

## 9. User Communication Before Significant Changes

Before making a significant change, communicate:

- what will change;
- why it is needed;
- which project area is affected;
- whether the canonical documentation will need updating;
- whether confirmation is required under the consequential-change rule.

Do not hide important architectural, data, workflow, or security consequences
inside a large implementation.

For small local changes, keep communication concise.

---

## 10. Documentation Synchronization

When implementation changes documented behavior, synchronize the appropriate
canonical documents.

Examples:

```text
New Outreach field
→ forms/outreach.md
→ database.md
→ design.md
```

or, as applicable:

```text
Workflow change
→ workflow.md
→ roles-permissions.md
→ design.md
→ PRD.md if product behavior changed
```

Do not update unrelated documentation merely for symmetry.

### Decision changes

If a significant approved decision changes:

1. Update the canonical source.
2. Record the decision in `docs/project-management/decisions.md` when appropriate.
3. Update derived documentation that genuinely depends on it.

Never make the code the hidden source of truth for a new project decision.

---

## 11. Task & Decision Management

### Tasks

The project task tracker is:

`docs/project-management/tasks.md`

Update it when:

- a tracked task is completed;
- a tracked task is blocked;
- a task meaningfully changes scope or status;
- the user asks to maintain task progress.

Do not rewrite the task tracker unnecessarily for trivial implementation work.

### Decisions

Important approved design changes belong in:

`docs/project-management/decisions.md`

A decision should normally record:

- what was decided;
- why;
- affected area;
- date/version when useful;
- whether it is locked.

Do not record every minor coding preference as a project decision.

---

## 12. Code Generation & Repository Behavior

Before creating a new file, class, component, helper, service, or abstraction:

1. Search for an existing equivalent.
2. Check the relevant architecture/design documentation.
3. Confirm that a new implementation is actually needed.
4. Follow existing naming and folder conventions.

Prefer reuse over duplicate implementations.

Do not introduce a new external dependency without a clear reason.

Avoid broad refactors when the task only requires a focused change.

Preserve unrelated working behavior.

---

## 13. Handling Ambiguity

Classify information when useful:

```text
KNOWN
CONFIRMED
LOCKED
PENDING VALIDATION
UNKNOWN
```

### Pending validation

A pending requirement is not equivalent to approval.

When the prototype must support a pending choice, preserve the documented
flexibility rather than hard-coding one interpretation unnecessarily. Validation
that can only be performed after a working prototype exists is not, by itself, a
reason to block all implementation; record it as pending and plan the relevant
prototype test or stakeholder confirmation.

### Unknown behavior

Do not invent behavior just to make the code compile.

Use an explicit safe default only when the project documentation already defines
such a default. Otherwise, ask.

### Conflicting information

When two relevant sources conflict:

1. If historical context or an earlier agreement is relevant, inspect the
   checkpoint Markdown file (`.md`) provided by the user first. Treat it as
   historical context, separate from the current repository ZIP/snapshot. If the
   checkpoint file is unavailable or unreadable, state that limitation rather than
   guessing. Do not assume the checkpoint is embedded in the repository ZIP.
2. Check `docs/project-management/decisions.md`.
3. Check the relevant canonical document.
4. If implementation is authorized and available, inspect the implementation for
   context; do not treat existing code as automatic proof of intended behavior.
5. Report the conflict, evidence, and impact, then ask the user if a consequential
   conflict remains unresolved.

---

## 14. Prototype Awareness

The current phase is prototype development.

Follow:

`docs/prototype/prototype.md`

for prototype scope and limitations.

Remember:

```text
Prototype ≠ Production
```

### Prototype behavior

- Prototype may simplify non-critical implementation details when explicitly
  documented.
- Prototype must still demonstrate the intended business workflow where that
  workflow is part of validation.
- Do not introduce unnecessary production-scale infrastructure solely for the
  prototype.
- Do not remove important business behavior merely because it is inconvenient
  to demonstrate.
- Do not treat prototype shortcuts as Production architecture unless they are
  separately approved.

---

## 15. Verification & Reporting

Do not report a feature as complete merely because code has been written.

For meaningful changes:

```text
Implement
→ Run relevant checks
→ Verify behavior
→ Inspect diff
→ Report result
```

### Verification should cover, as applicable

- automated tests;
- type checking;
- linting;
- build;
- authorization;
- validation;
- workflow/state transitions;
- file upload/access behavior;
- UI behavior;
- regression checks.

Do not claim tests or checks were run when they were not.

### Reporting

When finishing a substantial task, report concisely:

```text
## Changes
What changed.

## Verification
What was tested/checked and the result.

## Documentation
What documentation was updated, if any.

## Remaining
Known limitations, blockers, or follow-up work.
```

For trivial edits, use a shorter report.

---

## Final Operating Principle

Claude should optimize for **project consistency, correctness, readability, and
maintainability**, not merely speed of code generation.

When uncertain:

> Read the canonical documentation, inspect the existing implementation, identify
> the impact, and ask before making a consequential change.

Do not invent requirements.
Do not silently override locked decisions.
Do not hide important architectural or data changes.
