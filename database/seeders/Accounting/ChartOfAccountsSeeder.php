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
class ChartOfAccountsSeeder extends Seeder
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
                        ]
                    );
                }
            }
        }
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
