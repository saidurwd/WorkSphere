<?php

namespace App\Support;

/**
 * Gives a controller the cached reference lists.
 *
 * A trait rather than a constructor argument on seven controllers: most of them
 * already have constructors with their own collaborators, and adding a ninth
 * optional dependency to each is a larger and noisier change than one import and one
 * `use` line. The accessor resolves lazily, so a controller that never renders a
 * dropdown never constructs the service.
 *
 * {@see ReferenceData} for the caching and invalidation rules.
 */
trait ResolvesReferenceData
{
    private ?ReferenceData $referenceDataService = null;

    protected function referenceData(): ReferenceData
    {
        return $this->referenceDataService ??= app(ReferenceData::class);
    }
}
