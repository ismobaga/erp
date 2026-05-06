<?php

namespace Crommix\CRM\Services;

use Crommix\CRM\Models\Contact;
use Crommix\CRM\Models\Lead;

class CRMService
{
    /**
     * Create a new lead.
     *
     * @param array<string, mixed> $data
     */
    public function createLead(array $data): Lead
    {
        return Lead::create($data);
    }

    /**
     * Convert a lead into a contact.
     */
    public function convertLead(Lead $lead): Contact
    {
        $contact = Contact::create([
            'company_id'   => $lead->company_id,
            'lead_id'      => $lead->id,
            'first_name'   => $lead->first_name,
            'last_name'    => $lead->last_name,
            'email'        => $lead->email,
            'phone'        => $lead->phone,
            'company_name' => $lead->company_name,
            'assigned_to'  => $lead->assigned_to,
        ]);

        $lead->update([
            'status'       => 'converted',
            'converted_at' => now(),
        ]);

        return $contact;
    }

    /**
     * Update a lead.
     *
     * @param array<string, mixed> $data
     */
    public function updateLead(Lead $lead, array $data): Lead
    {
        $lead->update($data);

        return $lead->refresh();
    }
}
