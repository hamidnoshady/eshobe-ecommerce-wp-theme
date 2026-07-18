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

    public function test_wm_get_mobile_stock_label_empty_label() {
        $product = \Mockery::mock('WC_Product');
        $stock = ['label' => ''];
        $result = wm_get_mobile_stock_label($product, $stock);
        $this->assertEquals('', $result);
    }

    public function test_wm_get_mobile_stock_label_in_stock() {
        $product = \Mockery::mock('WC_Product');
        $product->shouldReceive('managing_stock')->andReturn(true);
        $product->shouldIgnoreMissing();

        $stock = ['label' => 'In Stock', 'status' => 'in_stock'];

        $result = wm_get_mobile_stock_label($product, $stock);
        $this->assertEquals('In Stock', $result);
    }

    public function test_wm_get_mobile_stock_label_out_of_stock() {
        $product = \Mockery::mock('WC_Product');
        $product->shouldIgnoreMissing();

        $stock = ['label' => 'Out of Stock', 'status' => 'out_of_stock'];

        $result = wm_get_mobile_stock_label($product, $stock);
        $this->assertEquals('Out of Stock', $result);
    }

    public function test_wm_get_mobile_stock_label_not_managing_stock() {
        $product = \Mockery::mock('WC_Product');
        $product->shouldReceive('managing_stock')->andReturn(false);
        $product->shouldIgnoreMissing();

        $stock = ['label' => 'In Stock', 'status' => 'in_stock'];

        $result = wm_get_mobile_stock_label($product, $stock);
        $this->assertEquals('In Stock', $result);
    }

    public function test_wm_get_mobile_stock_label_null_product() {
        $product = null;
        $stock = ['label' => 'In Stock', 'status' => 'in_stock'];

        $result = wm_get_mobile_stock_label($product, $stock);
        $this->assertEquals('In Stock', $result);
    }
}
