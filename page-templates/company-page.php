<?php
/**
 * Template Name: صفحه شرکتی (درباره ما / تماس با ما)
 * Template Post Type: page
 *
 * Clean full-width canvas for the company block family: renders the page's
 * blocks directly (no default page-header card / duplicate H1 — the
 * wm/page-hero block owns the H1), matching how front-page.php renders
 * block-managed homepages.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main wm-company-page">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>
<?php
get_footer();
