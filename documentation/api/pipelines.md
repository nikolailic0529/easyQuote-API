
* [Show Spaces](#show-spaces)
* [Create Pipeline](#create-pipeline)
* [Update Pipeline](#update-pipeline)
* [Mark Pipeline as Default](#mark-pipeline-as-default)
* [Delete Pipeline](#delete-pipeline)

# Show Spaces
    
    [GET] api/spaces
    
    Response:
    [
        "*": {
            "id",
            "space_name"
        }
    ]

# Show Pipeline

    [GET] api/pipelines
    
    Response:
    {
        "id",
        "space_id",
        "pipeline_name",
        "pipeline_stages": [
            "*": {
                "id",
                "stage_name",
                "stage_order"
            }
        ]
    }


# Create Pipeline
    
    [POST] api/pipelines

    Payload:
    {
        "space_id": {space_uuid}, *
        "pipeline_name": {string_max_191_chars}, *
        "pipeline_stages": [
            "*": {
                "stage_name": {string_max_191_chars}, *
            }
        ]
    }

# Update Pipeline

    [PATCH] api/pipelines/{pipeline_uuid}

    Payload:
    {
        "space_id": {space_uuid}, *
        "pipeline_name": {string_max_191_chars}, *
        "pipeline_stages": [
            "*": {
                "id": {null_or_stage_uuid}, *
                "stage_name": {string_max_191_chars}, *
            }
        ]
    }

# Mark Pipeline as Default

    [PATCH] api/pipelines/{pipeline_uuid}/default

# Delete Pipeline
    
    [DELETE] api/pipelines/{pipeline_uuid}

