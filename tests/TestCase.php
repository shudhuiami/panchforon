<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * Backend tests must not depend on a compiled frontend. CI never runs
         * the Vite build, and a view test is about routing and markup, not
         * about whether public/build/manifest.json exists on this machine.
         */
        $this->withoutVite();
    }
}
