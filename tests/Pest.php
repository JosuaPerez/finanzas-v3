<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific
| PHPUnit test case class. By default, that class is "PHPUnit\Framework\TestCase".
| Of course, you may need to change it using the "uses()" function.
|
| Applying RefreshDatabase globally for all Feature tests means every test
| starts with a clean in-memory SQLite database (configured via phpunit.xml),
| matching the behavior of PHPUnit class-based tests that declared "use RefreshDatabase".
|
*/

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain
| conditions. The "expect()" function gives you access to a set of "expectations"
| methods that you can use to assert different things. Of course, you may
| extend the Expectation API at any time.
|
*/

// Example custom expectation (extend as needed):
// expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code
| specific to your project that you constantly need to repeat. Here you can
| also expose helpers as global functions to help you reduce the amount of
| code you write.
|
*/

// Example helper (extend as needed):
// function something(): void {}
