# form_translator

This extension provides a backend module for translating TYPO3 form framework forms
and a cli for creating a source xliff file for a given form.

## Installation

Either from TYPO3 TER or through composer `$ composer req r3h6/form-translator`.

## Integration

If you like use machine translation by [LibreTranslate](https://libretranslate.com/)
you must only configure a api host in the extension configuration.
See available [mirrors](https://github.com/LibreTranslate/LibreTranslate#mirrors).

## Develpment/Contribution

Pull request are welcome!

__Please note__: I will not include other translation api's than LibreTranslate in this extension.
If you need an other service, you can create your own by implementing `TranslationServiceInterface`.
