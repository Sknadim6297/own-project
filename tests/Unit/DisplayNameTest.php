<?php

namespace Tests\Unit;

use App\Helpers\DisplayName;
use PHPUnit\Framework\TestCase;

class DisplayNameTest extends TestCase
{
    public function test_title_cases_accidental_lowercase_parts(): void
    {
        $this->assertSame('Rahul Sharma', DisplayName::present('Rahul sharma'));
        $this->assertSame('Priya Das', DisplayName::present('priya das'));
        $this->assertSame('Amit Kumar Singh', DisplayName::present('Amit kumar singh'));
    }

    public function test_preserves_intentional_capitalization_and_initials(): void
    {
        $this->assertSame('S. K. Das', DisplayName::present('S. K. Das'));
        $this->assertSame('McDonald', DisplayName::present('McDonald'));
        $this->assertSame('PRIYA DAS', DisplayName::present('PRIYA DAS'));
    }

    public function test_handles_hyphens_apostrophes_and_empty(): void
    {
        $this->assertSame('Mary-Jane Watson', DisplayName::present('mary-jane watson'));
        $this->assertSame("O'Brien", DisplayName::present("o'brien"));
        $this->assertSame('', DisplayName::present('   '));
        $this->assertSame('Rahul', DisplayName::present('Rahul '));
    }
}
