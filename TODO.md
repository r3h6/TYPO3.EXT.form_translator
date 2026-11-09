# Deferred Work

Out-of-scope items noticed while working on a change. Keep entries to one line;
delete them when done or promoted into a planned change.

<!-- Format: - [ ] YYYY-MM-DD — What and why. (from: <change-or-area>) -->

- [ ] 2026-11-10 — Drop the obsolete `extra.typo3/cms.app-dir` config; cms-composer-installers v5 warns that the app dir is always the Composer root. (from: TYPO3 12.4 dependency install)
- [ ] 2026-11-10 — Run `ci:libs:pack` on PHP 8.3/8.4 only; the 0.9 bundle needs PHP ≤ 8.4 while the 2.1 bundle needs PHP ≥ 8.3. (from: PHP 8.5 jsonpath bundles)
- [ ] 2026-11-14 — Remove `--no-suggest` from the `composer require` call in `.github/workflows/ci.yml`; current Composer no longer supports the option. (from: ci.yml checkout update)
- [ ] 2026-11-07 — `Form/Localize.html` uses `{typo3Language.title}` although the controller assigns `language`, so the language column header is empty. (from: shared translation files)
- [ ] 2026-11-07 — Page caches are not flushed when form translations change, so translated forms can stay cached. (from: shared translation files)
- [ ] 2026-11-07 — Rewrite `Resources/Public/JavaScript/Mod.js` without jQuery; TYPO3 v15 removes jQuery from the backend. (from: v14 compatibility)
- [ ] 2026-11-07 — `SharedTranslationFilesUpdateWizard` cannot see database-stored forms in Install Tool context because there is no backend user. (from: v14 compatibility)
- [ ] 2026-11-07 — Add `extra.typo3/cms.version` and `Package.providesPackages` to `composer.json` to silence the TYPO3 14.2 `ext_emconf.php` deprecation; needs a reliable version-bump process and changes the package dependency set used by the testing framework. (from: v14 compatibility)
- [ ] 2026-11-07 — TYPO3 v14 ignores `extra.typo3/cms.app-dir` and uses the Composer root as app dir; revisit the dev setup/DDEV docroot for v14. (from: v14 compatibility)
