<?php

namespace Iquesters\Dev\Database\Seeders;

use Iquesters\Foundation\Database\Seeders\BaseSeeder;

class DevSeeder extends BaseSeeder
{
    protected string $moduleName = 'dev';

    protected string $description = 'External system integrations module';

    protected array $metas = [
        'module_icon' => 'fas fa-code',
        'module_sidebar_menu' => [
            [
                "icon" => "fas fa-draw-polygon",
                "label" => "Vector list",
                "route" => "ui.list",
                "table_schema" => [
                    "slug" => "vector-response-table",
                    "name" => "Vector Response",
                    "description" => "Datatable schema for vector response",
                    "schema" => [
                        "entity" => "vector_responses",
                        "dt-options" => [
                            "columns" => [
                                ["data" => "id", "title" => "ID", "visible" => true],
                                [
                                    "data" => "integration_id",
                                    "title" => "Integration Id",
                                    "visible" => true,
                                    "link" => true,
                                    "form-schema-uid" => "vector_response-details"
                                ],
                                [
                                    "data" => "response",
                                    "title" => "Response",
                                    "visible" => true,
                                ],
                                [
                                    "data" => "started_at",
                                    "title" => "Started At",
                                    "visible" => true,
                                ],
                                [
                                    "data" => "Finished At",
                                    "title" => "Timeout",
                                    "visible" => true,
                                ],
                                [
                                    "data" => "Duration Seconds",
                                    "title" => "Sleep",
                                    "visible" => true,
                                ],
                                [
                                    "data" => "status",
                                    "title" => "Status",
                                    "visible" => true,
                                ],
                            ],
                            "options" => [
                                "pageLength" => 10,
                                "order" => [[0, "desc"]],
                                "responsive" => true
                            ]
                        ],
                        "default_view_mode" => "table"
                    ]
                ]
            ]
        ],
    ];

    protected array $entities = [];

    /**
     * Custom seeding logic for dev module
     */
    protected function seedCustom(): void
    {
        
    }
}