<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

// design-tokens.php registers hooks at include time; bootstrap only stubs add_action.
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter() {}
}
require_once __DIR__ . '/../inc/acf/design-tokens.php';

class DesignTokensTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function stubBasics() {
        // No ACF value set -> every token falls back to its default.
        Functions\expect( 'get_field' )->zeroOrMoreTimes()->andReturn( false );
        Functions\expect( 'esc_attr' )->zeroOrMoreTimes()->andReturnFirstArg();
        Functions\expect( 'sanitize_hex_color' )->zeroOrMoreTimes()->andReturnFirstArg();
    }

    public function test_hero_progress_defaults_are_emitted() {
        $this->stubBasics();

        $css = wm_build_design_tokens_css();

        $this->assertStringContainsString( '--wm-hero-progress-color:#C89B3C;', $css );
        $this->assertStringContainsString( '--wm-hero-progress-origin:right;', $css );
    }

    public function test_hero_progress_color_token_can_be_overridden() {
        Functions\expect( 'get_field' )
            ->zeroOrMoreTimes()
            ->andReturnUsing(
                function ( $name, $option_id = null ) {
                    if ( 'wm_hero_progress_color' === $name ) {
                        return '#123456';
                    }
                    return false;
                }
            );
        Functions\expect( 'esc_attr' )->zeroOrMoreTimes()->andReturnFirstArg();
        Functions\expect( 'sanitize_hex_color' )->zeroOrMoreTimes()->andReturnFirstArg();

        $css = wm_build_design_tokens_css();

        $this->assertStringContainsString( '--wm-hero-progress-color:#123456;', $css );
    }

    public function test_hero_progress_direction_token_can_be_overridden() {
        Functions\expect( 'get_field' )
            ->zeroOrMoreTimes()
            ->andReturnUsing(
                function ( $name, $option_id = null ) {
                    if ( 'wm_hero_progress_direction' === $name ) {
                        return 'left';
                    }
                    return false;
                }
            );
        Functions\expect( 'esc_attr' )->zeroOrMoreTimes()->andReturnFirstArg();
        Functions\expect( 'sanitize_hex_color' )->zeroOrMoreTimes()->andReturnFirstArg();

        $css = wm_build_design_tokens_css();

        $this->assertStringContainsString( '--wm-hero-progress-origin:left;', $css );
    }

    public function test_hero_progress_direction_falls_back_for_invalid_value() {
        Functions\expect( 'get_field' )
            ->zeroOrMoreTimes()
            ->andReturnUsing(
                function ( $name, $option_id = null ) {
                    if ( 'wm_hero_progress_direction' === $name ) {
                        return 'sideways';
                    }
                    return false;
                }
            );
        Functions\expect( 'esc_attr' )->zeroOrMoreTimes()->andReturnFirstArg();
        Functions\expect( 'sanitize_hex_color' )->zeroOrMoreTimes()->andReturnFirstArg();

        $css = wm_build_design_tokens_css();

        $this->assertStringContainsString( '--wm-hero-progress-origin:right;', $css );
    }
}
