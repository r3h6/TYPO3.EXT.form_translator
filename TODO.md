# Deferred Work

Out-of-scope items noticed while working on a change. Keep entries to one line;
delete them when done or promoted into a planned change.

<!-- Format: - [ ] YYYY-MM-DD — What and why. (from: <change-or-area>) -->

- [ ] 2026-11-07 — `Form/Localize.html` uses `{typo3Language.title}` although the controller assigns `language`, so the language column header is empty. (from: shared translation files)
- [ ] 2026-11-07 — Page caches are not flushed when form translations change, so translated forms can stay cached. (from: shared translation files)
