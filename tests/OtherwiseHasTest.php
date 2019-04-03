<?php

namespace Tests\Opdavies\GmailFilterBuilder;

use Opdavies\GmailFilterBuilder\Model\Filter;
use Opdavies\GmailFilterBuilder\Model\FilterGroup;
use PHPUnit\Framework\TestCase;
use Tightenco\Collect\Support\Collection;

class OtherwiseHasTest extends TestCase
{

    /** @test */
    public function it_returns_the_correct_number_of_filters()
    {
        $filters = FilterGroup::if(
            Filter::create()
                ->has('to:me@example.com foo')
                ->labelAndArchive('Test')
        )->otherwise(
            Filter::create()
                ->has('to:me@example.com bar')
                ->read()
        )->otherwise(
            Filter::create()
                ->has('to:me@example.com')
                ->trash()
      );

        $this->assertCount(3, $filters->all());
    }

    /** @test */
    public function same_conditions_are_kept_between_filters()
    {
        $filters = FilterGroup::if(
            Filter::create()
                ->has('to:me@example.com subject:Foo')
                ->read()
        )->otherwise(
            Filter::create()
                ->has('to:me@example.com subject:Bar')
                ->trash()
        );

        $filters->all()->each(function (Filter $filter) {
            $this->assertTrue($filter->getConditions()->contains('to:me@example.com'));
        });
    }

    /** @test */
    public function get_all_conditions_from_a_filter_group()
    {
        $filters = FilterGroup::if(
            Filter::create()
                ->has('to:me@example.com subject:Foo')
                ->read()
        )->otherwise(
            Filter::create()
                ->has('to:me@example.com subject:Bar')
                ->trash()
        )->otherwise(
            Filter::create()
                ->has('to:me@example.com subject:Baz')
                ->trash()
        );

        $expected = [
          ['to:me@example.com', 'subject:Foo'],
          ['to:me@example.com', 'subject:Bar'],
          ['to:me@example.com', 'subject:Baz'],
        ];

        $conditions = $filters->getConditions();
        $this->assertInstanceOf(Collection::class, $conditions);
        $this->assertSame($expected, $conditions->toArray());
    }

    /** @test */
    public function different_conditions_are_negated_in_subsequent_filters()
    {
        $filters = FilterGroup::if(
            Filter::create()
                ->has('to:me@example.com subject:Foo')
                ->read()
        )->otherwise(
            Filter::create()
                ->has('to:me@example.com subject:Bar')
                ->trash()
        )->otherwise(
            Filter::create()
                ->has('to:me@example.com subject:Baz')
                ->trash()
        );

        $this->assertSame('subject:Foo', $filters->all()->get(0)->getConditions()->get(1));

        // The subject condition from the first filter should be present but
        // negated.
        $this->assertSame('!subject:Foo', $filters->all()->get(1)->getConditions()->get(1));

        // Both subject conditions from both previous filters should be present
        // but negated.
//        $this->assertSame('!subject:[Foo|Bar]', $filters->all()->get(2)->getConditions()->get(1));
        $this->assertSame('!subject:Foo', $filters->all()->get(2)->getConditions()->get(1));
        $this->assertSame('!subject:Bar', $filters->all()->get(2)->getConditions()->get(2));
    }
}
