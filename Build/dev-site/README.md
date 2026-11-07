# dev-site

Development distribution sitepackage for the `form_translator` dev environment.

It contains the demo site (5 languages), the example form and the `sys_template` used to render the
page. The database content is exported into `Initialisation/data.xml`; the site configuration is kept in
`Initialisation/Site/main/config.yaml`.

## Languages

The demo site has five site languages, but the form translator offers three translation targets:

- `default` for the English site languages (`en_US` and `en_GB`).
- `de` for the German site languages (`de_DE` and `de_AT`).
- `de_CH` for the Swiss site language (languageId 2), so its translations stay distinct from plain
  `de`. TYPO3 has no built-in `de_CH` language key, so `ddev dev-setup` adds it to
  `EXTENSIONS/form_translator/typo3LanguageWhitelist`. The generated form translation files are
  `de_CH.<form>.xlf`; the German language keeps `de.<form>.xlf`.

## Export the current development site

Regenerate `Initialisation/data.xml` and `Initialisation/data.xml.files/` from the running MySQL
database whenever the page tree, form, or template changes. Do not edit these files by hand.

Export with the `rootPageId` from `config/sites/main/config.yaml`, then copy the result from
`fileadmin/user_upload/_temp_/importexport/` into `Initialisation/`:

```bash
ddev typo3 impexp:export data --type=xml --pid=<rootPageId> --levels=999 --table=_ALL \
    --include-related=_ALL --exclude=be_users:1 --exclude=sys_log:1 \
    --title=dev-site --description='Development distribution for r3h6/form-translator' \
    --save-files-outside-export-file
ddev exec cp -R .Build/public/fileadmin/user_upload/_temp_/importexport/data.xml \
    Build/dev-site/Initialisation/
ddev exec cp -R .Build/public/fileadmin/user_upload/_temp_/importexport/data.xml.files \
    Build/dev-site/Initialisation/
```

If the export fails because `tx_impexp_presets` is missing, create the impexp tables once with:

```bash
ddev typo3 extension:setup -e impexp
```

## Set up a fresh development environment

Start DDEV, install the Composer dependencies and run the setup command:

```bash
ddev start
ddev composer install
ddev dev-setup
```

`ddev dev-setup` installs TYPO3 into an empty database (backend admin `admin` with
password `Password$1`), runs `typo3 extension:setup`, which imports the page tree and
form, writes the site configuration with the imported root page id, and repairs the
form persistence identifier that impexp rewrites during the import. It is safe to run
again: an existing installation is kept and the import is skipped via `sys_registry`.

The install step only runs against an empty database. If the database contains tables
but TYPO3 is not installed, reset the database and run the command again.

## Notes

- The site configuration template is `Build/dev-site/Initialisation/Site/main/config.yaml`. Edit that
  file; `ddev dev-setup` copies it to the git-ignored `config/sites/main/config.yaml` and sets
  `rootPageId` to the imported root page on every run.
- The export excludes the backend admin record (`be_users:1`).
- TYPO3 remembers a successful import via `sys_registry`. To re-import the same snapshot into the same
  database, use a fresh database or delete the `extensionDataImport` entry for
  `Build/dev-site/Initialisation/dataImported`.
