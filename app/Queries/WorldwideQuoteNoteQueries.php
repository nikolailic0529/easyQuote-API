<?php

namespace App\Queries;

use App\Models\Quote\WorldwideQuoteNote;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorldwideQuoteNoteQueries
{
    public function __construct(protected ValidatorInterface $validator)
    {
    }

    public function paginateWorldwideQuoteNoteQuery(string $worldwideQuoteId, ?Request $request = null): Builder
    {
        $request ??= new Request;

        $violations = $this->validator->validate($worldwideQuoteId, [new NotBlank, new Uuid]);

        count($violations) && throw new ValidationFailedException($worldwideQuoteId, $violations);

        $query = WorldwideQuoteNote::query()
            ->where('worldwide_quote_id', $worldwideQuoteId)
            ->with('user:id,first_name,last_name');

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->allowOrderFields(...[
                'created_at',
            ])
            ->allowQuickSearchFields(...[
                'text',
            ])
            ->process();

    }
}
