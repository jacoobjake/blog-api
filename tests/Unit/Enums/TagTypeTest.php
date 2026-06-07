<?php

namespace Tests\Unit\Enums;

use App\Enums\TagType;
use PHPUnit\Framework\TestCase;

class TagTypeTest extends TestCase
{
    public function test_blog_value_is_blog_string(): void
    {
        $this->assertSame('blog', TagType::BLOG->value);
    }
}
