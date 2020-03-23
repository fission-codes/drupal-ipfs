CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Features
 * Troubleshooting
 * Contributors

INTRODUCTION
------------

 Provides integration with IPFS via stream wrappers.

 The recommended usage is to configure file or image fields to use IPFS as the
 upload destination. [Image styles](https://www.drupal.org/docs/user_guide/en/structure-image-styles.html)
 are supported for image fields, and the `public` schema will be used to store
 the image variations.

INSTALLATION
------------

 * Since this module depends on an external PHP library, it needs to be installed
   with Composer, see https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies
   for further information.

CONFIGURATION
-------------

 * The IPFS gateway can be configured at `/admin/config/media/ipfs`.
 * There are two stream wrappers to choose from, a raw IPFS wrapper and a Fission
version.
 * The Fission API endpoint requires a username and password for POSTing new
files. See https://guide.fission.codes/ for information on how to install Fission and
register a Fission account.
 * When adding File or Image fields to an Entity, select IPFS as the Upload Destination.
 * If you would like to display images or link to files from the IPFS, go to "Manage Display"
 on the entity bundle and select an IPFS field formatter.
 
 FEATURES
 --------
 
There are various challenges with having the entire `public://` file system in Drupal be handled by a third-party system. This also affects the Amazon S3 module. We will collect some research and post issues describing this.

IPFS is a good fit for making files available offline. So video or other large files would be a good fit for this.

As well, IPFS can provide sync capabilities, so a desktop integration where users can manage media assets locally, that are then reflected directly on the live Drupal site.

TROUBLESHOOTING
---------------


CONTRIBUTORS
-----------

 * Andrei Mateescu (https://www.drupal.org/u/amateescu)
 * Floyd Mann (https://www.drupal.org/u/floydm), working at [Affinity Bridge](https://affinitybridge.com), sponsored by [Fission](https://fission.codes)
