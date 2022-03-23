# Open Archive Initiative

* [Requirements](#requirements)
* [Installation](#installation)
* [Goal](#goal)
* [Supported Metadata formats](#supported-metadata-formats)
* [Supported Verbs](#supported-verbs)
  * [Identify](#identify)
  * [List Metadata Formats](#list-metadata-formats)
  * [List Sets](#list-sets)
  * [List Identifiers](#list-identifiers)
  * [List Records](#list-records)
  * [Get Record](#get-record)
* [Configuration](#configuration)
  * [OAI Record Managers](#oai-record-managers)
  * [OAI fields](#oai-fields)
* [Routing](#routing)
* [License](#license)
* [Maintainers](#maintainers)
* [Development and contribution](#development-and-contribution)

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

I am still new to the OAI spec, so we will be doing our absolute best to get everything right. Test coverage for what
has been built is very high, but that doesn't help if we've just gotten something incorrect in the spec, so please be
prepared to contribute your thoughts and/or code to help improve this module.

## Supported Metadata formats

We are currently only building support for `oai_dc`, however, we will be doing our best to build this in a way that will
allow you to easily supplement this module with additional Metadata format support.

## Supported Verbs

### Identify

Recommended environment variable: `OAI_API_ADMIN_EMAIL`

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

## OAI Records

All data provided through the API is driven by `OaiRecords`.

Each `OaiRecord` is associated to one of your `DataObjects`, however, it indexes all of the content that it cares about
so that fetching data for the API does not require you to fetch associated `DataObjects`.

When/where to `OaiRecords` get updated with data? See [OAI Record Managers](#oai-record-managers).

## Configuration

### OAI Record Managers

There are two "Record Manager" Extensions that you can apply to appropriate `DataObjects`. The purpose of these
Managers is to control when we trigger updates on OAI Records for our `DataObjects`.

* `OaiRecordManager`: This is the standard Manager which should be used for `DataObjects` that are specifically **not**
  `Versioned`.
* `VersionedOaiRecordManager`: This extends the above and slightly tweeks which model actions we want to use. This
  should be used for all `DataObjects` that **are** `Versioned`.

Using the incorrect Manager for your `DataObject` might result in OAI Records updating at unexpected times.

All updates for `OaiRecords` are performed through Queued Jobs. Please see the docblock above
`OaiRecordManager::triggerOaiRecordUpdate()` for the rationale behind using Queued Jobs.

### OAI fields

This modules makes no assumptions about how you wish to populate OAI Record data. As such, you need to specify how your
`DataObjects` are going to map to the expected OAI fields.

You can have a look in `OaiRecord` for a list of MANAGED_FIELDS fields. All of which support you adding CSV values for
when you need to have multiple of one field.

A note on CSV parsing: If you're anticipating that some of your properties could contain commas, then you might instead
need to map to a method that appropriately wraps your property value in quotes. See OaiRecord for the supported
enclosure.

When configuring your field mappings, the array key should map to the Oai field, and the value can map to a property or
method in your class. We use `relField()` to fetch from your class, so you can use any mapping that this framework
method supports.

Configure `oai_fields` in your class:
```php
private static array $oai_fields = [
    // Key = the OAI field name (Title)
    // Value = a property on your model (Title)
    'Title' => 'Title',
    // Key = the OAI field name (Description)
    // Value = a method in your model (getDescription)
    'Description' => 'getDescription',
];
```

Configure `oai_fields` in yaml:
```yaml
App\MyClass:
  oai_fields:
    Title: Title
    Description: getDescription
```

## Routing

Routing for the api is:
```
/api/v1/oai
```

If you need to change this route, then you can do so by overriding the routing configuration.

```yaml
---
name: myapp-openarchive-routes
After:
  - '#rootroutes'
  - '#coreroutes'
  - '#terraformers-openarchive-routes'
---
SilverStripe\Control\Director:
  rules:
    'api/v1/oai': MyApp\OpenArchive\OaiController
```

## License

See [License](license.md)

## Maintainers

* Chris Penny <chris.penny@gmail.com>
* Melissa Wu <melissa.wu@silverstripe.com>

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module
maintainers.
