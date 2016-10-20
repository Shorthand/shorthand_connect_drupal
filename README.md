# shorthand_connect_drupal
Shorthand connect plugin for Drupal

This repository has a number of Drupal plugins packaged together.  Each of the plugins (the folders) can be added to {DRUPAL}/sites/all/modules.  Alternatively, the entire repository can act as the modules directory.

Once the modules are copied, login as an administrator into Drupal, and enable these modueles (modules, scroll to bottom):

Shorthand connect (Required)
Entity API (Required by the Shorthand connect plugin)
jQuery update (Optional, if you run into issues with editing stories try this)

Once these modules are enabled, click the "Configure" link on the Shorthand module.  Enter in the export users user ID and token (both of these are found in the account page in shorthand).  Once this is done, you should be able to add new Shorthand stories in the same way you add other Drupal content (Content > Add Content > Shorthand Story).

The plugin is in an early stage, and feedback is very welcome.
