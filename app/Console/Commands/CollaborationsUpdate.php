<?php namespace App\Console\Commands;

use App\Models \ {
    Role,
    User,
    Vendor,
    Company,
    Quote\Quote,
    QuoteFile\QuoteFile,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND,
    Quote\Margin\CountryMargin
};
use Illuminate\Console\Command;
use Str;

class CollaborationsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collaborations:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign collaboration_id attribute to Models which were created by Users with Administrator Role';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Updating Collaborations for Administrators...");

        $this->reAssignAdministrators();

        $administrators = User::administrators()->get();

        $administrators->each(function ($administrator) {
            $administrator->collaboration_id = $administrator->id;
            $administrator->save();
            $this->output->write('.');
        });

        $this->info("\nCollaborations for Administrators were updated!");


        $this->updateModelCollaborations(
            [
                Role::class,
                Quote::class,
                QuoteFile::class,
                QuoteTemplate::class,
                TemplateField::class,
                MultiYearDiscount::class,
                PrePayDiscount::class,
                PromotionalDiscount::class,
                SND::class,
                CountryMargin::class,
                Company::class,
                Vendor::class
            ]
        );
    }

    /**
     * ReAssign Administrator Role to Users which don't have Administrator Role yet
     *
     * @return void
     */
    protected function reAssignAdministrators()
    {
        $users = json_decode(file_get_contents(database_path('seeds/models/users.json')), true);

        User::whereIn('email', collect($users)->pluck('email')->toArray())
            ->nonAdministrators()
            ->get()
            ->each(function ($user) {
                $user->assignRole('Administrator');
            });
    }

    protected function updateModelCollaborations(array $models)
    {
        foreach ($models as $model) {
            $plural = Str::plural(class_basename($model));
            $this->info("Updating Collaborations for {$plural}...");

            $entries = $model::whereHas('user', function ($query) {
                $query->administrators();
            })->get();

            $entries->each(function ($entry) {
                $entry->collaboration_id = $entry->user_id;
                $entry->save();
                $this->output->write('.');
            });

            $this->info("\nCollaborations for {$plural} were updated!");
        }
    }
}
