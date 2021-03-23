<?php

namespace App\Queries;

use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteNote;
use App\Queries\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorldwideQuoteNoteQueries
{
    protected Pipeline $pipeline;

    protected ValidatorInterface $validator;

    public function __construct(Pipeline $pipeline, ValidatorInterface $validator)
    {
        $this->pipeline = $pipeline;
        $this->validator = $validator;
    }

    public function paginateWorldwideQuoteNoteQuery(string $worldwideQuoteId, ?Request $request = null): Builder
    {
        $request ??= new Request;

        $violations = $this->validator->validate($worldwideQuoteId, [new NotBlank, new Uuid]);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        $query = WorldwideQuoteNote::where('worldwide_quote_id', $worldwideQuoteId)
                ->with('user:id,first_name,last_name')
                ->when(filled($request->query('search')), function (Builder $builder) use ($request) {
                    $input = $request->query('search');

                    $builder->where('text', 'like', "%$input%");
                });

        return $this->pipeline
            ->send($query)
            ->through([
                \App\Http\Query\OrderByCreatedAt::class,
                \App\Http\Query\DefaultOrderBy::class,
            ])
            ->thenReturn();

    }
}
