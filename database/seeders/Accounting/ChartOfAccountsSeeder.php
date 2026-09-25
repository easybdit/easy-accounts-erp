<?php

namespace Database\Seeders\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Database\Seeder;

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
                            'is_bank_account' => in_array((string) $childCode, $this->bankAccountCodes(), true),
                            'is_undeposited_funds' => (string) $childCode === $this->undepositedFundsCode(),
                            'opening_balance' => 0, // শুরুতে সব ব্যালেন্স শূন্য থাকবে
                        ]
                    );
                }
            }
        }
    }

    /**
     * ক্যাশ, ব্যাংক এবং মোবাইল ব্যাংকিং (bKash/Nagad) অ্যাকাউন্টের কোড
     */
    private function bankAccountCodes(): array
    {
        return ['1001', '1002', '1003'];
    }

    /**
     * Undeposited Funds: a holding account for customer payments received
     * (cash/checks) but not yet physically taken to the bank — cleared out
     * via a Bank Deposit batch (App\Actions\Banking\MakeBankDeposit), which
     * mirrors how a real bank statement shows one lump deposit rather than
     * each payment separately.
     */
    private function undepositedFundsCode(): string
    {
        return '1008';
    }

    private function tree(): array
    {
        return [
            // ১. সম্পদ (Assets) - যা আপনার আছে
            'asset' => [
                1000 => [
                    'name' => 'Assets (সম্পদ)',
                    'children' => [
                        1008 => 'Undeposited Funds (জমা না হওয়া টাকা)',
                        1001 => 'Cash (হাতে থাকা ক্যাশ)',
                        1002 => 'Bank Accounts (ব্যাংক অ্যাকাউন্ট)',
                        1003 => 'Mobile Banking (বিকাশ/নগদ)',
                        1004 => 'Accounts Receivable (কাস্টমারের কাছে পাওনা)',
                        1005 => 'Hardware Inventory (পিসি, রাউটার, বক্সের স্টক)',
                        1006 => 'Server & Networking Equipment (নিজস্ব সার্ভার ও নেটওয়ার্কিং যন্ত্রপাতি)',
                        1007 => 'Prepaid Server Rent (সার্ভারের অগ্রিম ভাড়া)',
                    ],
                ],
            ],

            // ২. দেনা (Liabilities) - যা আপনাকে দিতে হবে
            'liability' => [
                2000 => [
                    'name' => 'Liabilities (দেনা)',
                    'children' => [
                        2001 => 'Accounts Payable (সাপ্লায়ার/ডাটা সেন্টারের পাওনা)',
                        2002 => 'Tax/VAT Payable (সরকারি ভ্যাট)',
                        2003 => 'Deferred Revenue (কাস্টমারের অগ্রিম দেওয়া হোস্টিং ফি)',
                        2004 => 'Salary Payable (স্টাফদের বকেয়া বেতন)',
                    ],
                ],
            ],

            // ৩. মালিকানার পুঁজি (Equity)
            'equity' => [
                3000 => [
                    'name' => 'Equity (মালিকানার পুঁজি)',
                    'children' => [
                        3001 => "Owner's Equity (মালিকের বিনিয়োগ)",
                        3002 => 'Retained Earnings (সঞ্চিত লাভ)',
                    ],
                ],
            ],

            // ৪. আয় (Income) - আপনার ব্যবসার মূল উৎস
            'income' => [
                4000 => [
                    'name' => 'Income (আয়)',
                    'children' => [
                        4001 => 'Hosting & Domain Income (হোস্টিং ও ডোমেইন বিক্রি)',
                        4002 => 'Networking Service Income (নেটওয়ার্কিং সেটআপ ফি)',
                        4003 => 'Corporate Monthly Fee Income (কর্পোরেট মাসিক সার্ভিস ফি)',
                        4004 => 'Server Maintenance Income (সার্ভার মেইনটেন্যান্স আয়)',
                        4005 => 'Hardware Sales Income (পিসি, রাউটার, বক্স বিক্রি)',
                    ],
                ],
            ],

            // ৫. খরচ (Expenses) - যা আপনি খরচ করছেন
            'expense' => [
                5000 => [
                    'name' => 'Expenses (খরচ)',
                    'children' => [
                        5001 => 'Bandwidth & Server Cost (জিবি/সার্ভার কেনার খরচ)',
                        5002 => 'Domain Reseller Cost (ডোমেইন কেনার খরচ)',
                        5003 => 'Cost of Goods Sold - Hardware (হার্ডওয়্যার কেনার খরচ)',
                        5004 => 'Salaries & Wages (স্টাফদের বেতন)',
                        5005 => 'Office Rent & Utilities (অফিস ভাড়া ও বিদ্যুৎ বিল)',
                        5006 => 'Marketing & Advertising (মার্কেটিং খরচ)',
                        5007 => 'Software & Licenses (সফটওয়্যার লাইসেন্স ক্রয়)',
                    ],
                ],
            ],
        ];
    }
}
