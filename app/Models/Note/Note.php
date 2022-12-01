<?php

namespace App\Models\Note;

use App\Contracts\HasOwner;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteVersion;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\User;
use App\Traits\HasTimestamps;
use App\Traits\Uuid;
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
 * @property string|null $pl_reference
 * @property string|null $note
 * @property int|null $flags
 *
 * @property-read bool $from_entity_wizard
 * @property-read Collection<int, Opportunity>|Opportunity[] $opportunitiesHaveNote
 * @property-read Collection<int, Company>|Company[] $companiesHaveNote
 * @property-read Collection<int, Quote>|Quote[] $rescueQuotesHaveNote
 * @property-read Collection<int, QuoteVersion>|QuoteVersion[] $rescueQuoteVersionsHaveNote
 * @property-read Collection<int, WorldwideQuote>|WorldwideQuote[] $worldwideQuotesHaveNote
 * @property-read Collection<int, WorldwideQuoteVersion>|WorldwideQuoteVersion[] $worldwideQuoteVersionsHaveNote
 * @property-read Collection<int, Contact>|Contact[] $contactsHaveNote
 * @property-read Collection<int, ModelHasNotes> $modelsHaveNote
 */
class Note extends Model implements HasOwner
{
    use Uuid, SoftDeletes, HasFactory, HasTimestamps;

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
        return $this->belongsTo(User::class, 'user_id');
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
