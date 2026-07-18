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
}
