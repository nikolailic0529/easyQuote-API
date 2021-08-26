* [Show Values of Custom Field](#show-values-of-custom-field)
* [Update Values of Custom Field](#update-values-of-custom-field)

# Show Values of Custom Field
    
    [GET] api/custom-field-values/{custom_field_name}

    Available Custom Fields:

    opportunity_lost_reasons
    quote_dead_reasons
    quote_payment_terms
    task_statuses

# Update Values of Custom Field:
    
    [PUT] api/custom-field-values/{custom_field_name}

    Payload:
    {
        "field_values": [
            "*": {
                "id": {value_uuid}, * (null or uuid)
                "field_value": {string_max_500_chars},
                "is_default": {bool}
            }
        ]
    }

    The field values with defined id will be updated,
    values with null id will be created,
    other existing field values will be deleted.
