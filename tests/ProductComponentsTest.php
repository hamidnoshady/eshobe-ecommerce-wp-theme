<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class ProductComponentsTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_wm_get_product_brand_terms_with_first_taxonomy() {
        Functions\expect('wm_get_terms_for_product')
            ->once()
            ->with(123, 'product_brand')
            ->andReturn(['Brand A']);

        $result = wm_get_product_brand_terms(123);
        $this->assertEquals(['Brand A'], $result);
    }

    public function test_wm_get_product_brand_terms_with_second_taxonomy() {
        Functions\expect('wm_get_terms_for_product')
            ->once()
            ->with(123, 'product_brand')
            ->andReturn([]);

        Functions\expect('wm_get_terms_for_product')
            ->once()
            ->with(123, 'pa_brand')
            ->andReturn(['Brand B']);

        $result = wm_get_product_brand_terms(123);
        $this->assertEquals(['Brand B'], $result);
    }

    public function test_wm_get_product_brand_terms_with_third_taxonomy() {
        Functions\expect('wm_get_terms_for_product')
            ->once()
            ->with(123, 'product_brand')
            ->andReturn([]);

        Functions\expect('wm_get_terms_for_product')
            ->once()
            ->with(123, 'pa_brand')
            ->andReturn([]);

        Functions\expect('wm_get_terms_for_product')
            ->once()
            ->with(123, 'brand')
            ->andReturn(['Brand C']);

        $result = wm_get_product_brand_terms(123);
        $this->assertEquals(['Brand C'], $result);
    }

    public function test_wm_get_product_brand_terms_with_no_taxonomy_match() {
        Functions\expect('wm_get_terms_for_product')
            ->times(3)
            ->andReturn([]);

        $result = wm_get_product_brand_terms(123);
        $this->assertEquals([], $result);
    }


    public function test_wm_get_guarantee_with_get_field_first_key() {
        Functions\stubs([
            'get_field' => function($key, $product_id) {
                if ($key === 'گارانتی' && $product_id === 123) {
                    return '12 months guarantee';
                }
                return '';
            },
            'wp_strip_all_tags' => function($value) {
                return $value;
            }
        ]);

        $result = wm_get_guarantee(123);
        $this->assertEquals('12 months guarantee', $result);
    }

    public function test_wm_get_guarantee_with_get_field_later_key() {
        Functions\stubs([
            'get_field' => function($key, $product_id) {
                if ($key === 'product_warranty' && $product_id === 123) {
                    return '2 years guarantee';
                }
                return '';
            },
            'wp_strip_all_tags' => function($value) {
                return $value;
            }
        ]);

        $result = wm_get_guarantee(123);
        $this->assertEquals('2 years guarantee', $result);
    }

    public function test_wm_get_guarantee_with_get_post_meta_direct_match() {
        // Mock get_field to return empty so it falls through to get_post_meta
        Functions\stubs([
            'get_field' => function() { return ''; }
        ]);

        Functions\expect('get_post_meta')
            ->once()
            ->with(123)
            ->andReturn(['گارانتی' => ['3 years guarantee']]);

        Functions\stubs([
            'wp_strip_all_tags' => function($value) {
                return $value;
            },
            'maybe_unserialize' => function($value) {
                return $value;
            }
        ]);

        $result = wm_get_guarantee(123);
        $this->assertEquals('3 years guarantee', $result);
    }

    public function test_wm_get_guarantee_with_get_post_meta_partial_match() {
        Functions\stubs([
            'get_field' => function() { return ''; }
        ]);

        Functions\expect('get_post_meta')
            ->once()
            ->with(123)
            ->andReturn(['custom_guarantee_field' => '4 years guarantee']);

        Functions\stubs([
            'wp_strip_all_tags' => function($value) {
                return $value;
            },
            'maybe_unserialize' => function($value) {
                return $value;
            }
        ]);

        $result = wm_get_guarantee(123);
        $this->assertEquals('4 years guarantee', $result);
    }

    public function test_wm_get_guarantee_empty() {
        Functions\stubs([
            'get_field' => function() { return ''; }
        ]);

        Functions\expect('get_post_meta')
            ->once()
            ->with(123)
            ->andReturn([]);

        $result = wm_get_guarantee(123);
        $this->assertEquals('', $result);
    }
}