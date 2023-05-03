<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $companyIds = DB::table('companies')
            ->whereRaw('not flags & (1 << 0)')
            ->whereNull('deleted_at')
            ->pluck('id');

        $addressesToUpdate = $companyIds->reduce(static function (array $addressesToUpdate, string $companyId): array {
            $addressesOfCompany = DB::table('addressables')
                ->where('addressables.addressable_id', $companyId)
                ->join('addresses', 'addresses.id', 'addressables.address_id')
                ->whereNull('addresses.deleted_at')
                ->whereNull('addresses.contact_id')
                ->get([
                    'addresses.id', 'addresses.contact_id',
                ]);

            if ($addressesOfCompany->isEmpty()) {
                return $addressesToUpdate;
            }

            $idOfFirstContact = DB::table('contactables')
                ->where('contactables.contactable_id', $companyId)
                ->join('contacts', 'contacts.id', 'contactables.contact_id')
                ->whereNull('contacts.deleted_at')
                ->orderBy('contacts.created_at')
                ->value('contacts.id');

            if (is_null($idOfFirstContact)) {
                return $addressesToUpdate;
            }

            foreach ($addressesOfCompany as $address) {
                $address->contact_id = $idOfFirstContact;
                $addressesToUpdate[] = $address;
            }

            return $addressesToUpdate;
        }, []);

        DB::transaction(static function () use ($addressesToUpdate): void {
            foreach ($addressesToUpdate as $address) {
                DB::table('addresses')
                    ->where('id', $address->id)
                    ->update(['contact_id' => $address->contact_id]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
