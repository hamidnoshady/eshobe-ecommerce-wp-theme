<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class ThemeUpdaterTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        if (!defined('ABSPATH')) {
            define('ABSPATH', true);
        }

        // Mock global functions called in constructor
        Functions\when('get_stylesheet')->justReturn('my-theme');
        $theme_mock = \Mockery::mock();
        $theme_mock->shouldReceive('get')->with('Version')->andReturn('1.0.0');
        Functions\when('wp_get_theme')->justReturn($theme_mock);
        Functions\when('add_filter')->justReturn(true);

        require_once dirname(__DIR__) . '/inc/theme-updater.php';
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_check_for_update_returns_unmodified_transient_when_not_checked() {
        $updater = new \WM_Theme_Updater();
        $transient = new \stdClass();
        $transient->checked = false; // empty

        $result = $updater->check_for_update($transient);

        $this->assertEquals($transient, $result);
    }

    public function test_check_for_update_returns_unmodified_transient_when_no_release() {
        $updater = new \WM_Theme_Updater();
        $transient = new \stdClass();
        $transient->checked = true;

        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn(new \stdClass()); // Return something that isn't an array for is_wp_error to process
        Functions\when('is_wp_error')->justReturn(true);
        // The channel function checks for wm_technical_theme_update_channel
        // Since function_exists('wm_technical_theme_update_channel') will return false naturally
        // it falls back to 'stable', which is fine.

        $result = $updater->check_for_update($transient);

        $this->assertEquals($transient, $result);
    }

    public function test_check_for_update_sets_response_when_new_version_available() {
        $updater = new \WM_Theme_Updater();
        $transient = new \stdClass();
        $transient->checked = true;

        $release = [
            'version' => '2.0.0',
            'url' => 'https://github.com/my/repo',
            'zip_url' => 'https://github.com/my/repo/zip',
        ];

        Functions\when('get_transient')->justReturn($release);

        $result = $updater->check_for_update($transient);

        $this->assertObjectHasProperty('response', $result);
        $this->assertArrayHasKey('my-theme', $result->response);
        $this->assertEquals('2.0.0', $result->response['my-theme']['new_version']);
        $this->assertEquals('https://github.com/my/repo/zip', $result->response['my-theme']['package']);
    }

    public function test_check_for_update_sets_no_update_when_version_not_greater() {
        $updater = new \WM_Theme_Updater();
        $transient = new \stdClass();
        $transient->checked = true;

        $release = [
            'version' => '1.0.0',
            'url' => 'https://github.com/my/repo',
            'zip_url' => 'https://github.com/my/repo/zip',
        ];

        Functions\when('get_transient')->justReturn($release);

        $result = $updater->check_for_update($transient);

        $this->assertObjectHasProperty('no_update', $result);
        $this->assertArrayHasKey('my-theme', $result->no_update);
        $this->assertEquals('1.0.0', $result->no_update['my-theme']['new_version']);
        $this->assertEquals('https://github.com/my/repo/zip', $result->no_update['my-theme']['package']);
    }
}
