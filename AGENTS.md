# AGENTS.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Environment

You are running inside the project's DDEV web container, not on the host. Run
tooling directly (`php`, `composer`, `typo3`, `npm`, test binaries) without a
`ddev` prefix — host-side commands such as `ddev exec` or `ddev composer` are
not available in here. The docroot is `.Build/public`.

## Workflow

- Keep changes scoped: when something outside the current change comes up, do
  not fold it in. Add a one-line entry to `TODO.md` (date, what and why, where
  it came from) and continue.
- Review `TODO.md` when starting a change. Promote scheduled items into a
  planned change and delete them once done.
- The project is under heavy development and keeps no backwards
  compatibility. Mark breaking changes as such, but do not add data
  migrations, readers for old storage formats, version markers for legacy
  detection, rollback plans, or upgrade notes for them.
- Do not add `TODO.md` entries for cleaning up development DB data (smoke-run
  drafts, test records); the DB is reset at will.

## Coding principles

Apply these in order of precedence: simplicity wins over cleverness.

- **Simplicity first.** Implement the smallest solution that solves the actual
  problem. Avoid speculative abstraction, unnecessary layers, and extension
  points nobody asked for. Prefer deleting code over adding it.
- **Clean Code.** Use intention-revealing names and small, single-purpose
  functions. Avoid magic values, deep nesting, dead code, and commented-out
  blocks. Fail loudly instead of silently swallowing problems.
- **DRY.** Extract shared logic only when duplication is real, not when two
  snippets merely look alike. A little duplication is cheaper than the wrong
  abstraction; wait for the third occurrence before extracting.
- **Domain-Driven Design.** Keep domain logic in `Classes/Domain/` (models,
  value objects, repositories, services) and out of controllers, Fluid
  templates, and TCA. Use the language of the domain for class and method
  names, and keep persistence, HTTP, and TYPO3 framework concerns at the
  edges.

## Test-driven development

- Red, green, refactor: write a failing test first, make it pass with
  minimal code, then clean up with the suite green.
- Start bug fixes with a test that reproduces the bug.
- Test persistence and framework integration with functional tests. Write
  unit tests only when they need little mocking; otherwise prefer
  functional tests.
- Test behavior through public APIs, not implementation details.
- Never commit failing tests or weaken a test to make it pass.

## Commit policy

- Make atomic commits: one logical change per commit. Each commit must be self-contained and leave the tree in a working state. Do not mix unrelated changes, refactorings, or formatting with functional changes.
- Use the TYPO3 commit message format:

  ```text
  [TYPE] Imperative summary

  Explain what changed and why, wrapped at 72 characters.

  Resolves: #12345
  Related: #12340
  Releases: main, 13.4
  ```

  - **Summary line:** `[TYPE]` followed by a capitalized, imperative summary. Types are `[BUGFIX]`, `[FEATURE]`, `[TASK]`, and `[DOCS]`. Prefix breaking changes with `[!!!]` (for example `[!!!][TASK]`): any commit that removes or changes a public PHP API, TCA, the database schema, or configuration. Check this for every commit, including `Implement <change>` commits, and when a change's proposal or tasks mark it **BREAKING**. Keep the line under 52 characters if possible, and never over 72.
  - **Body:** separated from the summary by a blank line, wrapped at 72 characters, written in the imperative present tense. Describe what the commit changes; put problem reproduction details in the issue.
  - **Trailers:** one `Resolves: #<issue>` line per issue, plus `Related: #<issue>` for issues the commit does not close. Use `Releases:` to list target branches. Never write or modify `Change-Id`; the commit-msg hook generates it.

  Reference: https://docs.typo3.org/permalink/t3contribute:commitmessage
