```
===============================================
=== PKP Static Pages Plugin
=== Version: 1.2
=== Author: Juan Pablo Alperin <pkp@alperin.ca>
===============================================
```
## About

This plugin is intended to provide very simple content management. It allows
for the creation of static content pages with the assistance of an HTML editor.

## License

This plugin is licensed under the GNU General Public License v2. See the file
COPYING for the complete terms of this license.

## System Requirements

This plugin is compatible with...
 - OJS 3.0.x
 - OMP 1.2.x
 - OMP 1.1.x
It will NOT currently work with `path_info_disabled = On` in `config.inc.php`.

## Installation

To install the plugin:
 - Unpack the plugin tar.gz file to your `plugins/generic` directory
 - From your application's installation directory, run the upgrade script:
   ``` 
    $ php tools/upgrade.php upgrade
   ```
   (NOTE: It is recommended to back up your database first.)
   ```
    $ php tools/upgrade.php upgrade
   ``` 
 - Enable the plugin by going to:
   `Management` > `Website Settings` > `Plugins` > `Generic Plugins`
   ...and selecting the Enable checkbox beside `Static Pages Plugin`

## Management

New pages can be added/edited/deleted through the Plugin Management interface.

The `PATH` chosen for each page determines where the page is later accessed. To
direct users to static content created with this plugin, place links to
`http://www.../index.php/pages/view/%PATH%`, where `%PATH%` is a value you choose.

## Contact/Support

Documentation, bug listings, and updates can be found on this plugin's homepage
at <http://github.com/pkp/staticPages>.
