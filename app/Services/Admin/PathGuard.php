<?php

namespace App\Services\Admin;

use App\Models\LegacyRedirect;
use App\Services\ContentTypes;

/**
 * Checks a translated `path` value for collisions across every public
 * content type (Pages, Projects, Films, Destinations, Posts) plus legacy
 * redirects, mirroring the same check App\Services\PageAddresses already
 * does for Pages — so new content types can't accidentally collide on URL.
 */
class PathGuard
{
    public function isTaken(string $path, string $modelClass, ?int $exceptId = null): bool
    {
        if (LegacyRedirect::where('source', $path)->exists()) {
            return true;
        }

        foreach (ContentTypes::MODELS as $class) {
            $query = $class::withTrashed()->whereHas('translations', fn ($q) => $q->where('path', $path));

            if ($class === $modelClass && $exceptId) {
                $query->whereKeyNot($exceptId);
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }
}
