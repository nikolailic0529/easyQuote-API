<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    public function up(): void
    {
        $templates = DB::table('contract_templates')->lazyById(10);

        $containsQuoteAssets = static function (iterable $rows): bool {
            foreach ($rows as $r) {
                foreach ($r['child'] as $ch) {
                    foreach ($ch['controls'] as $control) {
                        if (($control['type'] ?? '') === 'quote_assets') {
                            return true;
                        }
                    }
                }
            }

            return false;
        };

        $quoteAssetsRow = [
            'id' => '4531244a-e179-4f22-b0a0-5e050074264d',
            'name' => 'Single Column',
            'class' => 'single-column field-dragger',
            'order' => 1,
            'toggle' => false,
            'controls' => [],
            'is_field' => false,
            'droppable' => false,
            'decoration' => '1',
            'visibility' => false,
            'child' => [
                [
                    'id' => 'd5ace174-076c-4267-b1ab-84c413d1cb62',
                    'class' => 'col-lg-12 border-right',
                    'controls' => [
                        [
                            'id' => '74b83b0e-be83-447e-99d6-1ebb0298c6c5',
                            'css' => null,
                            'src' => null,
                            'name' => 'quote_assets',
                            'show' => false,
                            'type' => 'quote_assets',
                            'class' => null,
                            'label' => 'Quote Assets',
                            'value' => null,
                            'is_field' => true,
                            'is_image' => false,
                            'droppable' => false,
                            'is_system' => false,
                            'is_required' => false,
                            'attached_child_id' => 'd5ace174-076c-4267-b1ab-84c413d1cb62',
                            'attached_element_id' => '4531244a-e179-4f22-b0a0-5e050074264d',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($templates as $t) {
            $fd = json_decode($t->form_data, true);

            if ($containsQuoteAssets($fd['data_pages'])) {
                continue;
            }

            $fd['data_pages'][] = $quoteAssetsRow;

            DB::table('contract_templates')
                ->where('id', $t->id)
                ->update(['form_data' => json_encode($fd)]);
        }
    }

    public function down(): void
    {
    }
};
