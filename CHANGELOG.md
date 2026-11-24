# Changelog

## 0.8.42

- Resolve a simple `$args = array( … )` assignment in the release build's capability guard, and fail the build when a registration's arguments cannot be read statically instead of passing silently.

## 0.8.41

- Fail the release build when a post type or taxonomy borrows a core capability (`manage_options`, `edit_theme_options`, …) while registering meta caps, so this class of bug cannot ship again.

## 0.8.40

- Fix `wm_contact_message` CPT hijacking the core `manage_options` capability, which caused 403 errors on all Settings API forms.
