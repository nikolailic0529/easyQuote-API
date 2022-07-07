<?php

use App\Models\AccessAttempt;
use App\Models\Address;
use App\Models\Addressable;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetTotal;
use App\Models\Attachment;
use App\Models\BusinessDivision;
use App\Models\CancelSalesOrderReason;
use App\Models\Collaboration\Invitation;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Contactable;
use App\Models\ContractType;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerTotal;
use App\Models\Customer\WorldwideCustomer;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Data\ExchangeRate;
use App\Models\Data\Language;
use App\Models\Data\Timezone;
use App\Models\DocumentProcessLog;
use App\Models\HpeContract;
use App\Models\HpeContractData;
use App\Models\HpeContractFile;
use App\Models\Image;
use App\Models\InternalCompany;
use App\Models\Location;
use App\Models\ModelHasRoles;
use App\Models\ModelNotification;
use App\Models\Opportunity;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\OpportunityForm\OpportunityFormSchema;
use App\Models\OpportunitySupplier;
use App\Models\PasswordReset;
use App\Models\Permission;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeline\PipelineStage;
use App\Models\Quote\BaseQuote;
use App\Models\Quote\BaseWorldwideQuote;
use App\Models\Quote\Contract;
use App\Models\Quote\ContractFieldColumn;
use App\Models\Quote\Discount\Discount;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\DistributionFieldColumn;
use App\Models\Quote\FieldColumn;
use App\Models\Quote\Margin\CountryMargin;
use App\Models\Quote\Margin\Margin;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteLocationTotal;
use App\Models\Quote\QuoteTotal;
use App\Models\Quote\QuoteVersion;
use App\Models\Quote\QuoteVersionFieldColumn;
use App\Models\Quote\QuoteVersionPivot;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\DataSelectSeparator;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\QuoteFile\ImportableColumnAlias;
use App\Models\QuoteFile\ImportedRawData;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\QuoteFileFormat;
use App\Models\QuoteFile\ScheduleData;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Space;
use App\Models\System\Activity;
use App\Models\System\ActivityExportCollection;
use App\Models\System\Build;
use App\Models\System\CustomField;
use App\Models\System\CustomFieldValue;
use App\Models\System\DocumentProcessorDriver;
use App\Models\System\Notification;
use App\Models\System\Period;
use App\Models\System\SystemSetting;
use App\Models\Task\Task;
use App\Models\Team;
use App\Models\Template\ContractTemplate;
use App\Models\Template\HpeContractTemplate;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\SalesOrderTemplate;
use App\Models\Template\TemplateField;
use App\Models\Template\TemplateFieldType;
use App\Models\Template\TemplateForm;
use App\Models\Template\TemplateSchema;
use App\Models\User;
use App\Models\UserForm;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Models\WorldwideQuoteAssetsGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateMorphTypeActivityLogAddressablesContactablesDiscountsImagesModelHasPermissionsModelHasRolesModelNotificationsNotificationsTasksTables extends Migration
{
    protected array $morphMap = [
        'fe2c0408-86de-47e2-9e12-67226ac330b1' => AccessAttempt::class,
        '5c322beb-f462-4b94-a94f-d8192637ee80' => Address::class,
        '9b4c200d-7c73-4fe3-98c7-a15aa060a55e' => Addressable::class,
        '6e7c6f29-37fd-4501-beb0-d39aeea0143f' => Asset::class,
        'c64cd840-17f0-4752-99dd-7d0182d16788' => AssetCategory::class,
        '0b0ca451-7659-401f-9951-b5d63747ce66' => AssetTotal::class,
        '20caecb5-9e30-40f4-b874-27d66e50239a' => Attachment::class,
        '38df4fea-50fe-4c32-902a-d43a0d56c306' => BusinessDivision::class,
        '6d4eb8cf-81e7-4b0a-9631-1870c81de94f' => CancelSalesOrderReason::class,
        '4e4c5a84-e117-4fc5-8c3c-11173555bc83' => Invitation::class,
        'af1ef6eb-48be-41aa-a7c9-889119a10536' => Contact::class,
        '76fc0327-a848-412a-86be-74ae4e0eea7d' => Contactable::class,
        '8fb134cd-f970-4b42-b8d1-cd685f4e6896' => ContractType::class,
        'e50d0626-b012-40cb-849c-e9b7dfe71a5b' => Customer::class,
        '2437f12a-0018-4f87-addb-297210ddcbb6' => CustomerTotal::class,
        '6207b123-39a6-4280-a6ed-bce1030ae702' => WorldwideCustomer::class,
        '382bc911-bbd5-45ba-9181-8b670ec1cebd' => Country::class,
        '441b9dec-833e-4da1-aad2-8c131b083bbd' => Currency::class,
        '77cfbbea-4988-4fac-bf9c-bfc2a1a2685c' => ExchangeRate::class,
        'ef95785b-32e9-48e4-a435-8e751bbdd024' => Language::class,
        '3e2dce9b-0e28-4c4e-b37c-8e653443bb5d' => Timezone::class,
        '24fd1a13-af45-4887-9dfa-d280afd4a173' => DocumentProcessLog::class,
        '117b2e3b-bada-4fb5-a7d4-a99e09de2041' => HpeContract::class,
        '07a3ef78-35cc-4981-a094-572144835b2d' => HpeContractData::class,
        '57e996cb-db10-4e6e-9229-fb349369e385' => HpeContractFile::class,
        '54ef4dda-7bdc-43ed-baa8-a24f735856aa' => Image::class,
        'fbfb19de-1ecd-46b6-8b0a-b5ad8153e19b' => InternalCompany::class,
        'e03c3f6b-91af-4fe0-beb2-2b8af5e767a5' => Location::class,
        '77f32beb-632e-4048-9720-bdfca9cae9b9' => ModelHasRoles::class,
        '94584819-6350-4fb8-8a4b-010fd1737b5b' => ModelNotification::class,
        'd06b9a45-6874-4b3c-85ff-aee9dce45017' => PasswordReset::class,
        'f5dea766-ef6e-47b8-aea7-f71d1875557f' => Permission::class,
        '501d2c5e-482a-4190-8a8d-113a2a2e9baa' => PipelineStage::class,
        'aab7d2a1-1f00-4db5-b5bd-3c8b604d2767' => DataSelectSeparator::class,
        '0c64a174-3c95-4544-b9c6-800d6ff8cb01' => DistributionRowsGroup::class,
        '69b784f4-28ee-44e3-b903-3005c802e43c' => ImportableColumn::class,
        '2eac650c-f311-4049-9df6-1c04c422c236' => ImportableColumnAlias::class,
        '4754ac76-f4e9-4c06-9d42-23a0a99dc16e' => ImportedRawData::class,
        'be701139-b801-472e-9c33-710fc9f3a445' => ImportedRow::class,
        'f1e04dc1-adc3-4459-b21f-fd3fb9c41ef5' => MappedRow::class,
        '8913ea48-e561-4ef5-ad6d-2c3922a580c1' => QuoteFile::class,
        'cfd53ac5-610d-4d62-bd37-ad45c2b113f5' => QuoteFileFormat::class,
        '4259d1ed-821d-4041-9108-48bb2f7ba5b0' => ScheduleData::class,
        'c81895a1-4a5f-4cde-8d64-94e5ef6427b5' => BaseQuote::class,
        'ac73c359-4beb-41b4-86de-4548e63c1244' => BaseWorldwideQuote::class,
        '41ada60c-2646-4579-b0fc-097f0bda4339' => Contract::class,
        '9984e3af-4a95-4095-a65b-bdb251ae55ba' => ContractFieldColumn::class,
        '856c0b52-c33f-443a-b055-b1ed6833714c' => \App\Models\Quote\Discount::class,
        'ace97f60-b7b1-4a9e-8fbb-aa0960ec5044' => Discount::class,
        '0cf9a1aa-13a8-4c20-a187-51c45552c848' => MultiYearDiscount::class,
        '00dd8cd2-fea4-48c3-836f-d14429e4113e' => PrePayDiscount::class,
        '760f4ca1-a83c-497f-812c-3b8b4c406b21' => PromotionalDiscount::class,
        '37afc89b-5ee2-4600-be5c-7a5615419091' => SND::class,
        '3f821905-e644-4d1a-9a1a-0b86c2179ec1' => DistributionFieldColumn::class,
        '52e9ffe8-0900-4b55-97cc-c21522194944' => FieldColumn::class,
        '1aeb039c-ce7e-495d-b6c2-063aef51fd2c' => CountryMargin::class,
        '523c0e3d-e31f-4e6f-abb3-572814cee231' => Margin::class,
        'd784c4a2-2322-4e3a-978b-c5b7620137a9' => QuoteLocationTotal::class,
        '8729ab2d-e412-4871-83e6-87c7310e2dfa' => QuoteTotal::class,
        'f3044d32-d2fd-48bd-b5cc-b3b04160ec24' => QuoteVersion::class,
        '6f2954ea-ace0-4c56-9403-78de13e109ca' => QuoteVersionFieldColumn::class,
        'c6101044-388e-420a-b0fb-dc12ff46eb9f' => QuoteVersionPivot::class,
        '9a82a4c5-61e1-4a8b-9c24-ea160e52a3b7' => WorldwideDistribution::class,
        'a2f361dc-3c76-4dcb-a782-33415cfb3b18' => Role::class,
        '11c7ef54-d5ba-464c-ae86-a1c88164028c' => SalesOrder::class,
        'e7bc2faf-c5e9-40e1-b962-db7fdaeac22d' => Space::class,
        '3c25a789-e33e-4cfb-891d-3287c90ef01c' => Activity::class,
        'a18e08bf-354b-4ecc-8891-1198cdcfc111' => ActivityExportCollection::class,
        'c35258c9-9d97-47f1-97d5-0403c8d32579' => Build::class,
        '0bb95663-87f3-48f1-ac8b-4323f7fdc024' => CustomField::class,
        '7304a4d1-e7d0-4650-b31b-196c7d57d44e' => CustomFieldValue::class,
        'b1351bb6-3797-4a40-85da-87269c611a84' => DocumentProcessorDriver::class,
        'b76ac480-8635-4f65-8232-b51f17e9b24b' => Notification::class,
        'fbe7ed33-c972-44ed-b81b-bb44bab8b6b3' => Period::class,
        '24e4b737-df4e-4ef1-bbb7-b3aeb0d80d1b' => SystemSetting::class,
        '8e71e7d1-4fcd-47e7-bc39-f52116f443df' => Task::class,
        '9f5e1437-d7ff-421d-9401-0984cc3bec83' => Team::class,
        '88c14ad7-aebc-488c-a1cb-bf4e2fe8af44' => ContractTemplate::class,
        '272620db-2f77-4efa-a68d-52f6c07beb52' => HpeContractTemplate::class,
        '746a4a92-d027-4911-9233-bea7271c7f24' => QuoteTemplate::class,
        '9d79573b-0bfa-402e-adf0-6f27cf0bee81' => TemplateField::class,
        'ccfecfda-3db9-4026-a43c-2426a3bd4afa' => TemplateFieldType::class,
        '9df77e7a-8e79-414b-bf09-4af15c5c5097' => TemplateForm::class,
        '7209618c-9c1a-4b52-ba1d-933b3a433b3c' => User::class,
        'f61ab618-f194-4966-bb94-373b393efd76' => UserForm::class,
        '6ffe5cb7-daa1-4f84-a83a-959181ab05bd' => Vendor::class,
        '61aef3e5-df47-44bb-9117-ff55a65bf682' => WorldwideQuoteAsset::class,
        'e2d9238f-55d8-4440-be77-d3529e26d2d3' => WorldwideQuoteAssetsGroup::class,
        '6c0f3f29-2d00-4174-9ef8-55aa5889a812' => Quote::class,
        '629f4c90-cd1f-479d-b60c-af912fa5fc4a' => Opportunity::class,
        'e6821c91-a534-4018-a256-5f9a71e1f7a7' => OpportunitySupplier::class,
        '4d6833e8-d018-4934-bfae-e8587f7aec51' => WorldwideQuote::class,
        '9d7c91c4-5308-4a40-b49e-f10ae552e480' => WorldwideQuoteVersion::class,
        'd5ac95d7-dcd3-4958-acce-82c9aba2f3cd' => SalesOrderTemplate::class,
        'bd250dc5-a62c-41e5-9aa4-022cf7c86de1' => TemplateSchema::class,
        '8cc6c6ce-1a57-4d51-9557-3e87c285efa1' => Pipeline::class,
        'f904f1d8-3209-4f09-8e28-13d116555e1f' => OpportunityForm::class,
        'eda5b270-8bd8-4809-8ce0-cb6379fe1b01' => OpportunityFormSchema::class,
        '37ab1118-a078-4f2d-b86a-826002f478b2' => 'App\Models\Note\WorldwideQuoteNote',
        'bdfc1329-b064-476f-8d5d-fbccdc02b278' => 'App\Models\Note\CompanyNote',
        '5b2fe950-aa70-4c36-9b1f-1383daecbb18' => Company::class,
    ];
    protected array $tableMorphColumn = [
        'activity_log' => ['subject_type', 'causer_type'],
        'addressables' => ['addressable_type'],
        'contactables' => ['contactable_type'],
        'discounts' => ['discountable_type'],
        'images' => ['imageable_type'],
        'model_has_permissions' => ['model_type'],
        'model_has_roles' => ['model_type'],
        'model_notifications' => ['model_type'],
        'notifications' => ['subject_type'],
        'tasks' => ['taskable_type'],
        'quote_totals' => ['quote_type'],
        'worldwide_distributions' => ['worldwide_quote_type'],
        'worldwide_quote_assets' => ['worldwide_quote_type']
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();

        foreach ($this->tableMorphColumn as $table => $morphColumns) {

            foreach ($this->morphMap as $morphType => $class) {

                foreach ($morphColumns as $column) {

                    DB::table($table)
                        ->where($column, $class)
                        ->update([
                            $column => $morphType,
                        ]);

                }
            }

        }

        DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::beginTransaction();

        foreach ($this->tableMorphColumn as $table => $morphColumns) {

            foreach ($this->morphMap as $morphType => $class) {

                foreach ($morphColumns as $column) {

                    DB::table($table)
                        ->where($column, $morphType)
                        ->update([
                            $column => $class,
                        ]);

                }
            }

        }

        DB::commit();
    }
}
