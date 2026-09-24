<?php

namespace Tests\Feature\Demo;

use App\Models\Accounting\Journal;
use App\Models\Contacts\Customer;
use App\Models\Inventory\Product;
use App\Models\Sales\Invoice;
use App\Models\User;
use Database\Seeders\Demo\DomainHostingDemoSeeder;
use Database\Seeders\Demo\EducationDemoSeeder;
use Database\Seeders\Demo\HospitalDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Each demo seeder is meant to run standalone on a fresh database (not
 * alongside DatabaseSeeder) and produce a ready-to-show demo: a working
 * login, a posted invoice, and a recorded expense.
 */
class DemoSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_hosting_demo_seeder_produces_a_working_demo(): void
    {
        $this->seed(DomainHostingDemoSeeder::class);

        $this->assertTrue(User::where('email', 'admin@hosting-demo.test')->first()?->hasRole('Administrator'));
        $this->assertTrue(Product::where('sku', 'DOM-001')->exists());
        $this->assertTrue(Customer::where('name', 'Bright Corner IT Ltd')->exists());
        $this->assertSame('posted', Invoice::first()?->status);
        $this->assertGreaterThan(0, Journal::count());

        // Re-running must not throw or duplicate the demo transactions.
        $this->seed(DomainHostingDemoSeeder::class);
        $this->assertSame(1, Invoice::count());
    }

    public function test_education_demo_seeder_produces_a_working_demo(): void
    {
        $this->seed(EducationDemoSeeder::class);

        $this->assertTrue(User::where('email', 'admin@school-demo.test')->first()?->hasRole('Administrator'));
        $this->assertTrue(Product::where('sku', 'ADM-001')->exists());
        $this->assertTrue(Customer::where('name', 'Ayesha Siddika (Class 10)')->exists());
        $this->assertSame('posted', Invoice::first()?->status);
        $this->assertGreaterThan(0, Journal::count());

        $this->seed(EducationDemoSeeder::class);
        $this->assertSame(1, Invoice::count());
    }

    public function test_hospital_demo_seeder_produces_a_working_demo(): void
    {
        $this->seed(HospitalDemoSeeder::class);

        $this->assertTrue(User::where('email', 'admin@hospital-demo.test')->first()?->hasRole('Administrator'));
        $this->assertTrue(Product::where('sku', 'CON-GEN')->exists());
        $this->assertTrue(Customer::where('name', 'Nasima Begum')->exists());
        $this->assertSame('posted', Invoice::first()?->status);
        $this->assertGreaterThan(0, Journal::count());

        $this->seed(HospitalDemoSeeder::class);
        $this->assertSame(1, Invoice::count());
    }
}
