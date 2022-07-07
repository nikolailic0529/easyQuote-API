<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Vendor;
use App\Services\Opportunity\OpportunityEntityValidator;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpportunityEntityValidatorTest extends TestCase
{
    /**
     * Test a valid opportunity entity passes the validation.
     */
    public function test_valid_opportunity_passes_validation(): void
    {
        /** @var OpportunityEntityValidator $validator */
        $validator = $this->app[OpportunityEntityValidator::class];

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $opp->primaryAccount->vendors()->attach(factory(Vendor::class)->create());
        $opp->primaryAccount->addresses()->attach(factory(Address::class)->create());
        $opp->primaryAccount->contacts()->attach(Contact::factory()->create());
        $opp->endUser->addresses()->attach(factory(Address::class)->create());
        $opp->endUser->contacts()->attach(Contact::factory()->create());


        $this->assertEmpty($validator($opp)->getMessages());
    }

    /**
     * Test an opportunity entity fails the validation with invalid primary account attributes.
     */
    public function test_invalid_opportunity_fails_validation_with_invalid_primary_account_attributes(): void
    {
        /** @var OpportunityEntityValidator $validator */
        $validator = $this->app[OpportunityEntityValidator::class];

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $opp->primaryAccount->update(['vat_type' => 'VAT Number', 'vat' => '']);

        $opp->primaryAccount->vendors()->detach();
        $opp->primaryAccount->addresses()->detach();
        $opp->primaryAccount->contacts()->detach();

        $errorBag = $validator($opp);

        $this->assertNotEmpty($errorBag->getMessages());

        $this->assertArrayHasKey('primary_account.vat', $errorBag->getMessages());
        $this->assertArrayHasKey('primary_account.vendors', $errorBag->getMessages());
        $this->assertArrayHasKey('primary_account.addresses', $errorBag->getMessages());
        $this->assertArrayHasKey('primary_account.contacts', $errorBag->getMessages());
    }

    /**
     * Test an opportunity entity fails the validation with missing primary account entity.
     */
    public function test_invalid_opportunity_fails_validation_with_missing_primary_account(): void
    {
        /** @var OpportunityEntityValidator $validator */
        $validator = $this->app[OpportunityEntityValidator::class];

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $opp->primaryAccount()->disassociate();

        $errorBag = $validator($opp);

        $this->assertNotEmpty($errorBag->getMessages());

        $this->assertArrayHasKey('primary_account', $errorBag->getMessages());
    }

    /**
     * Test an opportunity entity fails the validation with invalid primary account contact attributes.
     */
    public function test_invalid_opportunity_fails_validation_with_invalid_primary_account_contact_attributes(): void
    {
        /** @var OpportunityEntityValidator $validator */
        $validator = $this->app[OpportunityEntityValidator::class];

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $opp->primaryAccountContact->update(['email' => Str::random()]);

        $errorBag = $validator($opp);

        $this->assertNotEmpty($errorBag->getMessages());

        $this->assertArrayHasKey('primary_account_contact.email', $errorBag->getMessages());
    }

    /**
     * Test an opportunity entity fails the validation with missing primary account contact entity.
     */
    public function test_invalid_opportunity_fails_validation_with_missing_primary_account_contact(): void
    {
        /** @var OpportunityEntityValidator $validator */
        $validator = $this->app[OpportunityEntityValidator::class];

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $opp->primaryAccountContact()->disassociate();

        $errorBag = $validator($opp);

        $this->assertNotEmpty($errorBag->getMessages());

        $this->assertArrayHasKey('primary_account_contact', $errorBag->getMessages());
    }

    /**
     * Test an opportunity entity fails the validation with invalid end user attributes.
     */
    public function test_invalid_opportunity_fails_validation_with_invalid_end_user_attributes(): void
    {
        /** @var OpportunityEntityValidator $validator */
        $validator = $this->app[OpportunityEntityValidator::class];

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $opp->endUser->update(['vat_type' => 'VAT Number', 'vat' => '']);

        $opp->endUser->addresses()->detach();
        $opp->endUser->contacts()->detach();

        $errorBag = $validator($opp);

        $this->assertNotEmpty($errorBag->getMessages());

        $this->assertArrayHasKey('end_user.vat', $errorBag->getMessages());
        $this->assertArrayHasKey('end_user.addresses', $errorBag->getMessages());
        $this->assertArrayHasKey('end_user.contacts', $errorBag->getMessages());
    }

    /**
     * Test an opportunity entity fails the validation with missing end user entity.
     */
    public function test_invalid_opportunity_fails_validation_with_missing_end_user(): void
    {
        /** @var OpportunityEntityValidator $validator */
        $validator = $this->app[OpportunityEntityValidator::class];

        /** @var Opportunity $opp */
        $opp = Opportunity::factory()->create();

        $opp->endUser()->disassociate();

        $errorBag = $validator($opp);

        $this->assertNotEmpty($errorBag->getMessages());

        $this->assertArrayHasKey('end_user', $errorBag->getMessages());
    }

}
