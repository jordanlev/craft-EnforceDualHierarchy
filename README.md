# Enforce Dual Hierarchy plugin for Craft CMS 2.x

Enforces a simple 2-level hierarchy of 2 entry types within a structure section.

For example, you have an employee listing, and each employee is in a department. You want the top-level of the section to be "department" pages, and the child pages of each department to be "employee detail" pages. Using this plugin adds some validation rules to this section of your site that ensures each entry type is only added at the appropriate level (so an "employee" page *must* be added underneath a "department" page).

This validation applies to both the "Structure Tree View" (where the entries of the section are drag/dropped around) and to the "edit entry" pages of the CP. Unfortunately, due to limitations of Craft, it is not possible to provide a specific error message in the "Structure Tree View"... instead a generic error is displayed to the content author.

In an ideal world, Craft would provide a more explicit and user-friendly way to define that only certain entry types should be allowed as children of other entry types. If you would like to see this feature added, please [go here and upvote it](https://github.com/craftcms/cms/issues/1628)!

## Instructions

 1. Edit the `enforcedualhierarchy/EnforceDualHierarchyPlugin.php` file, and change lines #15 thru #19 so they apply to your particular structure section.
 2. Install the plugin using the normal methods. _Note that this has only been tested with Craft version 2.x (*not* Craft 3)._

## Limitations
This plugin is super hacky and very specific to 1 use case (one entry type for parents, one entry type for children, and only a 2-level structure).

Ideally this plugin would have a CP interface for choosing the section and entry types it applied to, but for now you have to edit the code.

Hopefully better functionality to achieve this goal gets implemented by Pixel & Tonic in Craft 3, which would render this plugin moot. If you agree, go and [upvote the feature request](https://github.com/craftcms/cms/issues/1628)!