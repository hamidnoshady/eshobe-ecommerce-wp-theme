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
        $result = wm_get_mobile_stock_label(null, ['label' => '']);
        $this->assertEquals('', $result);
    }

    public function test_wm_get_mobile_stock_label_no_product() {
        $result = wm_get_mobile_stock_label(null, ['label' => 'In stock', 'status' => 'in_stock']);
        $this->assertEquals('In stock', $result);
    }

    public function test_wm_get_mobile_stock_label_not_in_stock() {
        $product = Mockery::mock('WC_Product');
        $result = wm_get_mobile_stock_label($product, ['label' => 'Out of stock', 'status' => 'out_of_stock']);
        $this->assertEquals('Out of stock', $result);
    }

    public function test_wm_get_mobile_stock_label_not_managing_stock() {
        $product = Mockery::mock('WC_Product');
        $product->shouldReceive('managing_stock')->once()->andReturn(false);
        $result = wm_get_mobile_stock_label($product, ['label' => 'In stock', 'status' => 'in_stock']);
        $this->assertEquals('In stock', $result);
    }

    public function test_wm_get_mobile_stock_label_managing_stock_null_qty() {
        $product = Mockery::mock('WC_Product');
        $product->shouldReceive('managing_stock')->once()->andReturn(true);
        $product->shouldReceive('get_stock_quantity')->once()->andReturn(null);
        $result = wm_get_mobile_stock_label($product, ['label' => 'In stock', 'status' => 'in_stock']);
        $this->assertEquals('In stock', $result);
    }

    public function test_wm_get_mobile_stock_label_managing_stock_with_qty() {
        $product = Mockery::mock('WC_Product');
        $product->shouldReceive('managing_stock')->once()->andReturn(true);
        $product->shouldReceive('get_stock_quantity')->once()->andReturn(5);

        Functions\expect('wc_format_stock_quantity_for_display')
            ->once()
            ->with(5, $product)
            ->andReturn('5 in stock');

        $result = wm_get_mobile_stock_label($product, ['label' => 'In stock', 'status' => 'in_stock']);
        $this->assertEquals('In stock · 5 in stock', $result);
    }
}
