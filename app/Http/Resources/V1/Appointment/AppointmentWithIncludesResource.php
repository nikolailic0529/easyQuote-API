<?php

namespace App\Http\Resources\V1\Appointment;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class AppointmentWithIncludesResource extends JsonResource
{
    public static $wrap = null;

    public array $availableIncludes = [
        'userRelations.related',
        'contactRelations.related',
        'companyRelations.related',
        'opportunityRelations.related',
        'inviteeContactRelations.related',
        'inviteeUserRelations.related',
        'rescueQuoteRelations.related',
        'worldwideQuoteRelations.related',
        'attachmentRelations.related',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var \App\Models\Appointment\Appointment|self $this */

        $tz = $request->user()->timezone->utc ?? config('app.timezone');

        return [
            'id' => $this->getKey(),
            'activity_type' => $this->activity_type,
            'subject' => $this->subject,
            'description' => $this->description,
            'location' => $this->location,

            'start_date' => Carbon::instance($this->start_date)->tz($tz)->format(config('date.format_time')),
            'end_date' => Carbon::instance($this->end_date)->tz($tz)->format(config('date.format_time')),
            'reminder' => $this->reminder,

            'user_relations' => $this->userRelations,
            'contact_relations' => $this->contactRelations,
            'company_relations' => $this->companyRelations,
            'opportunity_relations' => $this->opportunityRelations,

            'invitee_contact_relations' => $this->inviteeContactRelations,
            'invitee_user_relations' => $this->inviteeUserRelations,

            'rescue_quote_relations' => $this->rescueQuoteRelations,
            'worldwide_quote_relations' => $this->worldwideQuoteRelations,

            'attachment_relations' => $this->attachmentRelations,

//            'companies' => $this->companies,
//            'opportunities' => $this->opportunities,
//            'contacts' => $this->contacts,

//            'invitees_contacts' => $this->inviteesContacts,
//            'invitees_users' => $this->inviteesUsers,

            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}
