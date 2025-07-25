<?php

namespace WebNorthCodeChallenge\Tests;

use Brain\Monkey;

class TestCase extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
