<?php

namespace Tests\Unit;

use App\Services\Site\PublicSite;
use PHPUnit\Framework\TestCase;

class VideoDeliveryTest extends TestCase
{
    public function test_local_film_urls_avoid_old_cdn_objects_without_changing_external_media(): void
    {
        $site = new PublicSite;
        $this->assertSame('/assets/films/showreel.mp4?delivery=4', $site->videoUrl('/assets/films/showreel.mp4'));
        $this->assertSame('/assets/films/showreel.mp4?lang=en&delivery=4#t=5', $site->videoUrl('/assets/films/showreel.mp4?lang=en#t=5'));
        $this->assertSame('https://example.test/video.mp4', $site->videoUrl('https://example.test/video.mp4'));
        $this->assertNull($site->videoUrl(null));
    }
}
