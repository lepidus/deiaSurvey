**English** | [Português Brasileiro](/docs/README-pt_BR.md)

# Demographic Data

This plugin allows the collection of demographic data from users via a questionnaire.

## Compatibility

The latest release of this plugin is compatible with the following PKP applications:

* OJS 3.4.0

Using PHP 8.1 or later.

## Plugin Download

To download the plugin, go to the [Releases page](https://github.com/lepidus/demographicData/releases) and download the tar.gz package of the latest release compatible with your website.

## Installation

1. Enter the administration area of ​​your application and navigate to `Settings`>` Website`> `Plugins`> `Upload a new plugin`.
2. Under __Upload file__ select the file __demographicData.tar.gz__.
3. Click __Save__ and the plugin will be installed on your website.

## Instructions for use
Once enabled, the demographic questionnaire is displayed in the users profile page. When accessing this page, the user will find a new tab, called "Demographic Data", where they can consent to answer the questionnaire or not.

![](docs/screenshots/Questionnaire-en.png)

For authors who are not registered in the system, an e-mail is sent when the submission is accepted, requesting them to fill in the demographic questionnaire. This e-mail is only sent to authors where there is no user with the same e-mail address in the system.

The e-mail sent offers two ways of filling in the data. The first requires an ORCID record to be authenticated, so that the data is associated with this record. The second uses the author's e-mail address and is recommended only for those who do not have an ORCID record.

Users can view and delete their demographic data at any time. For the authors without registration who answered the questionnaire, if they create an account in the system with the e-mail address or ORCID used, their data will be migrated to this new user.

## Credits
This plugin was sponsored by the Lepidus Tecnologia, Scientific Electronic Library Online (SciELO), Revista Encontros Bibli (UFSC) and others (we'll update soon).

Developed by Lepidus Tecnologia.

## License

__This plugin is licensed under the GNU General Public License v3.0__

__Copyright (c) 2024 Lepidus Tecnologia__