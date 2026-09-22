<?php

namespace Database\Seeders\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Database\Seeder;

/**
 * Minimal starter Chart of Accounts.
 *
 * This is generic demo/default data, not a fixed accounting policy.
 * All opening balances are zero, so the chart is trivially balanced.
 */
class ChartOfAccountsSeeder_old extends Seeder
{
    public function run(): void
    {
        foreach ($this->tree() as $type => $groups) {
            foreach ($groups as $code => $definition) {
                $parent = Account::updateOrCreate(
                    ['code' => (string) $code],
                    [
                        'name' => $definition['name'],
                        'type' => $type,
                        'parent_id' => null,
                        'is_active' => true,
                    ]
                );

                foreach ($definition['children'] ?? [] as $childCode => $childName) {
                    Account::updateOrCreate(
                        ['code' => (string) $childCode],
                        [
                            'name' => $childName,
                            'type' => $type,
                            'parent_id' => $parent->id,
                            'is_active' => true,
                            'is_bank_account' => in_array((string) $childCode, $this->bankAccountCodes(), true),
                            'opening_balance' => $this->openingBalances()[(string) $childCode] ?? 0,
                        ]
                    );
                }
            }
        }
    }

    /**
     * A starting Cash balance, offset by Owner's Equity so the seeded
     * chart still nets to zero (assets = liabilities + equity) — purely
     * to make the demo data look like a real, funded starting position
     * instead of an empty/negative cash account after the other demo
     * seeders record their transactions.
     */
    private function openingBalances(): array
    {
        return [
            '1001' => 2000, // Cash
            '3001' => 2000, // Owner's Equity
        ];
    }

    /**
     * Cash and Bank are actual Cash/Bank accounts (Section 31 Banking);
     * Accounts Receivable and Inventory are asset-type but not bank/cash.
     */
    private function bankAccountCodes(): array
    {
        return ['1001', '1002'];
    }

    private function tree(): array
    {
        return [
            'asset' => [
                1000 => [
                    'name' => 'Assets',
                    'children' => [
                        1001 => 'Cash',
                        1002 => 'Bank',
                        1003 => 'Accounts Receivable',
                        1004 => 'Inventory',
                    ],
                ],
            ],
            'liability' => [
                2000 => [
                    'name' => 'Liabilities',
                    'children' => [
                        2001 => 'Accounts Payable',
                        2002 => 'Tax Payable',
                    ],
                ],
            ],
            'equity' => [
                3000 => [
                    'name' => 'Equity',
                    'children' => [
                        3001 => "Owner's Equity",
                        3002 => 'Retained Earnings',
                    ],
                ],
            ],
            'income' => [
                4000 => [
                    'name' => 'Income',
                    'children' => [
                        4001 => 'Sales Revenue',
                        4002 => 'Service Revenue',
                    ],
                ],
            ],
            'expense' => [
                5000 => [
                    'name' => 'Expenses',
                    'children' => [
                        5001 => 'Cost of Goods Sold',
                        5002 => 'Operating Expenses',
                        5003 => 'Salaries Expense',
                    ],
                ],
            ],
        ];
    }
}
