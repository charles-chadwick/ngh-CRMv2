<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ActAs;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Backs the navbar "act as" switcher: searches the people a developer can
 * impersonate and records the choice in the session for {@see ActAs}.
 */
class ActAsController extends Controller
{
    /**
     * Whitelist of impersonable models keyed by the client `type` value.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $models = [
        'user' => User::class,
        'customer' => Customer::class,
    ];

    /**
     * Search users and customers to switch to.
     */
    public function options(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->withAvatar()
            ->limit(8)
            ->get()
            ->map(fn (User $user) => $this->payload($user, 'user'));

        $customers = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($grouped) => $grouped->withSearch($search)))
            ->withAvatar()
            ->limit(8)
            ->get()
            ->map(fn (Customer $customer) => $this->payload($customer, 'customer'));

        return response()->json($users->concat($customers)->values());
    }

    /**
     * Record the selected actor in the session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($this->models))],
            'id' => ['required', 'integer'],
        ]);

        abort_unless(
            $this->models[$validated['type']]::whereKey($validated['id'])->exists(),
            404,
        );

        $request->session()->put('act_as', [
            'type' => $validated['type'],
            'id' => (int) $validated['id'],
        ]);

        return back();
    }

    /**
     * Shape a user or customer for the switcher.
     *
     * @return array{type: string, id: int, full_name: string, avatar_url: ?string}
     */
    protected function payload(Model $person, string $type): array
    {
        return [
            'type' => $type,
            'id' => $person->getKey(),
            'full_name' => $person->full_name,
            'avatar_url' => $person->avatar_url,
        ];
    }
}
