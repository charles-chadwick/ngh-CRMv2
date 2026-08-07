<?php

namespace Database\Seeders;

use App\Models\Title;
use Illuminate\Database\Seeder;

class TitleSeeder extends Seeder
{
    /**
     * Job titles customers can be tagged with.
     *
     * @var list<string>
     */
    private array $titles = [
        'Chief Executive Officer',
        'Chief Operating Officer',
        'Chief Financial Officer',
        'Chief Technology Officer',
        'Chief Marketing Officer',
        'VP of Sales',
        'VP of Engineering',
        'Director of Operations',
        'Director of Marketing',
        'Product Manager',
        'Project Manager',
        'Account Manager',
        'Sales Representative',
        'Marketing Specialist',
        'Software Engineer',
        'Senior Software Engineer',
        'Lead Developer',
        'Data Analyst',
        'Business Analyst',
        'UX Designer',
        'Customer Success Manager',
        'Support Specialist',
        'Operations Coordinator',
        'Human Resources Manager',
        'Office Administrator',
        'Consultant',
        'Procurement Officer',
        'Logistics Coordinator',
        'Quality Assurance Engineer',
        'Founder',
    ];

    public function run(): void
    {
        collect($this->titles)
            ->each(fn (string $title) => Title::create(['title' => $title]));
    }
}
