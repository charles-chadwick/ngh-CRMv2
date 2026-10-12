<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateCompany
{
    use AsAction;

    /**
     * Create a company.
     *
     * @param  array{title: string, company_type_id?: int|null, description?: string|null}  $attributes
     */
    public function handle(array $attributes): Company
    {
        return Company::create($attributes);
    }
}
