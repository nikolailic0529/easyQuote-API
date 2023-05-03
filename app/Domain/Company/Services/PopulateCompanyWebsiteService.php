<?php

namespace App\Domain\Company\Services;

use App\Domain\Company\Events\CompanyUpdated;
use App\Domain\Company\Models\Company;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PopulateCompanyWebsiteService implements LoggerAware
{
    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
        protected readonly EventDispatcher $eventDispatcher,
        protected readonly array $config,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function work(): void
    {
        $companies = Company::query()
            ->whereNonSystem()
            ->lazyById(100);

        foreach ($companies as $company) {
            $this->populateWebsite($company);
        }
    }

    public function populateWebsite(Company $company): void
    {
        $website = $this->resolveWebsiteForCompany($company);

        if (blank($website)) {
            return;
        }

        $oldCompany = (new Company())->setRawAttributes($company->getRawOriginal());

        $company->website = $website;

        if ($company->isClean(['website'])) {
            return;
        }

        $this->logger->info("Populating website: [$company->name] --> [$company->website]", [
            'company_id' => $company->getKey(),
        ]);

        $this->conResolver->connection()
            ->transaction(static function () use ($company): void {
                $company->save();
            });

        $this->eventDispatcher->dispatch(
            new CompanyUpdated(
                company: $company,
                oldCompany: $oldCompany
            )
        );
    }

    public function resolveWebsiteForCompany(Company $company): ?string
    {
        $domain = $company->website;

        if (blank($domain) && filled($company->email)) {
            $domain = $this->parseDomainFromEmail($company->email);
        }

        if (blank($domain)) {
            return null;
        }

        if (!$this->isDomainSuitableForWebsite($domain)) {
            return null;
        }

        return $this->resolveDomainUrl($domain);
    }

    private function resolveDomainUrl(string $domain): string
    {
        if (str_starts_with(trim($domain), 'http')) {
            return $domain;
        }

        $domain = mb_strtolower(trim($domain));

        return "https://$domain";
    }

    private function isDomainSuitableForWebsite(string $domain): bool
    {
        $isEmailDomain = $this->getEmailDomainMap()[$domain] ?? false;

        return !$isEmailDomain;
    }

    private function parseDomainFromEmail(string $email): string
    {
        return (string) Str::of($email)->after('@')->trim();
    }

    /**
     * @return array<string, true>
     */
    private function getEmailDomainMap(): array
    {
        $path = $this->getEmailDomainsListPath();

        return once(static function () use ($path): array {
            if (!file_exists($path)) {
                throw new \Exception("File [$path] doesn't exist.");
            }

            $list = collect(explode("\n", file_get_contents($path)));

            return collect($list)
                ->mapWithKeys(static function (string $domain): array {
                    return [$domain => true];
                })
                ->all();
        });
    }

    protected function getEmailDomainsListPath(): string
    {
        return $this->config['domains_list_path'] ?? '';
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn (): LoggerInterface => $this->logger = $logger);
    }
}
