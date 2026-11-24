# Changelog

## 0.8.41

- Fail the release build when a post type or taxonomy borrows a core capability (`manage_options`, `edit_theme_options`, …) while registering meta caps, so this class of bug cannot ship again. Registrations that pass their arguments in a variable are resolved from a simple `$args = array( … )` assignment, and the build fails when it cannot read the registration at all.

## 0.8.40

- Fix `wm_contact_message` CPT hijacking the core `manage_options` capability, which caused 403 errors on all Settings API forms.
