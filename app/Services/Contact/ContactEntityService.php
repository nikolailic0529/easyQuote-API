<?php

namespace App\Services\Contact;

use App\Contracts\CauserAware;
use App\DTO\Contact\CreateContactData;
use App\DTO\Contact\UpdateContactData;
use App\Events\Contact\ContactCreated;
use App\Events\Contact\ContactDeleted;
use App\Events\Contact\ContactUpdated;
use App\Models\Contact;
use App\Models\Image;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;
use JetBrains\PhpStorm\ArrayShape;
use Webpatser\Uuid\Uuid;

class ContactEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(protected ConnectionInterface $connection,
                                protected EventDispatcher     $eventDispatcher)
    {
    }

    public function createContact(CreateContactData $data): Contact
    {
        return tap(new Contact(), function (Contact $contact) use ($data) {
            $contact->{$contact->getKeyName()} = (string)Uuid::generate(4);
            $contact->contact_type = $data->contact_type;
            $contact->first_name = $data->first_name;
            $contact->last_name = $data->last_name;
            $contact->phone = $data->phone;
            $contact->mobile = $data->mobile;
            $contact->job_title = $data->job_title;
            $contact->is_verified = $data->is_verified;

            $this->connection->transaction(fn() => $contact->save());

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

    protected function createImageForContact(Contact $contact, UploadedFile $file): Image
    {
        $modelImagesDir = $contact->imagesDirectory();

        $image = ImageManagerStatic::make($file->get());

        $imageProperties = $this->getContactImageProperties();

        $image->resize($imageProperties['width'], $imageProperties['height'], function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $storageDir = "public/$modelImagesDir";

        if (Storage::missing($storageDir)) {
            Storage::makeDirectory($storageDir);
        }

        $original = "$modelImagesDir/{$file->hashName()}";

        $image->save(Storage::path("public/$original"));

        return tap(new Image(), function (Image $imageEntity) use ($original, $contact) {
            $imageEntity->original = $original;
            $imageEntity->imageable()->associate($contact);

            $this->connection->transaction(fn() => $imageEntity->save());
        });
    }

    #[ArrayShape(['width' => "int", 'height' => "int"])]
    protected function getContactImageProperties(): array
    {
        return ['width' => 240, 'height' => 240];
    }

    public function updateContact(Contact $contact, UpdateContactData $data): Contact
    {
        return tap($contact, function (Contact $contact) use ($data) {
            $oldContact = (new Contact)->setRawAttributes($contact->getRawOriginal());

            $contact->contact_type = $data->contact_type;
            $contact->first_name = $data->first_name;
            $contact->last_name = $data->last_name;
            $contact->phone = $data->phone;
            $contact->mobile = $data->mobile;
            $contact->job_title = $data->job_title;
            $contact->is_verified = $data->is_verified;

            $this->connection->transaction(fn() => $contact->save());

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

    public function deleteContact(Contact $contact): void
    {
        $this->connection->transaction(fn() => $contact->delete());

        $this->eventDispatcher->dispatch(
            new ContactDeleted(contact: $contact, causer: $this->causer)
        );
    }

    public function markContactAsActive(Contact $contact): void
    {
        $contact->activated_at = now();

        $this->connection->transaction(fn() => $contact->save());
    }

    public function markContactAsInactive(Contact $contact): void
    {
        $contact->activated_at = null;

        $this->connection->transaction(fn() => $contact->save());
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}