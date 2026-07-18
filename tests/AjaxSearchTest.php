<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

if (!class_exists('WC_Product')) {
    class WC_Product {
        public function is_visible() { return true; }
        public function get_image_id() { return 0; }
        public function get_id() { return 1; }
        public function get_name() { return 'Test'; }
        public function get_price_html() { return '$10'; }
    }
}

class AjaxSearchTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // Mock standard WP functions
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('absint')->returnArg();
        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('wm_search_get_option')->justReturn(6);

        if (!function_exists('wc_get_products')) {
            function wc_get_products($args = []) {
                return [];
            }
        }

        require_once __DIR__ . '/../inc/ajax/search.php';
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
        unset($_GET['term']);
    }

    public function test_missing_term() {
        Functions\expect('wp_send_json_success')
            ->once()
            ->with(['results' => []])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_empty_term() {
        $_GET['term'] = '';

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(['results' => []])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_short_term() {
        $_GET['term'] = 'a';

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(['results' => []])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_valid_term_no_results() {
        $_GET['term'] = 'test';

        // Redefining mock for wc_get_products to return empty array
        Functions\expect('wc_get_products')
            ->once()
            ->with([
                's'       => 'test',
                'status'  => 'publish',
                'limit'   => 6,
                'orderby' => 'relevance',
            ])
            ->andReturn([]);

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(['results' => []])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_valid_term_with_results() {
        $_GET['term'] = 'test';

        $product = \Mockery::mock('WC_Product');
        $product->shouldReceive('is_visible')->once()->andReturn(true);
        $product->shouldReceive('get_image_id')->once()->andReturn(123);
        $product->shouldReceive('get_id')->zeroOrMoreTimes()->andReturn(1);
        $product->shouldReceive('get_name')->once()->andReturn('Test Product');
        $product->shouldReceive('get_price_html')->once()->andReturn('$10.00');

        Functions\expect('wc_get_products')
            ->once()
            ->andReturn([$product]);

        $brand_term = new \stdClass();
        $brand_term->name = 'Test Brand';

        Functions\expect('wm_get_product_brand_terms')
            ->once()
            ->with(1)
            ->andReturn([$brand_term]);

        Functions\expect('get_permalink')
            ->once()
            ->with(1)
            ->andReturn('http://test.com/product/1');

        Functions\expect('wp_get_attachment_image_url')
            ->once()
            ->with(123, 'woocommerce_thumbnail')
            ->andReturn('http://test.com/image.jpg');

        Functions\expect('wp_send_json_success')
            ->once()
            ->with([
                'results' => [
                    [
                        'id'        => 1,
                        'title'     => 'Test Product',
                        'permalink' => 'http://test.com/product/1',
                        'image'     => 'http://test.com/image.jpg',
                        'price'     => '$10.00',
                        'brand'     => 'Test Brand',
                    ]
                ]
            ])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_valid_term_with_cat_fallback() {
        $_GET['term'] = 'test';

        $product = \Mockery::mock('WC_Product');
        $product->shouldReceive('is_visible')->once()->andReturn(true);
        $product->shouldReceive('get_image_id')->once()->andReturn(123);
        $product->shouldReceive('get_id')->zeroOrMoreTimes()->andReturn(1);
        $product->shouldReceive('get_name')->once()->andReturn('Test Product');
        $product->shouldReceive('get_price_html')->once()->andReturn('$10.00');

        Functions\expect('wc_get_products')
            ->once()
            ->andReturn([$product]);

        Functions\expect('wm_get_product_brand_terms')
            ->once()
            ->with(1)
            ->andReturn([]);

        $cat_term = new \stdClass();
        $cat_term->name = 'Test Category';

        Functions\expect('wm_get_product_category_terms')
            ->once()
            ->with(1)
            ->andReturn([$cat_term]);

        Functions\expect('get_permalink')
            ->once()
            ->with(1)
            ->andReturn('http://test.com/product/1');

        Functions\expect('wp_get_attachment_image_url')
            ->once()
            ->with(123, 'woocommerce_thumbnail')
            ->andReturn('http://test.com/image.jpg');

        Functions\expect('wp_send_json_success')
            ->once()
            ->with([
                'results' => [
                    [
                        'id'        => 1,
                        'title'     => 'Test Product',
                        'permalink' => 'http://test.com/product/1',
                        'image'     => 'http://test.com/image.jpg',
                        'price'     => '$10.00',
                        'brand'     => 'Test Category',
                    ]
                ]
            ])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_valid_term_no_brand_or_cat() {
        $_GET['term'] = 'test';

        $product = \Mockery::mock('WC_Product');
        $product->shouldReceive('is_visible')->once()->andReturn(true);
        $product->shouldReceive('get_image_id')->once()->andReturn(123);
        $product->shouldReceive('get_id')->zeroOrMoreTimes()->andReturn(1);
        $product->shouldReceive('get_name')->once()->andReturn('Test Product');
        $product->shouldReceive('get_price_html')->once()->andReturn('$10.00');

        Functions\expect('wc_get_products')
            ->once()
            ->andReturn([$product]);

        Functions\expect('wm_get_product_brand_terms')
            ->once()
            ->with(1)
            ->andReturn([]);

        Functions\expect('wm_get_product_category_terms')
            ->once()
            ->with(1)
            ->andReturn([]);

        Functions\expect('get_permalink')
            ->once()
            ->with(1)
            ->andReturn('http://test.com/product/1');

        Functions\expect('wp_get_attachment_image_url')
            ->once()
            ->with(123, 'woocommerce_thumbnail')
            ->andReturn('http://test.com/image.jpg');

        Functions\expect('wp_send_json_success')
            ->once()
            ->with([
                'results' => [
                    [
                        'id'        => 1,
                        'title'     => 'Test Product',
                        'permalink' => 'http://test.com/product/1',
                        'image'     => 'http://test.com/image.jpg',
                        'price'     => '$10.00',
                        'brand'     => '',
                    ]
                ]
            ])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_valid_term_no_image() {
        $_GET['term'] = 'test';

        $product = \Mockery::mock('WC_Product');
        $product->shouldReceive('is_visible')->once()->andReturn(true);
        $product->shouldReceive('get_image_id')->once()->andReturn(false);
        $product->shouldReceive('get_id')->zeroOrMoreTimes()->andReturn(1);
        $product->shouldReceive('get_name')->once()->andReturn('Test Product');
        $product->shouldReceive('get_price_html')->once()->andReturn('$10.00');

        Functions\expect('wc_get_products')
            ->once()
            ->andReturn([$product]);

        Functions\expect('wm_get_product_brand_terms')
            ->once()
            ->with(1)
            ->andReturn([]);

        Functions\expect('wm_get_product_category_terms')
            ->once()
            ->with(1)
            ->andReturn([]);

        Functions\expect('get_permalink')
            ->once()
            ->with(1)
            ->andReturn('http://test.com/product/1');

        Functions\expect('wc_placeholder_img_src')
            ->once()
            ->with('woocommerce_thumbnail')
            ->andReturn('http://test.com/placeholder.jpg');

        Functions\expect('wp_send_json_success')
            ->once()
            ->with([
                'results' => [
                    [
                        'id'        => 1,
                        'title'     => 'Test Product',
                        'permalink' => 'http://test.com/product/1',
                        'image'     => 'http://test.com/placeholder.jpg',
                        'price'     => '$10.00',
                        'brand'     => '',
                    ]
                ]
            ])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_invisible_product() {
        $_GET['term'] = 'test';

        $product = \Mockery::mock('WC_Product');
        $product->shouldReceive('is_visible')->once()->andReturn(false);

        Functions\expect('wc_get_products')
            ->once()
            ->andReturn([$product]);

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(['results' => []])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }

    public function test_non_wc_product_instance() {
        $_GET['term'] = 'test';

        $product = new \stdClass(); // Not a WC_Product

        Functions\expect('wc_get_products')
            ->once()
            ->andReturn([$product]);

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(['results' => []])
            ->andReturnUsing(function($data) {
                throw new Exception('wp_send_json_success called');
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wp_send_json_success called');

        wm_ajax_search_products();
    }
}
