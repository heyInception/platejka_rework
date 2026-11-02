<?php

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', '/' );
require_once dirname( __DIR__ ) . '/src/Reviews/Settings.php';

final class ReviewSettingsTest extends TestCase {
	public function test_defaults_to_editor_review(): void {
		$this->assertSame( 'moderated', \Platejka\Core\Reviews\Settings::normalizeMode( null ) );
	}

	public function test_accepts_only_known_modes(): void {
		$this->assertSame( 'automatic', \Platejka\Core\Reviews\Settings::normalizeMode( 'automatic' ) );
		$this->assertSame( 'moderated', \Platejka\Core\Reviews\Settings::normalizeMode( 'moderated' ) );
		$this->assertSame( 'moderated', \Platejka\Core\Reviews\Settings::normalizeMode( 'invalid' ) );
	}
}
