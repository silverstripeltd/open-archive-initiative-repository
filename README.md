# Open Archive Initiative

## Requirements

* SilverStripe ^4.10
* PHP ^7.4

## Installation

```
composer require silverstripe-terraformers/open-archive-initiative
```

## Goal

The goal of this module is to provide you with an easy entry point to start serving content from your website as an
[Open Archive Initial Repostory](http://www.openarchives.org/OAI/openarchivesprotocol.html#Repository).

This module does **not** include anything to help with becoming an
[Open Archive Initiative Harvester](http://www.openarchives.org/OAI/openarchivesprotocol.html#harvester).

I am still new to the OAI spec, so I will be doing my absolute best to get everything right, but you're going to have
to show some patience, and you should be prepared to contribute your thoughts and/or code to help improve this module.

## Metadata formats support

I am currently only building support for OAI_DC, however, I will be doing my best to build this in a way that will allow
you to easily supplement this module with additional Metadata format support.

## Supported Verbs

### Identify

Mandatory environment variable: `OAI_API_ADMIN_EMAIL`

Please check out `OaiController::Identify()`. There are many options there for how you can configure different values
for this verb.

### List Metadata Formats

TBA

### List Sets

TBA

### List Identifiers

TBA

### List Records

TBA

### Get Record

TBA

## License

See [License](license.md)

## Maintainers

* Chris Penny <chris.penny@gmail.com>
* Melissa Wu <melissa.wu@silverstripe.com>

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module
maintainers.
