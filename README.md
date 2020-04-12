## Donation
If you find this plugin useful, please consider making a donation. Thank you!

[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HQFGGDAGHHM22)

# Newsletter Plugin

The **Newsletter** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav). It allows you to send email newsletters to a list of opt-in subscribers. It will automatically process unsubscribers before adding to email queue, or send right away if you're not using the queue. The greeting is personalized for every user! The email body supports markdown.

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
enabled: true                               # Required
log_enabled: false                          # Defaults to false
log: null                                   # Defaults to /logs/newsletter.log
sub_page_route: null                        # Defaults to /user/data/newsletter
unsub_page_route: null                      # Defaults to /user/data/newsletter-unsub
email_from: null                            # Defaults to email plugin default from
email_from_name: null                       # Defaults to email plugin default from name
flush_email_queue_preview: true             # Defaults to true
flush_email_queue_send: false               # Defaults to false
```

**Optional**: To allow registered members of your site to subscribe without filling out the form, add the newsletter field to
/user/config/plugins/login.yaml. Example:

```yaml
user_registration:
  enabled: false
  fields:
    - username
    - password
    - email
    - newsletter
    - fullname
    - title
    - level
    - twofa_enabled
```

**Optional cont.**: Then override the user profile page and add the checkbox to the profile. Example:

```yaml
newsletter:
    type: checkbox
    label: 'Subscribe to newsletter'
```

![Screenshot of admin config screen](https://github.com/marknokes/grav-plugin-newsletter/blob/master/screenshot.png)

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

* To create the admin plugin page, I looked high and low, but ultimately used the code found in the [Grav Comments Plugin](https://github.com/getgrav/grav-plugin-comments)
* I also would have spent far more time on it without the help of [Grav Dev Tools](https://github.com/getgrav/grav-plugin-devtools), upon which I stumbled while searching for tips and tricks.
* Big thanks to [the Man Things blog](https://manthings.net) for being a hilarious and inspiring website. Yeah, it's mine.

## To Do

- [ ] Error/Exception handling.
- [x] Figure out how to process Twig on PHP plugin side allowing predefined variables to be replaced in subject, greeting, and body before emails are sent.
- [ ] There should be some kind of list display/management...perhaps I'll get to that.
- [x] Send using templates Note: I only sort of got to this, but I like the way it's working. You can make one template.
- [ ] Integration with external email sending/list management API's
