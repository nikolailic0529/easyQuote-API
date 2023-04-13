<?php

namespace Tests\Unit;

use App\Domain\Authorization\Models\Permission;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Services\RolePresenter;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class RolePermissionsPresentationTest extends TestCase
{
    public function testItPresentsSuperRole(): void
    {
        /** @var Role $r */
        $r = Role::factory()->make(['name' => 'Administrator']);

        /** @var RolePresenter $presenter */
        $presenter = $this->app->make(RolePresenter::class);

        $result = $presenter->presentModules($r);

        $expected = [
            [
                'module' => 'Addresses',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [],
            ],
            [
                'module' => 'Assets',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                ],
            ],
            [
                'module' => 'Audit',
                'privilege' => 'Read Only',
                'submodules' => [],
            ],
            [
                'module' => 'Companies',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [],
            ],
            [
                'module' => 'Contacts',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [],
            ],
            [
                'module' => 'Contracts',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                    [
                        'submodule' => 'HPE Contracts',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Quote Based Contracts',
                        'privilege' => 'Read, Write and Delete',
                    ],
                ],
            ],
            [
                'module' => 'Countries',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [],
            ],
            [
                'module' => 'Data Allocations',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [],
            ],
            [
                'module' => 'Discounts',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                    [
                        'submodule' => 'Multi-Year Discounts',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Pre-Pay Discounts',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Promotional Discounts',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Special Negotiation Discounts',
                        'privilege' => 'Read, Write and Delete',
                    ],
                ],
            ],
            [
                'module' => 'Margins',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                ],
            ],
            [
                'module' => 'Opportunities',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                ],
            ],
            [
                'module' => 'Quotes',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                    [
                        'submodule' => 'External Quotes',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Internal Quotes',
                        'privilege' => 'Read, Write and Delete',
                    ],
                ],
            ],
            [
                'module' => 'Renewals',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                ],
            ],
            [
                'module' => 'Sales Orders',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                ],
            ],
            [
                'module' => 'Settings',
                'privilege' => 'Read & Write',
                'submodules' => [
                ],
            ],
            [
                'module' => 'Templates',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                    [
                        'submodule' => 'Contract Templates',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'HPE Contract Templates',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Importable Columns',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Opportunity Forms',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Quote Task Template',
                        'privilege' => 'Read & Write',
                    ],
                    [
                        'submodule' => 'Quote Templates',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Sales Order Templates',
                        'privilege' => 'Read, Write and Delete',
                    ],
                ],
            ],
            [
                'module' => 'Users',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [
                    [
                        'submodule' => 'Invitations',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Roles',
                        'privilege' => 'Read, Write and Delete',
                    ],
                    [
                        'submodule' => 'Teams',
                        'privilege' => 'Read, Write and Delete',
                    ],
                ],
            ],
            [
                'module' => 'Vendors',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [],
            ],
            [
                'module' => 'Worldwide Quotes',
                'privilege' => 'Read, Write and Delete',
                'submodules' => [],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider accessToCertainModuleDataProvider
     */
    public function testItPresentsRoleThatHasAccessToCertainModule(array $permissions, array $expected): void
    {
        $r = $this->makeRoleWithPermissions($permissions);

        /** @var RolePresenter $presenter */
        $presenter = $this->app->make(RolePresenter::class);

        $result = $presenter->presentModules($r);

        $this->assertEquals($expected, $result);
    }

    protected function accessToCertainModuleDataProvider(): iterable
    {
        yield 'Addresses: RO' => [
            ['view_addresses'],
            [
                [
                    'module' => 'Addresses',
                    'privilege' => 'Read Only',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Addresses: RW' => [
            ['view_addresses', 'create_addresses', 'update_addresses'],
            [
                [
                    'module' => 'Addresses',
                    'privilege' => 'Read & Write',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Addresses: RWD' => [
            ['view_addresses', 'create_addresses', 'update_addresses', 'delete_addresses'],
            [
                [
                    'module' => 'Addresses',
                    'privilege' => 'Read, Write and Delete',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Contacts: RO' => [
            ['view_contacts'],
            [
                [
                    'module' => 'Contacts',
                    'privilege' => 'Read Only',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Contacts: RW' => [
            ['view_contacts', 'create_contacts', 'update_contacts'],
            [
                [
                    'module' => 'Contacts',
                    'privilege' => 'Read & Write',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Contacts: RWD' => [
            ['view_contacts', 'create_contacts', 'update_contacts', 'delete_contacts'],
            [
                [
                    'module' => 'Contacts',
                    'privilege' => 'Read, Write and Delete',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Quotes: RO' => [
            ['view_own_quotes', 'view_quote_files'],
            [
                [
                    'module' => 'Quotes',
                    'privilege' => 'Read Only',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Quotes: RW' => [
            [
                'view_own_quotes', 'view_quote_files', 'create_quotes', 'update_own_quotes', 'create_quote_files', 'update_quote_files',
                'handle_quote_files',
            ],
            [
                [
                    'module' => 'Quotes',
                    'privilege' => 'Read & Write',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Quotes: RWD' => [
            [
                'view_own_quotes', 'view_quote_files', 'create_quotes', 'update_own_quotes', 'create_quote_files', 'update_quote_files',
                'handle_quote_files', 'delete_own_quotes', 'delete_quote_files', 'delete_rfq',
            ],
            [
                [
                    'module' => 'Quotes',
                    'privilege' => 'Read, Write and Delete',
                    'submodules' => [],
                ],
            ],
        ];

        yield 'Quotes: RO, External Quotes: RO' => [
            ['view_own_quotes', 'view_quote_files', 'view_own_external_quotes'],
            [
                [
                    'module' => 'Quotes',
                    'privilege' => 'Read Only',
                    'submodules' => [
                        [
                            'submodule' => 'External Quotes',
                            'privilege' => 'Read Only',
                        ],
                    ],
                ],
            ],
        ];

        yield 'Quotes: RO, External Quotes: RO, Internal Quotes: RO' => [
            ['view_own_quotes', 'view_quote_files', 'view_own_external_quotes', 'view_own_internal_quotes'],
            [
                [
                    'module' => 'Quotes',
                    'privilege' => 'Read Only',
                    'submodules' => [
                        [
                            'submodule' => 'External Quotes',
                            'privilege' => 'Read Only',
                        ],
                        [
                            'submodule' => 'Internal Quotes',
                            'privilege' => 'Read Only',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function makeRoleWithPermissions(array $permissions): Role
    {
        /** @var Role $r */
        $r = Role::factory()->make();
        $r->setRelation('permissions', Collection::empty());

        foreach ($permissions as $name) {
            $r->permissions->push(new Permission(['name' => $name]));
        }

        return $r;
    }
}
