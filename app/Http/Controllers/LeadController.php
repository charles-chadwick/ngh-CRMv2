<?php

namespace App\Http\Controllers;

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(): Response
    {
        $leads = Lead::query()
            ->with('assignee:id,first_name,last_name')
            ->withSearch(request('search'))
            ->withSort(request('sort_by'), request('sort_direction'))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Leads/Form', $this->formOptions());
    }

    public function store(LeadRequest $request): RedirectResponse
    {
        $lead = Lead::create($request->validated());

        return redirect()->route('leads.show', $lead);
    }

    public function show(Lead $lead): Response
    {
        $lead->load([
            'assignee:id,first_name,last_name',
            'creationActivity.causer',
            'updateActivity.causer',
        ]);

        return Inertia::render('Leads/Show', [
            'lead' => $lead,
            'notes' => $lead->paginatedNotes(),
            'note_types' => Note::TYPES,
        ]);
    }

    public function edit(Lead $lead): Response
    {
        $lead->load('assignee:id,first_name,last_name');

        return Inertia::render('Leads/Form', [
            'lead' => $lead,
            ...$this->formOptions(),
        ]);
    }

    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return redirect()->route('leads.show', $lead);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index');
    }

    /**
     * Shared dropdown data for the create/edit form.
     *
     * @return array{users: Collection<int, User>, statuses: array, types: array}
     */
    protected function formOptions(): array
    {
        return [
            'users' => User::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'statuses' => LeadStatus::options(),
            'types' => LeadType::options(),
        ];
    }
}
