# Newsletter Plugin

The **Newsletter** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav). Send email newsletters to a list of opt-in subscribers. Automatically process unsubscribers before adding to email queue, or send right away if you're not using the queue. The greeting is personalized for every user! The email body supports markdown.

## Installation

Installing the Newsletter plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install newsletter

This will install the Newsletter plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/newsletter`.

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `newsletter`. You can find these files on [GitHub](https://github.com/marknokes/grav-plugin-newsletter) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/newsletter
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com/marknokes/grav-plugin-newsletter/blob/master/blueprints.yaml).

### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/newsletter/newsletter.yaml` to `user/config/plugins/newsletter.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true             # Required
log: ''                   # Defaults to '/logs/newsletter.log'
data_dir: ''              # Defaults to '/user/data'
sub_page_route: ''        # Defaults to '/newsletter'
unsub_page_route: ''      # Defaults to '/newsletter-unsub'
email_from: ''            # Defaults to email plugin default from
```

Note that if you use the Admin Plugin, a file with your configuration named newsletter.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

* Create subscribe and unsubscribe form pages according to the [Example: Contact Form instructions](https://learn.getgrav.org/16/forms/forms/example-form) on the Grav website. There **must** be a field called name and a field called email for the subscribe form. The unsubscribe form only requires the email field.
Example:
```yaml
fields:
    name:
        label: Name
        .....
    email:
        label: Email
        .....
```
* The routes of each form should be noted in the newsletter.yaml file in your user/config/plugins/ directory if they are different from the default values.
* Optionally set the extension to yaml instead of txt. The plugin will parse either.
* Double check that your email settings are configured properly. Optionally override the "from" address in the email plugin with the newsletter.yaml config.

## Credits

To create the admin plugin page, I looked high and low, but ultimately used the code found in the [Grav Comments Plugin](https://github.com/getgrav/grav-plugin-comments)

## To Do

- [ ] Figure out how to process Twig on PHP plugin side allowing predefined variables to be replaced in subject, greeting, and body before emails are sent.
- [ ] There should be some kind of list display/management...perhaps I'll get to that.

