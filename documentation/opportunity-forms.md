* [Paginate Opportunity forms](#paginate-opportunity-forms)
* [Create Opportunity form](#create-opportunity-form)
* [Update Opportunity form](#update-opportunity-form)
* [Delete Opportunity form](#delete-opportunity-form)


# Paginate Opportunity forms

    [GET] api/opportunity-forms
    
    Available query parameters:
    order_by_pipeline_name={asc|desc}
    order_by_created_at={asc|desc}
    order_by_updated_at={asc|desc}

# Create Opportunity form

    [POST] api/opportunity-forms

    Payload:
    {
        "pipeline_id": {unique_pipeline_uuid} *
    }

    ---

    Use the following endpoint to fetch pipelines without opportunity form:
    
    [GET] api/pipelines/list/without-opportunity-form

# Update Opportunity form

    [PATCH] api/opportunity-forms/{opportunity_form_uuid}

    Payload:
    {
        "pipeline_id": {unique_pipeline_uuid} *
    }

    ---

    Use the following endpoint to fetch pipelines without opportunity form:
    
    [GET] api/pipelines/list/without-opportunity-form

# Update Schema of Opportunity form

    [PATCH] api/opportunity-forms/{opportunity_form_uuid}/schema

    Payload:
    {
        "form_data": [
            "*": {
                "id",
                "name",
                "class",
                "order",
                "child": [
                    "*": {
                        "id",
                        "class",
                        "position",
                        "controls",
                        "controls": [
                            "*": {
                                "id",
                                "name",
                                "type",
                                "class",
                                "label",
                                "value"
                            }
                        ]
                    }
                ]
            }
        ]
    }
    

# Delete Opportunity form

    [DELETE] api/opportunity-forms/{opportunity_form_uuid}
