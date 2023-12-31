<?php

namespace App\Domain\Contact\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Contact\Models\{
    Contact,
};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContactService
{
    protected Contact $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Retrieve contacts based on given addresses.
     *
     * @param Collection[Address] $addresses
     */
    public function retrieveContactsFromAddresses(Collection $addresses): Collection
    {
        $contacts = Collection::make();

        DB::transaction(
            fn () => $addresses->whereInstanceOf(Address::class)
                ->filter(fn (Address $address) => filled($address->contact_name) || filled($address->contact_email))
                ->each(function (Address $address) use ($contacts) {
                    $attributes = static::parseAttributesFromAddress($address);

                    $contact = $this->contact->firstOrCreate(
                        Arr::only($attributes, ['phone', 'contact_name', 'contact_type', 'email']),
                        $attributes
                    );

                    $contacts->push($contact);
                })
        );

        return $contacts;
    }

    protected static function parseAttributesFromAddress(Address $address): array
    {
        $contactName = Str::of($address->contact_name)->replaceMatches('/\s+/', ' ')->trim();

        return [
            'phone' => $address->contact_number,
            'contact_name' => (string) $contactName,
            'first_name' => (string) $contactName->before(' ')->trim(),
            'last_name' => (string) $contactName->after(' ')->trim(),
            'contact_type' => $address->address_type,
            'email' => $address->contact_email,
            'is_verified' => true,
        ];
    }
}
