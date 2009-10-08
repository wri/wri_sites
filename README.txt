;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Field Permissions module
;; $Id$
;;
;; Original author: markus_petrux (http://drupal.org/user/39593)
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

CONTENTS OF THIS FILE
=====================
* OVERVIEW
* USAGE
* REQUIREMENTS
* INSTALLATION


OVERVIEW
========

The Field Permissions module is a drop-in replacement for the Content
Permissions module shipped with CCK.

The key differences are:

  * It allows site administrators to set field-level permissions to edit or
    view CCK fields in any content, and optionally, edit or view permissions
    for content owned by the current user.

  * Permissions for each field are not created by default. Instead,
    administrators can enable these permissions explicitly for the fields
    where this feature is needed.


USAGE
=====

Once Field Permissions module is installed, you need to edit the field settings
form to enable permissions for each where you need this feature. You can choose
between:

  * Disabled.
  * Enable view and edit permissions for this field in any content.
  * Enable view and edit permissions for this field in any content and content
    owned by the current user.

Use this option to enable role based permissions for this field. When
permissions are enabled, access to this field is denied by default. You should
assign permissions to the proper user roles from the permissions administration
page. On the other hand, when this option is disabled, field permissions are
inherited from content view and/or edit permissions. ie. users allowed to view
a particular content will also be able to view all fields in that content when
this option is not enabled.


REQUIREMENTS
============

- Field module (Drupal core).


INSTALLATION
============

- Be sure to install all dependent modules.

- Copy all contents of this package to your modules directory preserving
  subdirectory structure.

- Go to Administer -> Site building -> Modules to install module.

- Review the settings of your fields. You will find a new option labelled
  "Field permissions" that allows you to enable permissions per field. It
  is disabled by default.
