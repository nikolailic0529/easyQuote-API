<?php

namespace Tests\Feature;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Models\AppointmentContactInvitee;
use App\Domain\Appointment\Models\AppointmentReminder;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Reminder\Enum\ReminderStatus;
use App\Domain\Rescue\Models\Quote;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class AppointmentTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    /**
     * Test an ability to view a list of appointments linked to company.
     */
    public function testCanViewListOfAppointmentsLinkedToCompany(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Appointment\Models\Appointment $appointment */
        $appointment = Appointment::factory()
            ->has(Company::factory(), 'companiesHaveAppointment')
            ->create([
                'subject' => Str::random(40),
            ]);

        $response = $this->getJson('api/companies/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'activity_type',
                        'subject',
                        'start_date',
                        'end_date',
                        'location',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/companies/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->subject)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/companies/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->activity_type->value)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test an ability to view a list of appointments linked to opportunity.
     */
    public function testCanViewListOfAppointmentsLinkedToOpportunity(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Appointment\Models\Appointment $appointment */
        $appointment = Appointment::factory()
            ->has(Opportunity::factory(), 'opportunitiesHaveAppointment')
            ->create([
                'subject' => Str::random(40),
            ]);

        $response = $this->getJson('api/opportunities/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'activity_type',
                        'subject',
                        'start_date',
                        'end_date',
                        'location',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/opportunities/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->subject)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/opportunities/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->activity_type->value)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test an ability to view a list of appointments linked to contact.
     */
    public function testCanViewListOfAppointmentsLinkedToContact(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Appointment\Models\Appointment $appointment */
        $appointment = Appointment::factory()
            ->has(Contact::factory(), 'contactsHaveAppointment')
            ->create([
                'subject' => Str::random(40),
            ]);

        $response = $this->getJson('api/contacts/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'activity_type',
                        'subject',
                        'start_date',
                        'end_date',
                        'location',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/contacts/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->subject)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/contacts/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->activity_type->value)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test an ability to view a list of appointments linked to rescue quote.
     */
    public function testCanViewListOfAppointmentsLinkedToRescueQuote(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Appointment\Models\Appointment $appointment */
        $appointment = Appointment::factory()
            ->has(Quote::factory(), 'rescueQuotesHaveAppointment')
            ->create([
                'subject' => Str::random(40),
            ]);

        $response = $this->getJson('api/quotes/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'activity_type',
                        'subject',
                        'start_date',
                        'end_date',
                        'location',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/quotes/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->subject)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/quotes/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->activity_type->value)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test an ability to view a list of appointments linked to worldwide quote.
     */
    public function testCanViewListOfAppointmentsLinkedToWorldwideQuote(): void
    {
        $this->authenticateApi();

        /** @var \App\Domain\Appointment\Models\Appointment $appointment */
        $appointment = Appointment::factory()
            ->has(WorldwideQuote::factory(), 'worldwideQuotesHaveAppointment')
            ->create([
                'subject' => Str::random(40),
            ]);

        $response = $this->getJson('api/ww-quotes/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'activity_type',
                        'subject',
                        'start_date',
                        'end_date',
                        'location',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/ww-quotes/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->subject)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/ww-quotes/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.$appointment->activity_type->value)
//            ->dump()
            ->assertOk();

        $this->assertCount(1, $response->json('data'));

        $response = $this->getJson('api/ww-quotes/'.$appointment->modelsHaveAppointment->first()->model_id.'/appointments?search='.Str::random(40))
//            ->dump()
            ->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    /**
     * Test an ability to view an existing appointment.
     */
    public function testCanViewAppointment(): void
    {
        $this->authenticateApi();

        $appointment = Appointment::factory()
            ->has(AppointmentReminder::factory()->for($this->app['auth']->user(), relationship: 'owner'), 'reminder')
            ->has(Contact::factory(), 'contacts')
            ->has(AppointmentContactInvitee::factory(), 'inviteesContacts')
            ->has(User::factory(), 'users')
            ->has(User::factory(), 'inviteesUsers')
            ->has(Opportunity::factory(), 'opportunities')
            ->has(Company::factory(), 'companies')
            ->has(Quote::factory(), 'rescueQuotes')
            ->has(WorldwideQuote::factory(), 'worldwideQuotes')
            ->create();

        $response = $this->getJson('api/appointments/'.$appointment->getKey().'?'.Arr::query([
                'include' => [
                    'user_relations.related',
                    'contact_relations.related',
                    'company_relations.related',
                    'opportunity_relations.related',
//                    'invitee_contact_relations.related',
                    'invitee_user_relations.related',
                    'rescue_quote_relations.related',
                    'worldwide_quote_relations.related',
                ],
            ]))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'subject',
                'description',
                'start_date',
                'end_date',
                'user_relations' => [
                    '*' => [
                        'appointment_id', 'user_id',
                        'related' => [
                            'id',
                        ],
                    ],
                ],
                'contact_relations' => [
                    '*' => [
                        'appointment_id', 'contact_id',
                        'related' => [
                            'id',
                        ],
                    ],
                ],
                'company_relations' => [
                    '*' => [
                        'appointment_id', 'company_id',
                        'related' => [
                            'id',
                        ],
                    ],
                ],
                'opportunity_relations' => [
                    '*' => [
                        'appointment_id', 'opportunity_id',
                        'related' => [
                            'id',
                        ],
                    ],
                ],
                'invitee_contact_relations' => [
                    '*' => [
                        'appointment_id', 'contact_id',
                        'related' => [
                            'id',
                        ],
                    ],
                ],
                'invitee_user_relations' => [
                    '*' => [
                        'appointment_id', 'user_id',
                        'related' => [
                            'id',
                        ],
                    ],
                ],
                'rescue_quote_relations' => [
                    '*' => [
                        'appointment_id', 'quote_id',
                        'related' => [
                            'id',
                        ],
                    ],
                ],
                'worldwide_quote_relations' => [
                    '*' => [
                        'appointment_id', 'quote_id',
                        'related' => [
                            'id',
                        ],
                    ],
                ],
                'reminder' => [
                    'id',
                    'appointment_id',
                    'start_date_offset',
                    'status',
                    'created_at',
                    'updated_at',
                ],
                'created_at',
                'updated_at',
            ]);

        foreach ([
                     'user_relations',
                     'contact_relations',
                     'company_relations',
                     'opportunity_relations',
//                     'invitee_contact_relations',
                     'invitee_user_relations',
                     'rescue_quote_relations',
                     'worldwide_quote_relations',
                 ] as $key) {
            $this->assertNotEmpty($response->json($key), $key);
        }
    }

    /**
     * Test an ability to create a new appointment without reminder set.
     */
    public function testCanCreateAppointmentWithoutReminder(): void
    {
        $this->authenticateApi();

        /** @var User $user */
        $user = $this->app['auth']->user();

        $modelHasAppointment = Company::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $data['model_has_appointment'] = [
            'id' => $modelHasAppointment->getKey(),
            'type' => 'Company',
        ];

        $response = $this->postJson('api/appointments', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'user_relations',
                'contact_relations',
                'company_relations',
                'opportunity_relations',
                'invitee_contact_relations',
                'invitee_user_relations',
                'reminder',
                'created_at',
                'updated_at',
            ]);

        $response = $this->getJson('api/appointments/'.$response->json('id'))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'user_relations',
                'contact_relations',
                'company_relations',
                'opportunity_relations',
                'invitee_contact_relations',
                'invitee_user_relations',
                'reminder',
                'created_at',
                'updated_at',
            ]);

        foreach (Arr::except($data, 'model_has_appointment') as $key => $value) {
            $value = match ($key) {
                'start_date', 'end_date' => Carbon::parse($value)->tz($user->timezone->utc)->format('m/d/y H:i:s'),
                default => $value,
            };

            $this->assertSame($value, $response->json($key), $key);
        }
    }

    /**
     * Test an ability to create a new appointment linked to companies.
     */
    public function testCanCreateAppointmentLinkedToCompanies(): void
    {
        $this->authenticateApi();

        $modelHasAppointment = Company::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $data['model_has_appointment'] = [
            'id' => $modelHasAppointment->getKey(),
            'type' => 'Company',
        ];

        $company = Company::factory()->create();

        $data['company_relations'] = [
            [
                'company_id' => $company->getKey(),
            ],
        ];

        $response = $this->postJson('api/appointments', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations',
                'company_relations' => [
                    '*' => [
                        'appointment_id',
                        'company_id',
                    ],
                ],
                'opportunity_relations',
                'invitee_contact_relations',
                'invitee_user_relations',
            ]);

        $response = $this->getJson('api/appointments/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations',
                'company_relations' => [
                    '*' => [
                        'appointment_id',
                        'company_id',
                    ],
                ],
                'opportunity_relations',
                'invitee_contact_relations',
                'invitee_user_relations',
            ]);

        $this->assertContains($company->getKey(), $response->json('company_relations.*.company_id'));
    }

    /**
     * Test an ability to create a new appointment linked to opportunities.
     */
    public function testCanCreateAppointmentLinkedToOpportunities(): void
    {
        $this->authenticateApi();

        $modelHasAppointment = Company::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $data['model_has_appointment'] = [
            'id' => $modelHasAppointment->getKey(),
            'type' => 'Company',
        ];

        $opportunity = Opportunity::factory()->create();

        $data['opportunity_relations'] = [
            ['opportunity_id' => $opportunity->getKey()],
        ];

        $response = $this->postJson('api/appointments', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations',
                'company_relations',
                'opportunity_relations' => [
                    '*' => [
                        'appointment_id',
                        'opportunity_id',
                    ],
                ],
                'invitee_contact_relations',
                'invitee_user_relations',
            ]);

        $response = $this->getJson('api/appointments/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations',
                'company_relations',
                'opportunity_relations' => [
                    '*' => [
                        'appointment_id',
                        'opportunity_id',
                    ],
                ],
                'invitee_contact_relations',
                'invitee_user_relations',
            ]);

        $this->assertContains($opportunity->getKey(), $response->json('opportunity_relations.*.opportunity_id'));
    }

    /**
     * Test an ability to create a new appointment linked to contacts.
     */
    public function testCanCreateAppointmentLinkedToContacts(): void
    {
        $this->authenticateApi();

        $modelHasAppointment = Company::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $data['model_has_appointment'] = [
            'id' => $modelHasAppointment->getKey(),
            'type' => 'Company',
        ];

        $contact = Contact::factory()->create();

        $data['contact_relations'] = [
            ['contact_id' => $contact->getKey()],
        ];

        $response = $this->postJson('api/appointments', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations' => [
                    '*' => [
                        'appointment_id',
                        'contact_id',
                    ],
                ],
                'company_relations',
                'opportunity_relations',
                'invitee_contact_relations',
                'invitee_user_relations',
            ]);

        $response = $this->getJson('api/appointments/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations' => [
                    '*' => [
                        'appointment_id',
                        'contact_id',
                    ],
                ],
                'company_relations',
                'opportunity_relations',
                'invitee_contact_relations',
                'invitee_user_relations',
            ]);

        $this->assertContains($contact->getKey(), $response->json('contact_relations.*.contact_id'));
    }

    /**
     * Test an ability to create a new appointment with invitees users.
     */
    public function testCanCreateAppointmentWithInviteesUsers(): void
    {
        $this->authenticateApi();

        $modelHasAppointment = Company::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $data['model_has_appointment'] = [
            'id' => $modelHasAppointment->getKey(),
            'type' => 'Company',
        ];

        $user = User::factory()->create();

        $data['invitee_user_relations'] = [
            ['user_id' => $user->getKey()],
        ];

        $response = $this->postJson('api/appointments', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations',
                'company_relations',
                'opportunity_relations',
                'invitee_contact_relations',
                'invitee_user_relations' => [
                    '*' => [
                        'appointment_id',
                        'user_id',
                    ],
                ],
            ]);

        $response = $this->getJson('api/appointments/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations',
                'company_relations',
                'opportunity_relations',
                'invitee_contact_relations',
                'invitee_user_relations' => [
                    '*' => [
                        'appointment_id',
                        'user_id',
                    ],
                ],
            ]);

        $this->assertContains($user->getKey(), $response->json('invitee_user_relations.*.user_id'));
    }

//    /**
//     * Test an ability to create a new appointment with invitees contacts.
//     */
//    public function testCanCreateAppointmentWithInviteesContacts(): void
//    {
//        $this->authenticateApi();
//
//        $modelHasAppointment = Company::factory()->create();
//
//        $data = Appointment::factory()->make()->toArray();
//
//        $data['model_has_appointment'] = [
//            'id' => $modelHasAppointment->getKey(),
//            'type' => 'Company'
//        ];
//
//        $contact = Contact::factory()->create();
//
//        $data['invitee_contact_relations'] = [
//            ['contact_id' => $contact->getKey()],
//        ];
//
//        $response = $this->postJson('api/appointments', $data)
// //            ->dump()
//            ->assertCreated()
//            ->assertJsonStructure([
//                'id',
//                'subject',
//                'description',
//                'start_date',
//                'end_date',
//                'reminder',
//                'created_at',
//                'updated_at',
//                'user_relations',
//                'contact_relations',
//                'company_relations',
//                'opportunity_relations',
//                'invitee_contact_relations' => [
//                    '*' => [
//                        'appointment_id',
//                        'contact_id',
//                    ],
//                ],
//                'invitee_user_relations',
//            ]);
//
//        $response = $this->getJson('api/appointments/'.$response->json('id'))
//            ->assertOk()
//            ->assertJsonStructure([
//                'id',
//                'subject',
//                'description',
//                'start_date',
//                'end_date',
//                'reminder',
//                'created_at',
//                'updated_at',
//                'user_relations',
//                'contact_relations',
//                'company_relations',
//                'opportunity_relations',
//                'invitee_contact_relations' => [
//                    '*' => [
//                        'appointment_id',
//                        'contact_id',
//                    ],
//                ],
//                'invitee_user_relations',
//            ]);
//
//        $this->assertContains($contact->getKey(), $response->json('invitee_contact_relations.*.contact_id'));
//    }

    /**
     * Test an ability to create a new appointment with attachments.
     */
    public function testCanCreateAppointmentWithAttachments(): void
    {
        $this->authenticateApi();

        $modelHasAppointment = Company::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $data['model_has_appointment'] = [
            'id' => $modelHasAppointment->getKey(),
            'type' => 'Company',
        ];

        $attachment = factory(Attachment::class)->create();

        $data['attachment_relations'] = [
            ['attachment_id' => $attachment->getKey()],
        ];

        $response = $this->postJson('api/appointments', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations',
                'company_relations',
                'opportunity_relations',
                'attachment_relations' => [
                    '*' => [
                        'attachable_id',
                        'attachment_id',
                    ],
                ],
                'invitee_user_relations',
            ]);

        $response = $this->getJson('api/appointments/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
                'user_relations',
                'contact_relations',
                'company_relations',
                'opportunity_relations',
                'attachment_relations' => [
                    '*' => [
                        'attachable_id',
                        'attachment_id',
                    ],
                ],
                'invitee_user_relations',
            ]);

        $this->assertContains($attachment->getKey(), $response->json('attachment_relations.*.attachment_id'));
    }

    /**
     * Test an ability to create a new appointment with reminder set.
     */
    public function testCanCreateAppointmentWithReminder(): void
    {
        $this->authenticateApi();

        /** @var User $user */
        $user = $this->app['auth']->user();

        $modelHasAppointment = Company::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $data['model_has_appointment'] = [
            'id' => $modelHasAppointment->getKey(),
            'type' => 'Company',
        ];

        $reminder = AppointmentReminder::factory()->make()->toArray();

        $data['reminder'] = Arr::except($reminder, 'appointment_id');

        $response = $this->postJson('api/appointments', $data)
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder' => [
                    'id',
                    'appointment_id',
                    'start_date_offset',
                    'status',
                    'created_at',
                    'updated_at',
                ],
                'created_at',
                'updated_at',
            ]);

        $response = $this->getJson('api/appointments/'.$response->json('id'))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder' => [
                    'id',
                    'appointment_id',
                    'start_date_offset',
                    'status',
                    'created_at',
                    'updated_at',
                ],
                'created_at',
                'updated_at',
            ]);

        foreach (Arr::except($data, ['reminder', 'model_has_appointment']) as $key => $value) {
            $value = match ($key) {
                'start_date', 'end_date' => Carbon::parse($value)->tz($user->timezone->utc)->format('m/d/y H:i:s'),
                default => $value,
            };

            $this->assertSame($value, $response->json($key), $key);
        }

        foreach (Arr::dot(Arr::only($data, 'reminder')) as $key => $value) {
            $this->assertSame($value, $response->json($key), $key);
        }
    }

    /**
     * Test an ability to update an existing appointment without reminder set.
     */
    public function testCanUpdateAppointmentWithoutReminder(): void
    {
        $appointment = Appointment::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $this->authenticateApi();

        /** @var User $user */
        $user = $this->app['auth']->user();

        $this->patchJson('api/appointments/'.$appointment->getKey(), $data)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
            ]);

        $response = $this->getJson('api/appointments/'.$appointment->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder',
                'created_at',
                'updated_at',
            ]);

        foreach ($data as $key => $value) {
            $value = match ($key) {
                'start_date', 'end_date' => Carbon::parse($value)->tz($user->timezone->utc)->format('m/d/y H:i:s'),
                default => $value,
            };

            $this->assertSame($value, $response->json($key), $key);
        }
    }

    /**
     * Test an ability to update an existing appointment with reminder set.
     */
    public function testCanUpdateAppointmentWithReminder(): void
    {
        $appointment = Appointment::factory()->create();

        $data = Appointment::factory()->make()->toArray();

        $reminder = AppointmentReminder::factory()->make()->toArray();

        $data['reminder'] = Arr::except($reminder, 'appointment_id');

        $this->authenticateApi();

        /** @var \App\Domain\User\Models\User $user */
        $user = $this->app['auth']->user();

        $this->patchJson('api/appointments/'.$appointment->getKey(), $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder' => [
                    'id',
                    'appointment_id',
                    'start_date_offset',
                    'status',
                    'created_at',
                    'updated_at',
                ],
                'created_at',
                'updated_at',
            ]);

        $response = $this->getJson('api/appointments/'.$appointment->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activity_type',
                'subject',
                'description',
                'start_date',
                'end_date',
                'reminder' => [
                    'id',
                    'appointment_id',
                    'start_date_offset',
                    'status',
                    'created_at',
                    'updated_at',
                ],
                'created_at',
                'updated_at',
            ]);

        foreach (Arr::dot($data) as $key => $value) {
            $value = match ($key) {
                'start_date', 'end_date' => Carbon::parse($value)->tz($user->timezone->utc)->format('m/d/y H:i:s'),
                default => $value,
            };

            $this->assertSame($value, $response->json($key), $key);
        }
    }

    /**
     * Test an ability to set appointment reminder.
     */
    public function testCanSetAppointmentReminder(): void
    {
        $this->authenticateApi();

        $reminder = AppointmentReminder::factory()->create();

        $data = [
            'snooze_date' => now()->addMinutes(5)->format('Y-m-d H:i:s'),
            'status' => ReminderStatus::Snoozed->value,
        ];

        $tz = $this->app['auth']->user()->timezone->utc ?? config('app.timezone');

        $snoozeDateConv = Carbon::instance(Carbon::parse($data['snooze_date'], tz: $tz))->tz(config('app.timezone'));

        $this->putJson("api/appointment-reminders/{$reminder->getKey()}", $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'appointment_id',
                    'user_id',
                    'start_date_offset',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.status', $data['status'])
            ->assertJsonPath('data.snooze_date', $snoozeDateConv->toJSON());
    }

    /**
     * Test an ability to dismiss appointment reminder.
     */
    public function testCanDismissAppointmentReminder(): void
    {
        $this->authenticateApi();

        $reminder = AppointmentReminder::factory()
            ->for($this->app['auth']->user(), relationship: 'owner')
            ->create([
                'status' => ReminderStatus::Scheduled,
            ]);

        $this->getJson("api/appointments/{$reminder->appointment->getKey()}")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'reminder',
            ])
            ->assertJsonPath('reminder.id', $reminder->getKey());

        $data = [
            'status' => ReminderStatus::Dismissed->value,
        ];

        $this->putJson("api/appointment-reminders/{$reminder->getKey()}", $data)
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'appointment_id',
                    'user_id',
                    'start_date_offset',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.status', $data['status']);

        $this->getJson("api/appointments/{$reminder->appointment->getKey()}")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'reminder',
            ])
            ->assertJsonPath('reminder', null);
    }

    /**
     * Test an ability to delete an existing reminder from appointment.
     */
    public function testCanDeleteReminderFromAppointment(): void
    {
        $this->authenticateApi();

        $appointment = Appointment::factory()
            ->has(AppointmentReminder::factory()->for($this->app['auth']->user(), 'owner'), relationship: 'reminder')
            ->create();

        $r = $this->getJson("api/appointments/{$appointment->getKey()}")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'reminder' => [
                    'id',
                ],
            ]);

        $reminderId = $r->json('reminder.id');

        $this->assertNotNull($reminderId);

        $this->deleteJson("api/appointment-reminders/$reminderId")
//            ->dump()
            ->assertNoContent();

        $this->getJson("api/appointments/{$appointment->getKey()}")
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'reminder',
            ])
            ->assertJsonPath('reminder', null);
    }

    /**
     * Test an ability to delete an existing appointment.
     */
    public function testCanDeleteAppointment(): void
    {
        $this->authenticateApi();

        $appointment = Appointment::factory()->has(AppointmentReminder::factory(), 'reminder')->create();

        $this->deleteJson('api/appointments/'.$appointment->getKey())
            ->assertNoContent();

        $this->getJson('api/appointments/'.$appointment->getKey())
            ->assertNotFound();
    }
}
