<?php

namespace Tests\Unit\Accounting;

use App\Models\Accounting\Account;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    public function test_normal_balance_for_each_type(): void
    {
        $expected = [
            'asset' => 'debit',
            'expense' => 'debit',
            'liability' => 'credit',
            'equity' => 'credit',
            'income' => 'credit',
        ];

        foreach ($expected as $type => $side) {
            $account = new Account(['type' => $type]);

            $this->assertSame($side, $account->normalBalance());
        }
    }
}
