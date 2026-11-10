# Deferred Work

Out-of-scope items noticed while working on a change. Keep entries to one line;
delete them when done or promoted into a planned change.

<!-- Format: - [ ] YYYY-MM-DD — What and why. (from: <change-or-area>) -->

- [ ] 2026-11-10 — Drop the obsolete `extra.typo3/cms.app-dir` config; cms-composer-installers v5 warns that the app dir is always the Composer root. (from: TYPO3 12.4 dependency install)
- [ ] 2026-11-10 — Run `ci:libs:pack` on PHP 8.3/8.4 only; the 0.9 bundle needs PHP ≤ 8.4 while the 2.1 bundle needs PHP ≥ 8.3. (from: PHP 8.5 jsonpath bundles)
