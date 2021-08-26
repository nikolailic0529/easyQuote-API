* [Show the list of the system settings](#show-the-list-of-the-system-settings)
* [Update the system settings](#update-the-system-settings)

# Show the list of the system settings

    [GET] api/settings

    Response:
    {
        "global": [
            "*": {
                "id",
                "value",
                "possible_values": [
                    "*": {
                        "label",
                        "value",
                    },
                ],
                "is_read_only",
                "validation",
                "label",
                "field_title",
                "field_type",
            }
        ],
        "exchange_rates": [
            "*": {
                ...
            }
        ],
        "maintenance": [
            "*": {
                ...
            }
        ],
    }

# Update the system settings

    [PATCH] api/settings

    Payload:
    [
        "*": {
            "id": {setting_uuid}, *,
            "value": {setting_value}, *
        }
    ]

