<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateCustomer
{
    use AsAction;

    /**
     * Create a customer.
     *
     * @param  array{first_name: string, last_name: string, email: string, password: string, company_id?: int|null, customer_role_id?: int|null, title?: string|null, prefix?: string|null, suffix?: string|null}  $attributes
     */
    public function handle(array $attributes): Customer
    {
        return Customer::create($attributes);
    }
}
