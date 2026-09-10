<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class NavMenuGuardTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', true );
		}

		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when(
			'absint'
		)->alias(
			static function ( $value ) {
				return abs( (int) $value );
			}
		);

		require_once dirname( __DIR__ ) . '/inc/components/nav-menu-guard.php';
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	private function make_item( array $args ) {
		return (object) array_merge(
			array(
				'ID'               => 10,
				'title'            => '',
				'attr_title'       => '',
				'object'           => '',
				'object_id'        => 0,
				'url'              => '',
				'menu_item_parent' => 0,
			),
			$args
		);
	}

	public function test_persian_title_is_detected_as_wishlist() {
		$item = $this->make_item( array( 'title' => 'علاقه مندی ها', 'url' => 'https://example.com/page' ) );

		$this->assertTrue( wm_nav_item_is_wishlist( $item ) );
	}

	public function test_english_wishlist_url_is_detected() {
		$item = $this->make_item( array( 'title' => 'My List', 'url' => 'https://example.com/wishlist/' ) );

		$this->assertTrue( wm_nav_item_is_wishlist( $item ) );
	}

	public function test_page_object_with_wishlist_slug_is_detected() {
		$item = $this->make_item(
			array(
				'title'     => 'لیست من',
				'object'    => 'page',
				'object_id' => 42,
				'url'       => 'https://example.com/unknown/',
			)
		);

		Functions\expect( 'get_post_field' )
			->once()
			->with( 'post_name', 42 )
			->andReturn( 'favorites' );

		$this->assertTrue( wm_nav_item_is_wishlist( $item ) );
	}

	public function test_regular_menu_item_is_not_flagged() {
		$item = $this->make_item(
			array(
				'title'     => 'فروشگاه',
				'object'    => 'page',
				'object_id' => 7,
				'url'       => 'https://example.com/shop/',
			)
		);

		Functions\expect( 'get_post_field' )
			->once()
			->with( 'post_name', 7 )
			->andReturn( 'shop' );

		$this->assertFalse( wm_nav_item_is_wishlist( $item ) );
	}

	public function test_top_level_detection() {
		$top    = $this->make_item( array( 'menu_item_parent' => 0 ) );
		$nested = $this->make_item( array( 'menu_item_parent' => '15' ) );

		$this->assertTrue( wm_nav_item_is_top_level( $top ) );
		$this->assertFalse( wm_nav_item_is_top_level( $nested ) );
	}

	public function test_filter_removes_top_level_wishlist_only() {
		$shop          = $this->make_item( array( 'ID' => 1, 'title' => 'فروشگاه' ) );
		$wishlist_top  = $this->make_item( array( 'ID' => 2, 'title' => 'علاقه مندی ها', 'url' => 'https://example.com/wishlist/' ) );
		$account       = $this->make_item( array( 'ID' => 3, 'title' => 'حساب کاربری' ) );
		$wishlist_link = $this->make_item( array( 'ID' => 4, 'title' => 'علاقه مندی ها', 'url' => 'https://example.com/wishlist/', 'menu_item_parent' => 3 ) );

		$result = wm_nav_guard_filter_menu_objects( array( $shop, $wishlist_top, $account, $wishlist_link ) );

		$this->assertSame( array( $shop, $account, $wishlist_link ), $result );
	}

	public function test_filter_keeps_items_untouched_when_no_wishlist_present() {
		$shop    = $this->make_item( array( 'ID' => 1, 'title' => 'فروشگاه' ) );
		$account = $this->make_item( array( 'ID' => 3, 'title' => 'حساب کاربری' ) );

		$items = array( $shop, $account );
		$this->assertSame( $items, wm_nav_guard_filter_menu_objects( $items ) );
	}
}
