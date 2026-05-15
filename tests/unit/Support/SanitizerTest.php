<?php
/**
 * Unit tests for the Sanitizer helper.
 *
 * @package WhatsCom\Tests\Unit\Support
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\Support;

use WhatsCom\Support\Sanitizer;
use WP_Mock\Tools\TestCase;

/**
 * Class SanitizerTest
 */
class SanitizerTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	/**
	 * @dataProvider provideValidPhones
	 */
	public function test_phone_valid( string $input, string $expected ): void {
		$this->assertSame( $expected, Sanitizer::phone( $input ) );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function provideValidPhones(): array {
		return array(
			'e164 with plus'    => array( '+34612345678', '+34612345678' ),
			'digits only'       => array( '34612345678', '+34612345678' ),
			'spaces stripped'   => array( '+34 612 345 678', '+34612345678' ),
			'dashes stripped'   => array( '+34-612-345-678', '+34612345678' ),
		);
	}

	public function test_phone_invalid_returns_empty_string(): void {
		$this->assertSame( '', Sanitizer::phone( 'not-a-phone' ) );
	}

	public function test_template_name_lowercases_and_strips(): void {
		$this->assertSame( 'order_confirmed', Sanitizer::templateName( 'Order_Confirmed!' ) );
	}
}
