<?php

use App\Models\Quote\WorldwideQuote;
use App\Services\WorldwideQuote\WorldwideQuoteAttachmentService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @var WorldwideQuoteAttachmentService $service */
        $service = app(WorldwideQuoteAttachmentService::class);

        $conn = DB::connection($this->getConnection());

        $conn->transaction(static function () use ($service): void {
            WorldwideQuote::query()
                ->whereNotNull('submitted_at')
                ->lazyById(100)
                ->each(static function (WorldwideQuote $quote) use ($service): void {
                    rescue(fn() => $service->createAttachmentFromSubmittedQuote($quote), rescue: static function ()
                    use ($quote): void {
                        Log::warning("Could not create attachment from submitted quote.", [
                            'id' => $quote->getKey(),
                            'quote_number' => $quote->quote_number,
                        ]);
                    });
                    rescue(fn() => $service->createAttachmentFromDistributorFiles($quote), rescue: static function ()
                    use ($quote): void {
                        Log::warning("Could not create attachment from distributor files.", [
                            'id' => $quote->getKey(),
                            'quote_number' => $quote->quote_number,
                        ]);
                    });
                });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
