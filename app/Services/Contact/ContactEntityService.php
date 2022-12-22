<?php

namespace App\Services\Contact;

use App\Contracts\CauserAware;
use App\DTO\Contact\CreateContactCompanyRelationNoBackrefData;
use App\DTO\Contact\CreateContactData;
use App\DTO\Contact\UpdateContactData;
use App\Events\Contact\ContactCreated;
use App\Events\Contact\ContactDeleted;
use App\Events\Contact\ContactUpdated;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Image;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\LaravelData\DataCollection;
use Webpatser\Uuid\Uuid;

class ContactEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected EventDispatcher $eventDispatcher
    ) {
    }

    public function createContact(CreateContactData $data): Contact
    {
        return tap(new Contact(), function (Contact $contact) use ($data) {
            $contact->{$contact->getKeyName()} = (string) Uuid::generate(4);
            $contact->user()->associate($this->causer);

            $contact->forceFill(
                $data->except('picture', 'company_relations')->all()
            );

            $companyRelations = collect();

            if ($data->company_relations instanceof DataCollection) {
                $companyRelations = $this->mapCompanyRelations($data->company_relations);
            }

            $this->connection->transaction(static function () use ($companyRelations, $contact): void {
                $contact->save();
                $contact->companies()->syncWithoutDetaching($companyRelations->all());
            });

            if (false === is_null($data->picture)) {
                $contact->image()->delete();
                $this->createImageForContact($contact, $data->picture);
                $contact->image()->flushQueryCache();
            }

            $this->eventDispatcher->dispatch(
                new ContactCreated(contact: $contact, causer: $this->causer)
            );
        });
    }

    public function updateContact(Contact $contact, UpdateContactData $data): Contact
    {
        return tap($contact, function (Contact $contact) use ($data) {
            $oldContact = (new Contact)->setRawAttributes($contact->getRawOriginal());

            $contact->forceFill(
                $data->except('picture', 'company_relations')->all()
            );

            $companyRelations = collect();

            if ($data->company_relations instanceof DataCollection) {
                $companyRelations = $this->mapCompanyRelations($data->company_relations);
            }

            $this->connection->transaction(static function () use ($contact, $companyRelations): void {
                $contact->save();
                $contact->companies()->syncWithoutDetaching($companyRelations->all());
            });

            if (false === is_null($data->picture)) {
                $contact->image()->delete();
                $this->createImageForContact($contact, $data->picture);
                $contact->image()->flushQueryCache();
            }

            $this->eventDispatcher->dispatch(
                new ContactUpdated(contact: $oldContact, newContact: $contact, causer: $this->causer)
            );
        });
    }

    public function associateContactWithAddress(Contact $contact, Address $address): Contact
    {
        return tap($contact, function (Contact $contact) use ($address) {
            $oldContact = (new Contact)->setRawAttributes($contact->getRawOriginal());
            $contact->address()->associate($address);

            $this->connection->transaction(static fn() => $contact->save());

            $this->eventDispatcher->dispatch(
                new ContactUpdated(contact: $oldContact, newContact: $contact, causer: $this->causer)
            );
        });
    }

    public function deleteContact(Contact $contact): void
    {
        $this->connection->transaction(static fn() => $contact->delete());

        $this->eventDispatcher->dispatch(
            new ContactDeleted(contact: $contact, causer: $this->causer)
        );
    }

    public function markContactAsActive(Contact $contact): void
    {
        $contact->activated_at = now();

        $this->connection->transaction(static fn() => $contact->save());
    }

    public function markContactAsInactive(Contact $contact): void
    {
        $contact->activated_at = null;

        $this->connection->transaction(static fn() => $contact->save());
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }

    protected function mapCompanyRelations(DataCollection $relations): BaseCollection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $relations->toCollection()
            ->mapWithKeys(function (CreateContactCompanyRelationNoBackrefData $data): array {
                $attributes = $data->except('id')->all();

                return [$data->id => $attributes];
            });
    }

    protected function createImageForContact(Contact $contact, UploadedFile $file): Image
    {
        $modelImagesDir = $contact->imagesDirectory();

        $image = ImageManagerStatic::make($file->get());

        $imageProperties = $this->getContactImageProperties();

        $image->resize($imageProperties['width'], $imageProperties['height'], static function ($constraint): void {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $storageDir = "public/$modelImagesDir";

        if (Storage::missing($storageDir)) {
            Storage::makeDirectory($storageDir);
        }

        $original = "$modelImagesDir/{$file->hashName()}";

        $image->save(Storage::path("public/$original"));

        return tap(new Image(), function (Image $imageEntity) use ($original, $contact): void {
            $imageEntity->original = $original;
            $imageEntity->imageable()->associate($contact);

            $this->connection->transaction(static fn() => $imageEntity->save());
        });
    }

    #[ArrayShape(['width' => "int", 'height' => "int"])]
    protected function getContactImageProperties(): array
    {
        return ['width' => 240, 'height' => 240];
    }
}