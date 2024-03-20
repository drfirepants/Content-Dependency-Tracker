# Content Dependency Tracker for Drupal

The Content Dependency Tracker is a Drupal module designed to enhance content management workflows by tracking and displaying dependencies between content entities. This module helps content editors and site administrators understand how content items are interconnected, particularly focusing on node and media entities. By identifying these dependencies, users can make more informed decisions when modifying or deleting content, ensuring the integrity and cohesion of site content.

## Features

- **Entity Reference Tracking**: Automatically identifies and tracks references made through entity reference fields in nodes and media entities.
- **Configurable Reference Fields**: Provides an administrative interface allowing users to select specific entity reference fields they wish to track dependencies for. By default, all user-created entity reference fields are tracked if none are explicitly selected.
- **Content Dependency Display**: Adds a section to the node and media edit forms that lists entities referencing the current item, enhancing content awareness.
- **Warning System**: Integrates a configurable warning message that alerts users to potential impacts when attempting to delete content that is referenced by other items. This feature aims to prevent accidental content removal that could lead to broken links or missing content dependencies.

## Usage

After installing the module, site administrators can configure which entity reference fields to track through the module's settings page. Once configured, content editors will see a new section in the edit forms of nodes and media entities, listing out all other entities that reference the current item. Additionally, a warning message will be displayed when editing content that is referenced by others, helping to prevent unintended content removal.

This module is particularly useful for sites with complex content structures and relationships, such as those with extensive cross-referencing between articles, media assets, and other content types.

## Contributing

Contributions are welcome! Whether you're fixing bugs, adding new features, or improving documentation, please feel free to submit pull requests or open issues to discuss proposed changes.
