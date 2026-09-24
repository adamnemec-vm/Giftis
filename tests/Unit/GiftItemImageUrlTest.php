<?php

namespace Tests\Unit;

use App\Models\GiftItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftItemImageUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_no_image_is_set(): void
    {
        $item = GiftItem::factory()->make(['image_path' => null]);

        $this->assertNull($item->resolved_image_url);
    }

    public function test_returns_external_urls_unchanged(): void
    {
        $item = GiftItem::factory()->make(['image_path' => 'https://example.com/photo.jpg']);

        $this->assertSame('https://example.com/photo.jpg', $item->resolved_image_url);
    }

    public function test_resolves_placeholder_paths_to_a_public_asset_url(): void
    {
        $item = GiftItem::factory()->make(['image_path' => '/images/placeholders/book.svg']);

        $this->assertSame(asset('/images/placeholders/book.svg'), $item->resolved_image_url);
    }

    public function test_resolves_uploaded_paths_to_the_storage_disk_url(): void
    {
        $item = GiftItem::factory()->make(['image_path' => 'gifts/some-upload.jpg']);

        $this->assertSame(asset('storage/gifts/some-upload.jpg'), $item->resolved_image_url);
    }
}
