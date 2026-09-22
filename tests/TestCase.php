<?php

namespace Tests;

use Database\Seeders\Security\PermissionSeeder;
use Database\Seeders\Security\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Permissions/roles must exist before any factory-made User can be
     * assigned the Administrator role (see UserFactory::configure()), so
     * every RefreshDatabase test gets them seeded automatically here.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)
            && Schema::hasTable('permissions')) {
            $this->seed(PermissionSeeder::class);
            $this->seed(RoleSeeder::class);
        }
    }
}
