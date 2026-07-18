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

    public function test_wm_normalize_meta_value_with_simple_string() {
        Functions\expect('wp_strip_all_tags')->andReturnFirstArg();
        $this->assertEquals('test string', wm_normalize_meta_value(' test string '));
    }

    public function test_wm_normalize_meta_value_with_html_tags() {
        Functions\expect('wp_strip_all_tags')
            ->once()
            ->with('<p>test</p>')
            ->andReturn('test');

        $this->assertEquals('test', wm_normalize_meta_value('<p>test</p>'));
    }

    public function test_wm_normalize_meta_value_with_array() {
        Functions\expect('wp_strip_all_tags')->andReturnFirstArg();
        $value = ['value1', 'value2'];
        $this->assertEquals('value1، value2', wm_normalize_meta_value($value));
    }

    public function test_wm_normalize_meta_value_with_nested_array() {
        Functions\expect('wp_strip_all_tags')->andReturnFirstArg();
        $value = ['value1', ['nested1', 'nested2']];
        $this->assertEquals('value1، nested1، nested2', wm_normalize_meta_value($value));
    }

    public function test_wm_normalize_meta_value_with_array_containing_empty_values() {
        Functions\expect('wp_strip_all_tags')->andReturnFirstArg();
        $value = ['value1', '', null, 'value2'];
        $this->assertEquals('value1، value2', wm_normalize_meta_value($value));
    }

    public function test_wm_normalize_meta_value_with_object_having_name() {
        $obj = new stdClass();
        $obj->name = 'Object Name';
        $this->assertEquals('Object Name', wm_normalize_meta_value($obj));
    }

    public function test_wm_normalize_meta_value_with_object_missing_name() {
        $obj = new stdClass();
        $obj->title = 'Object Title';
        $this->assertEquals('', wm_normalize_meta_value($obj));
    }

    public function test_wm_normalize_meta_value_with_integer() {
        Functions\expect('wp_strip_all_tags')->andReturnFirstArg();
        $this->assertEquals('123', wm_normalize_meta_value(123));
    }

    public function test_wm_normalize_meta_value_with_boolean() {
        Functions\expect('wp_strip_all_tags')->andReturnFirstArg();
        // string casting of true is '1'
        $this->assertEquals('1', wm_normalize_meta_value(true));
        $this->assertEquals('', wm_normalize_meta_value(false));
    }
}
