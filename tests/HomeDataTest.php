<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/../inc/helpers/home-data.php';

class HomeDataTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_video_url_accepts_acf_array() {
        $video = array( 'url' => 'https://example.com/hero.mp4', 'mime_type' => 'video/mp4' );

        $this->assertSame( 'https://example.com/hero.mp4', wm_home_get_video_url( $video ) );
    }

    public function test_video_url_resolves_attachment_id() {
        Functions\expect( 'wp_get_attachment_url' )
            ->once()
            ->with( 42 )
            ->andReturn( 'https://example.com/uploads/hero.webm' );

        $this->assertSame( 'https://example.com/uploads/hero.webm', wm_home_get_video_url( 42 ) );
    }

    public function test_video_url_accepts_plain_string() {
        $this->assertSame( 'https://example.com/hero.ogv', wm_home_get_video_url( 'https://example.com/hero.ogv' ) );
    }

    public function test_video_url_returns_empty_for_empty_value() {
        $this->assertSame( '', wm_home_get_video_url( '' ) );
        $this->assertSame( '', wm_home_get_video_url( array() ) );
    }

    public function test_video_type_uses_acf_mime_type_when_present() {
        $video = array( 'url' => 'https://example.com/hero.mp4', 'mime_type' => 'video/mp4' );

        $this->assertSame( 'video/mp4', wm_home_get_video_type( $video ) );
    }

    public function test_video_type_guesses_from_extension() {
        Functions\expect( 'wp_parse_url' )->andReturnUsing( 'parse_url' );

        $this->assertSame( 'video/webm', wm_home_get_video_type( 'https://example.com/hero.webm' ) );
        $this->assertSame( 'video/ogg', wm_home_get_video_type( 'https://example.com/hero.ogv' ) );
        $this->assertSame( 'video/mp4', wm_home_get_video_type( 'https://example.com/hero.mp4' ) );
    }

    public function test_video_type_returns_empty_for_unknown_extension() {
        Functions\expect( 'wp_parse_url' )->andReturnUsing( 'parse_url' );

        $this->assertSame( '', wm_home_get_video_type( 'https://example.com/hero.xyz' ) );
    }
}
