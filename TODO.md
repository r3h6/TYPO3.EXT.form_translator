# Deferred Work

Out-of-scope items noticed while working on a change. Keep entries to one line;
delete them when done or promoted into a planned change.

<!-- Format: - [ ] YYYY-MM-DD — What and why. (from: <change-or-area>) -->

- [ ] 2026-11-10 — Drop the obsolete `extra.typo3/cms.app-dir` config; cms-composer-installers v5 warns that the app dir is always the Composer root. (from: TYPO3 12.4 dependency install)
- [ ] 2026-11-10 — Rebuild `Resources/Private/Php/jsonpath.phar` from softcreatr/jsonpath 0.9.1 to drop the PHP 8.1 return-type deprecations in the old bundled build; build it with PHP ≤ 8.4, since 0.9.1 caps PHP at 8.4. (from: PHP 8.5 jsonpath dependency)
