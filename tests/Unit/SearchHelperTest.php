<?php

namespace Tests\Unit;

use App\Support\Discovery\FeatureRegistry;
use App\Support\Search\SearchHelper;
use Tests\TestCase;

class SearchHelperTest extends TestCase
{
    public function test_like_pattern_escapes_wildcards(): void
    {
        $this->assertSame('%100\\%25\_off%', SearchHelper::likePattern('100%25_off'));
    }

    public function test_like_pattern_returns_null_for_blank_input(): void
    {
        $this->assertNull(SearchHelper::likePattern(''));
        $this->assertNull(SearchHelper::likePattern('   '));
    }
}
