import re

files_and_replacements = {
    'template-parts/home/hero-slider.php': (
        r"\$slides = wm_home_get_option\( 'home_hero_slides', array\(\) \);\s*\$slides = array_values\(\s*array_filter\(\s*\(array\) \$slides,\s*function\( \$slide \) \{\s*return ! empty\( \$slide\['slide_enabled'\] \) && \( ! empty\( \$slide\['slide_title'\] \) \|\| ! empty\( \$slide\['slide_image_desktop'\] \) \);\s*\}\s*\)\s*\);",
        """$slides = wm_home_get_valid_items(
    'home_hero_slides',
    function( $slide ) {
        return ! empty( $slide['slide_enabled'] ) && ( ! empty( $slide['slide_title'] ) || ! empty( $slide['slide_image_desktop'] ) );
    }
);"""
    ),
    'template-parts/home/popular-styles.php': (
        r"\$items = wm_home_get_option\( 'home_popular_styles', array\(\) \);\s*\$items = array_values\(\s*array_filter\(\s*\(array\) \$items,\s*function\( \$item \) \{\s*return ! empty\( \$item\['style_enabled'\] \) && ! empty\( \$item\['style_title'\] \) && \( ! empty\( \$item\['style_url'\] \) \|\| ! empty\( \$item\['style_term'\] \) \);\s*\}\s*\)\s*\);",
        """$items = wm_home_get_valid_items(
    'home_popular_styles',
    function( $item ) {
        return ! empty( $item['style_enabled'] ) && ! empty( $item['style_title'] ) && ( ! empty( $item['style_url'] ) || ! empty( $item['style_term'] ) );
    }
);"""
    ),
    'template-parts/home/brand-categories.php': (
        r"\$brands = wm_home_get_option\( 'home_brand_items', array\(\) \);\s*\$brands = array_values\(\s*array_filter\(\s*\(array\) \$brands,\s*function\( \$item \) \{\s*return ! empty\( \$item\['brand_enabled'\] \) && ! empty\( \$item\['brand_term'\] \);\s*\}\s*\)\s*\);",
        """$brands = wm_home_get_valid_items(
    'home_brand_items',
    function( $item ) {
        return ! empty( $item['brand_enabled'] ) && ! empty( $item['brand_term'] );
    }
);"""
    ),
    'template-parts/home/trust.php': (
        r"\$items = wm_home_get_option\( 'home_trust_items', array\(\) \);\s*\$items = array_values\(\s*array_filter\(\s*\(array\) \$items,\s*function\( \$item \) \{\s*return ! empty\( \$item\['trust_enabled'\] \) && ! empty\( \$item\['trust_title'\] \);\s*\}\s*\)\s*\);",
        """$items = wm_home_get_valid_items(
    'home_trust_items',
    function( $item ) {
        return ! empty( $item['trust_enabled'] ) && ! empty( $item['trust_title'] );
    }
);"""
    )
}

for file_path, (pattern, replacement) in files_and_replacements.items():
    with open(file_path, 'r') as f:
        content = f.read()

    new_content, count = re.subn(pattern, replacement, content)
    if count == 0:
        print(f"Warning: Could not find match in {file_path}")
    else:
        with open(file_path, 'w') as f:
            f.write(new_content)
        print(f"Updated {file_path}")
