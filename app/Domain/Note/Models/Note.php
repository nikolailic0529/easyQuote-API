<?php

namespace App\Domain\Note\Models;

use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\QuoteVersion;
use App\Domain\Shared\Eloquent\Concerns\HasTimestamps;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null                                                                                          $pl_reference
 * @property string|null                                                                                          $note
 * @property int|null                                                                                             $flags
 * @property bool                                                                                                 $from_entity_wizard
 * @property Collection<int, \App\Domain\Worldwide\Models\Opportunity>|\App\Domain\Worldwide\Models\Opportunity[] $opportunitiesHaveNote
 * @property Collection<int, \App\Domain\Company\Models\Company>|Company[]                                        $companiesHaveNote
 * @property Collection<int, Quote>|\App\Domain\Rescue\Models\Quote[]                                             $rescueQuotesHaveNote
 * @property Collection<int, QuoteVersion>|QuoteVersion[]                                                         $rescueQuoteVersionsHaveNote
 * @property Collection<int, \App\Domain\Worldwide\Models\WorldwideQuote>|WorldwideQuote[]                        $worldwideQuotesHaveNote
 * @property Collection<int, WorldwideQuoteVersion>|WorldwideQuoteVersion[]                                       $worldwideQuoteVersionsHaveNote
 * @property Collection<int, \App\Domain\Contact\Models\Contact>|\App\Domain\Contact\Models\Contact[]             $contactsHaveNote
 * @property Collection<int, ModelHasNotes>                                                                       $modelsHaveNote
 */
class Note extends Model implements HasOwner
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;
    use HasTimestamps;

    const FROM_ENTITY_WIZARD = 1 << 0;
    const FROM_ENTITY_WIZARD_DRAFT = 1 << 1;
    const FROM_ENTITY_WIZARD_SUBMIT = 1 << 2;
    const SYSTEM = 1 << 3;

    protected $guarded = [];

    protected static function newFactory(): NoteFactory
    {
        return NoteFactory::new();
    }

    public function getFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }

    protected function fromEntityWizard(): Attribute
    {
        return Attribute::get(fn (): bool => $this->getFlag(self::FROM_ENTITY_WIZARD));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'user_id');
    }

    public function modelsHaveNote(): HasMany
    {
        return $this->hasMany(ModelHasNotes::class);
    }

    public function opportunitiesHaveNote(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, name: 'model', table: (new ModelHasNotes())->getTable())->using(ModelHasNotes::class);
    }

    public function companiesHaveNote(): MorphToMany
    {
        return $this->morphedByMany(Company::class, name: 'model', table: (new ModelHasNotes())->getTable())->using(ModelHasNotes::class);
    }

    public function rescueQuotesHaveNote(): MorphToMany
    {
        return $this->morphedByMany(Quote::class, name: 'model', table: (new ModelHasNotes())->getTable())->using(ModelHasNotes::class);
    }

    public function rescueQuoteVersionsHaveNote(): MorphToMany
    {
        return $this->morphedByMany(QuoteVersion::class, name: 'model', table: (new ModelHasNotes())->getTable())->using(ModelHasNotes::class);
    }

    public function worldwideQuotesHaveNote(): MorphToMany
    {
        return $this->morphedByMany(WorldwideQuote::class, name: 'model', table: (new ModelHasNotes())->getTable())->using(ModelHasNotes::class);
    }

    public function worldwideQuoteVersionsHaveNote(): MorphToMany
    {
        return $this->morphedByMany(WorldwideQuoteVersion::class, name: 'model', table: (new ModelHasNotes())->getTable())->using(ModelHasNotes::class);
    }

    public function contactsHaveNote(): MorphToMany
    {
        return $this->morphedByMany(Contact::class, name: 'model', table: (new ModelHasNotes())->getTable())->using(ModelHasNotes::class);
    }
}
