<?php

namespace Tests\Unit;

use App\DTO\Opportunity\BatchOpportunityUploadResult;
use App\DTO\Opportunity\ImportOpportunityData;
use App\Models\User;
use App\Services\Opportunity\OpportunityEntityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;
use Tests\TestCase;

class OpportunityImportTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test imports PPS excel files.
     *
     * @return void
     */
    public function testImportsPpsExcelFiles()
    {
        /** @var OpportunityEntityService $entityService */
        $entityService = $this->app[OpportunityEntityService::class];

        $data = new ImportOpportunityData([
            'opportunities_file' => UploadedFile::fake()->createWithContent('PSS.xlsx', file_get_contents(base_path('tests/Unit/Data/opportunity/PSS.xlsx'))),
            'accounts_data_file' => UploadedFile::fake()->createWithContent('PPS Account.xlsx', file_get_contents(base_path('tests/Unit/Data/opportunity/PPS Account.xlsx'))),
            'account_contacts_file' => UploadedFile::fake()->createWithContent('PPS Contact.xlsx', file_get_contents(base_path('tests/Unit/Data/opportunity/PPS Contact.xlsx'))),
        ]);

        $user = factory(User::class)->create();

        $result = $entityService->batchImportOpportunities($data, $user);

        $this->assertEmpty($result->errors);

        $this->assertCount(1, $result->opportunities);

        $this->assertInstanceOf(BatchOpportunityUploadResult::class, $result);

        $this->assertSame('Contract', $result->opportunities[0]->opportunity_type);
        $this->assertSame('PPS - Price Performance Solutions', $result->opportunities[0]->account_name);
        $this->assertSame('Christian Pullara', $result->opportunities[0]->account_manager_name);
        $this->assertSame(35653.367875648, $result->opportunities[0]->opportunity_amount);
        $this->assertSame('£ 35,653.37', $result->opportunities[0]->opportunity_amount_formatted);
        $this->assertSame('2021-04-01', $result->opportunities[0]->opportunity_start_date);
        $this->assertSame('2022-03-31', $result->opportunities[0]->opportunity_end_date);
        $this->assertSame('2021-06-30', $result->opportunities[0]->opportunity_closing_date);
        $this->assertSame('1. Preparation', $result->opportunities[0]->sale_action_name);
        $this->assertSame('Germany (Equens) PPS IBM Services 02062021', $result->opportunities[0]->project_name);
        $this->assertSame('None', $result->opportunities[0]->campaign_name);
    }
}
