# Open Archive Initiative > Repository

* [Requirements](#requirements)
* [Installation](#installation)
* [Goal](#goal)
* [Supported Metadata formats](#supported-metadata-formats)
* [Supported Verbs](#supported-verbs)
  * [Identify](#identify)
  * [List Metadata Formats](#list-metadata-formats)
  * [List Records](#list-records)
  * [List Identifiers](#list-identifiers)
  * [List Sets (TBA)](#list-sets-tba)
  * [Get Record](#get-record)
* [Configuration](#configuration)
  * [OAI Record Managers](#oai-record-managers)
  * [OAI fields](#oai-fields)
* [Routing](#routing)
* [Populating your initial set of OAI Records](#populating-your-initial-set-of-oai-records)
* [License](#license)
* [Maintainers](#maintainers)
* [Development and contribution](#development-and-contribution)

## Requirements

* SilverStripe ^4.10
* PHP ^7.4

## Installation

```
composer require silverstripe-terraformers/open-archive-initiative-repository
```

## Goal

The goal of this module is to provide you with an easy entry point to start serving content from your website as an
[Open Archive Initial Repostory](http://www.openarchives.org/OAI/openarchivesprotocol.html#Repository).

This module does **not** include anything to help with becoming an
[Open Archive Initiative Harvester](http://www.openarchives.org/OAI/openarchivesprotocol.html#harvester).

We are still new to the OAI spec, so we will be doing our absolute best to get everything right. Test coverage for what
has been built is very high, but that doesn't help if we've just gotten something incorrect in the spec, so please be
prepared to contribute your thoughts and/or code to help improve this module.

## Supported Metadata formats

We are currently only building support for `oai_dc`, however, we will be doing our best to build this in a way that will
allow you to easily supplement this module with additional Metadata format support.

## Supported Verbs

### Identify

Repository name: The default is simply using the Site Title that you have set in the CMS
Admin email: Set using the environment variable `OAI_API_ADMIN_EMAIL`

Please check out `OaiController::Identify()`. There are many options there for how you can configure different values
for this verb.

### List Metadata Formats

The response for this endpoint is generated based on the config you specify for `OaiController::$supported_formats`.

### List Records

This endpoint requires that the request includes a `metadataPrefix` parameter value that matches one of the configs
that you have specified for `OaiController::$supported_formats`.

The output of this endpoint is based on your current OAI Records

Filter support:

* `from`: specifies a lower bound for datestamp-based selective harvesting. UTC+0 datetimes must be provided.
* `until`: specifies an upper bound for datestamp-based selective harvesting. UTC+0 datetimes must be provided.
* `resumptionToken`: Default to 1 hour expiry time. This can be updated through the
  `OaiController::$resumption_token_expiry` config
* `set`: TBA

### List Identifiers

Same support as List Records. The only difference is that List Identifiers only provides `headers` for each OAI Record.

### List Sets (TBA)

TBA

### Get Record

Requires harvesters to provide a `metadataPrefix` and `identifier`.

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

You can have a look in `OaiRecord` for a list of MANAGED_FIELDS. All of which support you adding CSV values for when
you need to have multiple of one field.

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

## Populating your initial set of OAI Records

Because the module can't know what `DataObjects` you wish to apply the `OaiRecordManager` to, we have opted **not** to
add any dev task to populate initial `OaiRecords`. However, below is an example build task that you might adapt/own/use.

In this example we have applied the `OaiRecordManager` to `Page` and `File`.

```php
<?php

namespace App\Tasks;

... imports

class CreateInitialOaiRecords extends BuildTask
{

    private static $segment = 'create-initial-oai-records'; // phpcs:ignore

    protected $title = 'Create Initial OAI Records'; // phpcs:ignore

    protected $description = 'Create/update OAI Records for all Pages and Documents'; // phpcs:ignore

    /**
     * @param HTTPRequest $request
     * @return void
     */
    public function run($request) // phpcs:ignore SlevomatCodingStandard.TypeHints
    {
        $classes = [
            Page::class,
            File::class,
        ];

        foreach ($classes as $class) {
            // Set our stage to LIVE so that we only fetch DataObjects that are available on the frontend. This isn't
            // totally necessary since the Queued Job will validate this itself, but it saves us from queueing Jobs that
            // we know we don't need
            /** @var DataList|OaiRecordManager[] $dataObjects */
            $dataObjects = Versioned::withVersionedMode(static function () use ($class): DataList {
                Versioned::set_stage(Versioned::LIVE);

                return DataObject::get($class);
            });

            // Easy as, just triggerOaiRecordUpdate(). This method + the queued job will take care of the rest
            foreach ($dataObjects as $dataObject) {
                $dataObject->triggerOaiRecordUpdate();
            }
        }

        echo 'Finished queueing OAI Record update Jobs';
    }

}

```

## License

See [License](license.md)

## Maintainers

* Chris Penny <chris.penny@gmail.com>
* Melissa Wu <melissa.wu@silverstripe.com>

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module
maintainers.
